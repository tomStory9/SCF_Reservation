<?php

namespace App\Controller\Admin;

use App\Entity\Specialty;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SpecialtyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Specialty::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'admin.field.id')->hideOnForm();
        yield TextField::new('name', 'admin.field.specialty_name');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.specialty')
            ->setEntityLabelInPlural('admin.entity.specialties');
    }
}
