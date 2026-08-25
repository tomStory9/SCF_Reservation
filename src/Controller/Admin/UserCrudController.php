<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\UserStatus;
use App\Repository\UserLogEntryRepository;
use App\Repository\UserRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    private ?string $newPassword = null;

    public function __construct(
        private readonly UserRoleRepository $userRoleRepository,
        private readonly UserLogEntryRepository $userLogEntryRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $rolesFromDb = $this->userRoleRepository->findAll();

        $choices = [];
        foreach ($rolesFromDb as $userRole) {
            $roleKey = strtolower((string) $userRole->getRoleName());
            $translationKey = 'admin.enum.user_role.'.str_replace('role_', '', $roleKey);
            $label = $this->translator->trans($translationKey);

            $choices[$translationKey === $label ? (string) $userRole : $label] = $userRole->getRoleName();
        }

        yield TextField::new('email', 'admin.field.email');
        yield ChoiceField::new('roles', 'admin.field.user_type')
            ->setChoices($choices)
            ->allowMultipleChoices(false)
            ->renderAsBadges()
            ->setFormTypeOptions([
                'getter' => function (object $user, FormInterface $form): ?string {
                    $roles = $user->getRoles();

                    return $roles[0] ?? null;
                },
                'setter' => function (object $user, ?string $roleAsString, FormInterface $form): void {
                    $user->setRoles($roleAsString ? [$roleAsString] : []);
                },
            ]);
        yield TextField::new('name', 'admin.field.first_name');
        yield TextField::new('lastName', 'admin.field.last_name');
        yield TextField::new('phone', 'admin.field.phone');
        yield TextField::new('company', 'admin.field.company');
        yield ChoiceField::new('language', 'admin.field.language')
            ->setChoices([
                'admin.enum.language.fr' => 'fr',
                'admin.enum.language.en' => 'en',
                'admin.enum.language.ja' => 'ja',
            ])
            ->formatValue(fn ($value) => null === $value
                ? null
                : $this->translator->trans('admin.enum.language.'.$value));
        yield IntegerField::new('googleId', 'admin.field.google_id');
        yield IntegerField::new('lineId', 'admin.field.line_id');
        yield ChoiceField::new('userStatus', 'admin.field.user_status')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => UserStatus::class,
            ])
            ->formatValue(fn ($value) => null === $value
                ? null
                : $this->translator->trans('admin.enum.user_status.'.$value->value));
        yield BooleanField::new('isVerified', 'admin.field.verified_user');
        yield TextField::new('newPassword', 'admin.field.new_password')
            ->setFormType(PasswordType::class)
            ->setFormTypeOptions([
                'mapped' => false,
                'required' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new Length(min: 8, minMessage: 'errors.password_too_short'),
                    new Regex(pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', message: 'errors.password_invalid'),
                ],
            ])
            ->setHelp('admin.help.new_password')
            ->onlyWhenUpdating();
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $builder = parent::createEditFormBuilder($entityDto, $formOptions, $context);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();

            if (!$form->has('newPassword')) {
                return;
            }

            $newPassword = $form->get('newPassword')->getData();
            $this->newPassword = \is_string($newPassword) && '' !== $newPassword ? $newPassword : null;
        });

        return $builder;
    }

    public function updateEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        $newPassword = $this->newPassword;
        $this->newPassword = null;

        if ($entityInstance instanceof User && null !== $newPassword) {
            $entityInstance->setPassword($this->passwordHasher->hashPassword($entityInstance, $newPassword));
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureActions(Actions $actions): Actions
    {
        $currentUser = $this->getUser();
        $currentUserId = $currentUser instanceof User ? $currentUser->getId() : null;

        $history = Action::new('history', 'admin.action.history')
            ->setIcon('fa fa-clock-rotate-left')
            ->linkToCrudAction('history');

        $ban = Action::new('ban', 'admin.action.ban')
            ->setIcon('fa fa-user-slash')
            ->linkToUrl(fn (User $user): string => $this->getBanUrl($user))
            ->renderAsForm()
            ->asDangerAction()
            ->addCssClass('action-ban')
            ->askConfirmation('admin.confirm.ban_user', 'admin.action.ban')
            ->displayIf(static fn (User $user): bool => UserStatus::SUSPENDED !== $user->getUserStatus()
                && $user->getId() !== $currentUserId);

        return $actions
            ->add(Crud::PAGE_INDEX, $history)
            ->add(Crud::PAGE_DETAIL, $history)
            ->add(Crud::PAGE_EDIT, $history)
            ->add(Crud::PAGE_INDEX, $ban)
            ->add(Crud::PAGE_DETAIL, $ban)
            ->add(Crud::PAGE_EDIT, $ban);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.user')
            ->setEntityLabelInPlural('admin.entity.users');
    }

    #[AdminRoute(path: '/history', name: 'history')]
    public function history(AdminContext $context): Response
    {
        /** @var User|null $user */
        $user = $context->getEntity()?->getInstance();

        if (!$user instanceof User) {
            $this->addFlash('error', 'admin.flash.user_not_found');

            return $this->redirect($this->getIndexUrl());
        }

        return $this->render('admin/user/history.html.twig', [
            'user' => $user,
            'history' => $this->userLogEntryRepository->findForUser($user),
            'back_url' => $this->getIndexUrl(),
        ]);
    }

    #[AdminRoute(path: '/ban', name: 'ban', options: ['methods' => ['POST']])]
    public function banUser(AdminContext $context, Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = $context->getEntity()?->getInstance();

        if (!$user instanceof User) {
            $this->addFlash('error', 'admin.flash.user_not_found');

            return $this->redirect($this->getIndexUrl());
        }

        $token = new CsrfToken('ban-user-'.$user->getId(), (string) $request->query->get('_token'));

        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $currentUser = $this->getUser();

        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('warning', 'admin.flash.cannot_ban_yourself');

            return $this->redirect($this->getIndexUrl());
        }

        if (UserStatus::SUSPENDED === $user->getUserStatus()) {
            $this->addFlash('warning', 'admin.flash.user_already_banned');

            return $this->redirect($this->getIndexUrl());
        }

        $user->setUserStatus(UserStatus::SUSPENDED);
        $this->entityManager->flush();

        $this->addFlash('success', 'admin.flash.user_banned');

        return $this->redirect($this->getIndexUrl());
    }

    private function getBanUrl(User $user): string
    {
        $urlGenerator = clone $this->adminUrlGenerator;

        return $urlGenerator
            ->setController(self::class)
            ->setAction('banUser')
            ->setEntityId($user->getId())
            ->set('_token', $this->csrfTokenManager->getToken('ban-user-'.$user->getId())->getValue())
            ->generateUrl();
    }

    private function getIndexUrl(): string
    {
        return $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->unset('entityId')
            ->unset('_token')
            ->generateUrl();
    }
}
