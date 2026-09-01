<?php

namespace App\Controller\Admin;

use App\Entity\TimeSlot;
use App\Enum\TimeSlotPeriod;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
class TimeSlotCrudController extends AbstractCrudController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return TimeSlot::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TimeField::new('startTime', 'admin.field.start_time');
        yield TimeField::new('endTime', 'admin.field.end_time');
        yield ChoiceField::new('period', 'admin.field.period')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => TimeSlotPeriod::class,
            ])
            ->formatValue(fn ($value) => null === $value
                ? null
                : $this->translator->trans('admin.enum.time_slot_period.'.$value->value));
    }

    public function configureFilters(Filters $filters): Filters
    {
        $periodChoices = [];
        foreach (TimeSlotPeriod::cases() as $case) {
            $periodChoices['admin.enum.time_slot_period.'.$case->value] = $case;
        }

        return $filters
            ->add(
                ChoiceFilter::new('period', 'admin.field.period')
                ->setChoices($periodChoices)
            )
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.time_slot')
            ->setEntityLabelInPlural('admin.entity.time_slots');
    }
}
