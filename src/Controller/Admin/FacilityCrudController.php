<?php

namespace App\Controller\Admin;

use App\Entity\Facility;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FacilityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Facility::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'admin.field.name');
        yield TextField::new('address', 'admin.field.address');
        yield TextField::new('internationalAddress', 'admin.field.internationalAddress');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.facility')
            ->setEntityLabelInPlural('admin.entity.facilities');
    }
}
