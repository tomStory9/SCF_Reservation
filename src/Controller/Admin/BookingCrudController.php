<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Enum\BookingStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

final class BookingCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Booking::class;
    }

    private function getIndexUrl(): string
    {
        return $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.booking')
            ->setEntityLabelInPlural('admin.entity.bookings')
            ->setDefaultSort([
                'createdDate' => 'DESC',
            ])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $approve = Action::new('approve', 'admin.action.approve')
            ->linkToCrudAction('approveBooking')
            ->addCssClass('btn btn-success')
            ->displayIf(static function (Booking $booking): bool {
                return BookingStatus::PENDING === $booking->getBookingStatus();
            });

        $decline = Action::new('decline', 'admin.action.decline')
            ->linkToCrudAction('declineBooking')
            ->addCssClass('btn btn-danger')
            ->displayIf(static function (Booking $booking): bool {
                return BookingStatus::PENDING === $booking->getBookingStatus();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $approve)
            ->add(Crud::PAGE_INDEX, $decline)
            ->disable(
                Action::NEW,
                Action::EDIT,
                Action::DELETE,
            );
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('zone', 'admin.field.zone')
            ->formatValue(
                fn ($value, $entity) => $value?->getName()
            )
            ->setFormTypeOption('choice_label', 'name');

        yield AssociationField::new('userBooking', 'admin.field.user')
            ->formatValue(
                fn ($value, $entity) => $value?->getFullName()
            )
            ->setFormTypeOption(
                'choice_label',
                fn ($user) => $user->getFullName()
            );

        yield DateTimeField::new('startDate', 'admin.field.start_date');
        yield DateTimeField::new('endDate', 'admin.field.end_date');
        yield IntegerField::new('price', 'admin.field.price');
        yield IntegerField::new('guestCount', 'admin.field.guest_count');

        yield ChoiceField::new('bookingStatus', 'admin.field.status')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => BookingStatus::class,
            ])
            ->formatValue(
                fn ($value) => null === $value
                    ? null
                    : $this->translator->trans('admin.enum.booking_status.'.$value->value)
            );

        yield DateTimeField::new('createdDate', 'admin.field.created_date');
    }

    public function createIndexQueryBuilder(
        $searchDto,
        $entityDto,
        $fields,
        $filters,
    ): QueryBuilder {
        return parent::createIndexQueryBuilder(
            $searchDto,
            $entityDto,
            $fields,
            $filters,
        )
            ->andWhere('entity.bookingStatus = :status')
            ->setParameter('status', BookingStatus::PENDING);
    }

    #[AdminRoute(
        path: '/approve-booking',
        name: 'approve_booking',
    )]
    public function approveBooking(AdminContext $context): RedirectResponse
    {
        /** @var Booking|null $booking */
        $booking = $context->getEntity()?->getInstance();

        if (!$booking instanceof Booking) {
            $this->addFlash(
                'error',
                'admin.flash.booking_not_found',
            );

            return $this->redirect($this->getIndexUrl());
        }

        if (BookingStatus::PENDING !== $booking->getBookingStatus()) {
            $this->addFlash(
                'warning',
                'admin.flash.booking_no_longer_pending',
            );

            return $this->redirect($this->getIndexUrl());
        }

        $booking->setBookingStatus(BookingStatus::APPROVED);

        $this->entityManager->flush();

        $this->addFlash(
            'success',
            'admin.flash.booking_approved',
        );

        return $this->redirect($this->getIndexUrl());
    }

    #[AdminRoute(
        path: '/decline-booking',
        name: 'decline_booking',
    )]
    public function declineBooking(AdminContext $context): RedirectResponse
    {
        /** @var Booking|null $booking */
        $booking = $context->getEntity()?->getInstance();

        if (!$booking instanceof Booking) {
            $this->addFlash(
                'error',
                'admin.flash.booking_not_found',
            );

            return $this->redirect($this->getIndexUrl());
        }

        if (BookingStatus::PENDING !== $booking->getBookingStatus()) {
            $this->addFlash(
                'warning',
                'admin.flash.booking_no_longer_pending',
            );

            return $this->redirect($this->getIndexUrl());
        }

        $booking->setBookingStatus(BookingStatus::DECLINED);

        $this->entityManager->flush();

        $this->addFlash(
            'success',
            'admin.flash.booking_declined',
        );

        return $this->redirect($this->getIndexUrl());
    }
}
