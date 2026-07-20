<?php

namespace App\Controller\Admin;

use App\Entity\UserRole;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserRoleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UserRole::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('roleName', 'Role name');
        yield TextField::new('label', 'Label');
        yield IntegerField::new('allocatedHoursPerMonth', 'Allocated free hours per month');
    }
}
