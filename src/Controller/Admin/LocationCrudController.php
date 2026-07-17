<?php

namespace App\Controller\Admin;

use App\Entity\Location;
use App\Enum\LocationType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

class LocationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Location::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Name');
        yield ChoiceField::new('typeLocation', 'Type')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => LocationType::class,
            ])
            ->formatValue(fn ($value, $entity) => $value?->value);
        yield IntegerField::new('maxCapacity', 'Max Capacity');
    }
}
