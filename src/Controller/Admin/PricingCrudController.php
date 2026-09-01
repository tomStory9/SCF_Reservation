<?php

namespace App\Controller\Admin;

use App\Entity\Pricing;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
class PricingCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Pricing::class;
    }

    public function configureFields(string $pageName): iterable
    {
        if (Crud::PAGE_EDIT === $pageName) {
            yield AssociationField::new('zone', 'admin.field.zone')->setDisabled();
            yield AssociationField::new('weekDay', 'admin.field.week_day')->setDisabled();
            yield AssociationField::new('timeSlot', 'admin.field.time_slot')->setDisabled();

            yield IntegerField::new('fullPrice', 'admin.field.full_price');
            yield IntegerField::new('reducedPriceA', 'admin.field.reduced_price_a');
            yield IntegerField::new('reducedPriceB', 'admin.field.reduced_price_b');
            // yield IntegerField::new('guestCount', 'Guest Count');

            return;
        }

        yield AssociationField::new('zone', 'admin.field.zone')
            ->formatValue(fn ($value, $entity) => $value?->getName())
            ->setFormTypeOption('choice_label', 'name');
        yield AssociationField::new('timeSlot', 'admin.field.time_slot');
        yield AssociationField::new('weekDay', 'admin.field.week_day')
            ->setFormTypeOption('choice_label', fn ($weekDay) => $this->translateWeekDay($weekDay?->getDayNumber()))
            ->formatValue(fn ($value) => $this->translateWeekDay($value?->getDayNumber()));
        yield IntegerField::new('fullPrice', 'admin.field.full_price');
        yield IntegerField::new('reducedPriceA', 'admin.field.reduced_price_a');
        yield IntegerField::new('reducedPriceB', 'admin.field.reduced_price_b');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('timeSlot', 'admin.filter.time_slot'))
            ->add(EntityFilter::new('zone', 'admin.filter.zone'))
            ->add(NumericFilter::new('fullPrice', 'admin.field.full_price'))
            ->add(NumericFilter::new('reducedPriceA', 'admin.field.reduced_price_a'))
            ->add(NumericFilter::new('reducedPriceB', 'admin.field.reduced_price_b'))
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $request = $this->requestStack->getCurrentRequest();

        $activeDay = $request->query->get('day', 1);

        if ($activeDay) {
            $qb->join('entity.weekDay', 'w')
                ->andWhere('w.dayNumber = :dayNumber')
                ->setParameter('dayNumber', $activeDay);
        }

        return $qb;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.pricing')
            ->setEntityLabelInPlural('admin.entity.pricings')
            ->overrideTemplate('crud/index', 'admin/pricing/index.html.twig')
        ;
    }

    private function translateWeekDay(?int $dayNumber): string
    {
        $day = [
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            7 => 'sunday',
        ][$dayNumber] ?? null;

        return null === $day ? '' : $this->translator->trans('admin.enum.week_day.'.$day);
    }
}
