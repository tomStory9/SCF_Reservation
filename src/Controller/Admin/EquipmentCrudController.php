<?php

namespace App\Controller\Admin;

use App\Entity\Equipment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class EquipmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Equipment::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'admin.field.equipment_name');
        yield IntegerField::new('unitPrice', 'admin.field.unit_price');
        yield IntegerField::new('maxQuantity', 'admin.field.max_quantity');
        yield AssociationField::new('zone', 'admin.field.zone')
            ->formatValue(fn ($value, $entity) => $value?->getName())
            ->setFormTypeOption('choice_label', 'name');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('zone', 'admin.filter.zone'))
            ->add(TextFilter::new('name', 'admin.filter.equipment_name'))
            ->add(NumericFilter::new('unitPrice', 'admin.filter.unit_price'))
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.equipment')
            ->setEntityLabelInPlural('admin.entity.equipments');
    }
}
