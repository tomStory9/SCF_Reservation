<?php

namespace App\Controller\Admin;

use App\Entity\Zone;
use App\Enum\ZoneType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

class ZoneCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Zone::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Name');
        yield ChoiceField::new('typeZone', 'Type')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => ZoneType::class,
            ])
            ->formatValue(fn ($value, $entity) => $value?->value);
        yield IntegerField::new('maxCapacity', 'Max Capacity');
    }
}
