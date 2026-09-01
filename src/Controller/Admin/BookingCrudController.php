<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Enum\BookingStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
class BookingCrudController extends AbstractCrudController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Booking::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            ->remove(Crud::PAGE_INDEX, Action::EDIT);
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
            ->formatValue(fn ($value) => null === $value
                ? null
                : $this->translator->trans('admin.enum.booking_status.'.$value->value));
        yield DateTimeField::new('CheckedInAt', 'admin.field.checked_in_at');
        yield DateTimeField::new('CheckedOutAt', 'admin.field.checked_out_at');
        yield DateTimeField::new('createdDate', 'admin.field.created_date');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('userBooking', 'admin.filter.user_booking'))
            ->add(EntityFilter::new('zone', 'admin.filter.zone'))

            ->add(
                ChoiceFilter::new('bookingStatus', 'admin.field.status')
                    ->setChoices([
                        $this->translator->trans('admin.enum.booking_status.pending') => BookingStatus::PENDING,
                        $this->translator->trans('admin.enum.booking_status.approved') => BookingStatus::APPROVED,
                        $this->translator->trans('admin.enum.booking_status.paid') => BookingStatus::PAID,
                        $this->translator->trans('admin.enum.booking_status.declined') => BookingStatus::DECLINED,
                    ])
            )

            ->add(DateTimeFilter::new('startDate', 'admin.field.start_date'))
            ->add(DateTimeFilter::new('endDate', 'admin.field.end_date'))
            ->add(DateTimeFilter::new('createdDate', 'admin.field.created_date'))

            ->add(BooleanFilter::new('isFullDay', 'admin.field.is_full_day'));
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.booking')
            ->setEntityLabelInPlural('admin.entity.bookings')
            ->overrideTemplate('crud/detail', 'admin/booking/detail.html.twig');
    }
}
