<?php

namespace App\Controller\Admin;

use App\Entity\TimeSlot;
use App\Enum\TimeSlotPeriod;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

class TimeSlotCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TimeSlot::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield TimeField::new('startTime');
        yield TimeField::new('endTime');
        yield ChoiceField::new('period', 'Period')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => TimeSlotPeriod::class,
            ])
            ->formatValue(fn ($value, $entity) => $value?->value);
    }
}
