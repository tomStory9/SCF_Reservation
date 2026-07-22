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
use Symfony\Component\HttpFoundation\RequestStack;

class PricingCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Pricing::class;
    }

    public function configureFields(string $pageName): iterable
    {
        if (Crud::PAGE_EDIT === $pageName) {
            yield AssociationField::new('zone', 'Zone')->setDisabled();
            yield AssociationField::new('weekDay', 'Week Day')->setDisabled();
            yield AssociationField::new('timeSlot', 'Time Slot')->setDisabled();

            yield IntegerField::new('fullPrice', 'Full Price');
            yield IntegerField::new('reducedPriceA', 'Reduced Price A');
            yield IntegerField::new('reducedPriceB', 'Reduced Price B');
            yield IntegerField::new('guestCount', 'Guest Count');

            return;
        }

        yield AssociationField::new('zone', 'Zone')
            ->formatValue(fn ($value, $entity) => $value?->getName())
            ->setFormTypeOption('choice_label', 'name');
        yield AssociationField::new('timeSlot', 'Time Slot');
        yield AssociationField::new('weekDay', 'Week Day')
            ->formatValue(fn ($value, $entity) => $value?->getLabel());
        yield IntegerField::new('fullPrice', 'Full Price');
        yield IntegerField::new('reducedPriceA', 'Reduced Price A');
        yield IntegerField::new('reducedPriceB', 'Reduced Price B');
        yield IntegerField::new('guestCount', 'Guest Count');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('timeSlot', 'Time Slot'))
            ->add(EntityFilter::new('zone', 'Zone'))
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
            ->overrideTemplate('crud/index', 'admin/pricing/index.html.twig')
        ;
    }
}
