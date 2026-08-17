<?php

namespace App\Controller\Admin;

use App\Entity\UserRole;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
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
        yield TextField::new('roleName', 'admin.field.role_name');
        yield TextField::new('label', 'admin.field.label');
        yield IntegerField::new('allocatedHoursPerMonth', 'admin.field.allocated_hours');
        yield IntegerField::new('maxAdvanceBookingDays', 'admin.field.max_advance_booking');
        yield TextField::new('tarif', 'admin.field.tarif');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.user_role')
            ->setEntityLabelInPlural('admin.entity.user_roles');
    }
}
