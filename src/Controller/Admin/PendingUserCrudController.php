<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\UserStatus;
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

final class PendingUserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
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
            ->setEntityLabelInSingular('Compte en attente')
            ->setEntityLabelInPlural('Comptes en attente')
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $approve = Action::new('approve', 'Approuver')
            ->linkToCrudAction('approveUser')
            ->addCssClass('btn btn-success')
            ->displayIf(static function (User $user): bool {
                return UserStatus::PENDING === $user->getUserStatus();
            });

        $decline = Action::new('decline', 'Refuser')
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
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email', 'Email');
        yield TextField::new('name', 'Firstname');
        yield TextField::new('lastname', 'Lastname');
        yield TextField::new('company', 'Company');
        yield ChoiceField::new('userStatus', 'Statut')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => UserStatus::class,
            ])
            ->formatValue(fn ($value, $entity) => $value?->value);
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
            $this->addFlash('error', 'Utilisateur introuvable.');

            return $this->redirect($this->getIndexUrl());
        }

        if (UserStatus::PENDING !== $user->getUserStatus()) {
            $this->addFlash('warning', 'Ce compte n’est plus en attente.');

            return $this->redirect($this->getIndexUrl());
        }

        $user->setUserStatus(UserStatus::APPROVED);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le compte a été approuvé.');

        return $this->redirect($this->getIndexUrl());

        return $this->redirect($this->getIndexUrl());
    }

    #[AdminRoute(path: '/decline-user', name: 'decline_user')]
    public function declineUser(AdminContext $context): RedirectResponse
    {
        /** @var User|null $user */
        $user = $context->getEntity()?->getInstance();

        if (!$user instanceof User) {
            $this->addFlash('error', 'Utilisateur introuvable.');

            return $this->redirect($this->getIndexUrl());
        }

        if (UserStatus::PENDING !== $user->getUserStatus()) {
            $this->addFlash('warning', 'Ce compte n’est plus en attente.');

            return $this->redirect($this->getIndexUrl());
        }

        $user->setUserStatus(UserStatus::DECLINED);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le compte a été refusé.');

        return $this->redirect($this->getIndexUrl());
    }
}
