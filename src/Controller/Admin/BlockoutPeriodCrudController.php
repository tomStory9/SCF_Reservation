<?php

namespace App\Controller\Admin;

use App\Entity\BlockoutPeriod;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class BlockoutPeriodCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlockoutPeriod::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateTimeField::new('startDate', 'admin.field.start_date');
        yield DateTimeField::new('endDate', 'admin.field.end_date');
        yield BooleanField::new('active', 'admin.field.active');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.blockout_period')
            ->setEntityLabelInPlural('admin.entity.blockout_periods');
    }
}
