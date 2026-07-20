<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRoleRepository;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\FormInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserRoleRepository $userRoleRepository,
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
            $choices[(string) $userRole] = $userRole->getRoleName();
        }

        yield TextField::new('email', 'Mail');
        yield ChoiceField::new('roles', 'User Type')
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
        yield TextField::new('name', 'First Name');
        yield TextField::new('lastName', 'Last Name');
        yield TextField::new('phone', 'Phone');
        yield TextField::new('company', 'Company');
        yield IntegerField::new('googleId', 'ID Google');
        yield IntegerField::new('lineId', 'ID Line');
        yield BooleanField::new('isVerified', 'Verified User');
    }
}
