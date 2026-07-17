<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Enum\BookingStatus;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

class BookingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Booking::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('location', 'Location')
            ->formatValue(fn ($value, $entity) => $value?->getName())
            ->setFormTypeOption('choice_label', 'name');
        yield AssociationField::new('userBooking', 'User')
            ->formatValue(fn ($value, $entity) => $value?->getFullName())
            ->setFormTypeOption('choice_label', fn ($user) => $user->getFullName());
        yield DateTimeField::new('startDate');
        yield DateTimeField::new('endDate');
        yield IntegerField::new('price', 'Price');
        yield IntegerField::new('guestCount', 'Nb guest');
        yield ChoiceField::new('bookingStatus', 'Satus')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => BookingStatus::class,
            ])
            ->formatValue(fn ($value, $entity) => $value?->value);
        yield DateTimeField::new('createdDate', 'Booking Date');
    }
}
