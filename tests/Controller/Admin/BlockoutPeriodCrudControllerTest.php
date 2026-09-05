<?php

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\BlockoutPeriodCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use PHPUnit\Framework\TestCase;

final class BlockoutPeriodCrudControllerTest extends TestCase
{
    public function testDateFieldsOnlyAllowFullHours(): void
    {
        $fields = iterator_to_array(new BlockoutPeriodCrudController()->configureFields(Crud::PAGE_NEW));

        foreach (array_slice($fields, 0, 2) as $field) {
            $dto = $field->getAsDto();

            self::assertFalse($dto->getFormTypeOption('with_minutes'));
            self::assertFalse($dto->getFormTypeOption('with_seconds'));
            self::assertSame(
                DateTimeField::WIDGET_CHOICE,
                $dto->getCustomOption(DateTimeField::OPTION_WIDGET),
            );
        }
    }
}
