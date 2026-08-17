<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\UserStatus;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class PendingUserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly MailerService $mailerService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    private function getIndexUrl(): string
    {
        return $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.pending_user')
            ->setEntityLabelInPlural('admin.entity.pending_users')
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $approve = Action::new('approve', 'admin.action.approve')
            ->linkToCrudAction('approveUser')
            ->addCssClass('btn btn-success')
            ->displayIf(static function (User $user): bool {
                return UserStatus::PENDING === $user->getUserStatus();
            });

        $decline = Action::new('decline', 'admin.action.decline')
            ->linkToCrudAction('declineUser')
            ->addCssClass('btn btn-danger')
            ->displayIf(static function (User $user): bool {
                return UserStatus::PENDING === $user->getUserStatus();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $approve)
            ->add(Crud::PAGE_INDEX, $decline)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'admin.field.id')->hideOnForm();
        yield EmailField::new('email', 'admin.field.email');
        yield TextField::new('name', 'admin.field.first_name');
        yield TextField::new('lastname', 'admin.field.last_name');
        yield TextField::new('company', 'admin.field.company');
        yield ChoiceField::new('userStatus', 'admin.field.status')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => UserStatus::class,
            ])
            ->formatValue(fn ($value) => null === $value
                ? null
                : $this->translator->trans('admin.enum.user_status.'.$value->value));
    }

    public function createIndexQueryBuilder(
        $searchDto,
        $entityDto,
        $fields,
        $filters
    ): QueryBuilder {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->andWhere('entity.userStatus = :status')
            ->setParameter('status', UserStatus::PENDING);
    }

    #[AdminRoute(path: '/approve-user', name: 'approve_user')]
    public function approveUser(AdminContext $context): RedirectResponse
    {
        /** @var User|null $user */
        $user = $context->getEntity()?->getInstance();

        if (!$user instanceof User) {
            $this->addFlash('error', 'admin.flash.user_not_found');

            return $this->redirect($this->getIndexUrl());
        }

        if (UserStatus::PENDING !== $user->getUserStatus()) {
            $this->addFlash('warning', 'admin.flash.user_no_longer_pending');

            return $this->redirect($this->getIndexUrl());
        }

        $user->setUserStatus(UserStatus::APPROVED);
        $this->entityManager->flush();

        $this->mailerService->sendApprovedEmail($user);

        $this->addFlash('success', 'admin.flash.user_approved');

        return $this->redirect($this->getIndexUrl());
    }

    #[AdminRoute(path: '/decline-user', name: 'decline_user')]
    public function declineUser(AdminContext $context): RedirectResponse
    {
        /** @var User|null $user */
        $user = $context->getEntity()?->getInstance();

        if (!$user instanceof User) {
            $this->addFlash('error', 'admin.flash.user_not_found');

            return $this->redirect($this->getIndexUrl());
        }

        if (UserStatus::PENDING !== $user->getUserStatus()) {
            $this->addFlash('warning', 'admin.flash.user_no_longer_pending');

            return $this->redirect($this->getIndexUrl());
        }

        $user->setUserStatus(UserStatus::DECLINED);
        $this->entityManager->flush();

        $this->mailerService->sendDeniedEmail($user);
        $this->addFlash('success', 'admin.flash.user_declined');

        return $this->redirect($this->getIndexUrl());
    }
}
