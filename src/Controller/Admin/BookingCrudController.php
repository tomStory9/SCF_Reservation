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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class BookingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Booking::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('zone', 'admin.field.zone')
            ->formatValue(fn ($value, $entity) => $value?->getName())
            ->setFormTypeOption('choice_label', 'name');
        yield AssociationField::new('userBooking', 'admin.field.user')
            ->formatValue(fn ($value, $entity) => $value?->getFullName())
            ->setFormTypeOption('choice_label', fn ($user) => $user->getFullName());
        yield DateTimeField::new('startDate', 'admin.field.start_date');
        yield DateTimeField::new('endDate', 'admin.field.end_date');
        yield IntegerField::new('price', 'admin.field.price');
        yield IntegerField::new('guestCount', 'admin.field.guest_count');
        yield ChoiceField::new('bookingStatus', 'admin.field.status')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => BookingStatus::class,
            ])
            ->formatValue(fn ($value, $entity) => $value?->value);
        yield DateTimeField::new('CheckedInAt', 'admin.field.checked_in_at');
        yield DateTimeField::new('CheckedOutAt', 'admin.field.checked_out_at');
        yield DateTimeField::new('createdDate', 'admin.field.created_date');
    }
}
