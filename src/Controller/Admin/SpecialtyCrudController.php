<?php

namespace App\Controller\Admin;

use App\Entity\Specialty;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SpecialtyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Specialty::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'admin.field.specialty_name');
    }
}
