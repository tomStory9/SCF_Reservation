<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRoleRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserRoleRepository $userRoleRepository,
        private readonly TranslatorInterface $translator,
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
        yield IntegerField::new('googleId', 'admin.field.google_id');
        yield IntegerField::new('lineId', 'admin.field.line_id');
        yield BooleanField::new('isVerified', 'admin.field.verified_user');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.user')
            ->setEntityLabelInPlural('admin.entity.users');
    }
}
