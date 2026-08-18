<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use App\Service\MailerService;
use App\Service\StripePaiementService;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class PendingBookingCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly BookingRepository $bookingRepository,
        private readonly MailerService $mailerService,
        private readonly StripePaiementService $stripePaymentService,
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
        $monthlyBookings = Action::new(
            'monthlyBookings',
            'admin.action.view_month',
        )
            ->setIcon('fa fa-calendar-days')
            ->linkToCrudAction('monthlyBookings')
            ->addCssClass('btn btn-info')
            ->displayIf(static function (Booking $booking): bool {
                return BookingStatus::PENDING
                    === $booking->getBookingStatus();
            });

        $approve = Action::new(
            'approve',
            'admin.action.approve',
        )
            ->setIcon('fa fa-check')
            ->linkToCrudAction('approveBooking')
            ->addCssClass('btn btn-success')
            ->displayIf(static function (Booking $booking): bool {
                return BookingStatus::PENDING
                    === $booking->getBookingStatus();
            });

        $decline = Action::new(
            'decline',
            'admin.action.decline',
        )
            ->setIcon('fa fa-xmark')
            ->linkToCrudAction('declineBooking')
            ->addCssClass('btn btn-danger')
            ->displayIf(static function (Booking $booking): bool {
                return BookingStatus::PENDING
                    === $booking->getBookingStatus();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $monthlyBookings)
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
                fn ($value) => $value?->getName(),
            )
            ->setFormTypeOption(
                'choice_label',
                'name',
            );

        yield AssociationField::new(
            'userBooking',
            'admin.field.user',
        )
            ->formatValue(
                fn ($value) => $value?->getFullName(),
            )
            ->setFormTypeOption(
                'choice_label',
                fn ($user) => $user->getFullName(),
            );

        yield DateTimeField::new(
            'startDate',
            'admin.field.start_date',
        );

        yield DateTimeField::new(
            'endDate',
            'admin.field.end_date',
        );

        yield IntegerField::new(
            'price',
            'admin.field.price',
        );

        yield IntegerField::new(
            'guestCount',
            'admin.field.guest_count',
        );

        yield ChoiceField::new(
            'bookingStatus',
            'admin.field.status',
        )
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => BookingStatus::class,
            ])
            ->formatValue(
                fn ($value) => null === $value
                    ? null
                    : $this->translator->trans('admin.enum.booking_status.'.$value->value),
            );

        yield DateTimeField::new(
            'createdDate',
            'admin.field.created_date',
        );
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
            ->setParameter(
                'status',
                BookingStatus::PENDING,
            );
    }

    #[AdminRoute(
        path: '/monthly-bookings',
        name: 'monthly_bookings',
    )]
    public function monthlyBookings(
        AdminContext $context,
    ): Response {
        /** @var Booking|null $booking */
        $booking = $context
            ->getEntity()
            ?->getInstance();

        if (!$booking instanceof Booking) {
            $this->addFlash(
                'error',
                'admin.flash.booking_not_found',
            );

            return $this->redirect(
                $this->getIndexUrl(),
            );
        }

        $user = $booking->getUserBooking();
        $bookingStartDate = $booking->getStartDate();

        if (null === $user || null === $bookingStartDate) {
            $this->addFlash(
                'error',
                'admin.flash.booking_user_or_date_not_found',
            );

            return $this->redirect(
                $this->getIndexUrl(),
            );
        }

        $monthStart = new \DateTimeImmutable(
            $bookingStartDate->format('Y-m-01 00:00:00'),
        );

        $nextMonth = $monthStart->modify('+1 month');

        $bookings = $this->bookingRepository
            ->findUserBookingsForPeriod(
                $user,
                $monthStart,
                $nextMonth,
            );

        $hoursByBooking = [];
        $alreadyBookedHours = 0.0;
        $currentBookingHours = $this->calculateBookingHours(
            $booking,
        );

        foreach ($bookings as $userBooking) {
            $hours = $this->calculateBookingHours(
                $userBooking,
            );

            $bookingId = $userBooking->getId();

            if (null !== $bookingId) {
                $hoursByBooking[$bookingId] = $hours;
            }

            if ($userBooking->getId() === $booking->getId()) {
                continue;
            }

            $alreadyBookedHours += $hours;
        }

        return $this->render(
            'admin/booking/monthly_bookings.html.twig',
            [
                'booking' => $booking,
                'user' => $user,
                'bookings' => $bookings,
                'hoursByBooking' => $hoursByBooking,
                'alreadyBookedHours' => round(
                    $alreadyBookedHours,
                    2,
                ),
                'currentBookingHours' => round(
                    $currentBookingHours,
                    2,
                ),
                'monthStart' => $monthStart,
                'nextMonth' => $nextMonth,
                'indexUrl' => $this->getIndexUrl(),
            ],
        );
    }

    private function calculateBookingHours(
        Booking $booking,
    ): float {
        $startDate = $booking->getStartDate();
        $endDate = $booking->getEndDate();

        if (null === $startDate || null === $endDate) {
            return 0;
        }

        $durationInSeconds = $endDate->getTimestamp()
            - $startDate->getTimestamp();

        return round(
            $durationInSeconds / 3600,
            2,
        );
    }

    #[AdminRoute(
        path: '/approve-booking',
        name: 'approve_booking',
    )]
    public function approveBooking(
        AdminContext $context,
    ): RedirectResponse {
        /** @var Booking|null $booking */
        $booking = $context
            ->getEntity()
            ?->getInstance();

        if (!$booking instanceof Booking) {
            $this->addFlash(
                'error',
                'admin.flash.booking_not_found',
            );

            return $this->redirect(
                $this->getIndexUrl(),
            );
        }

        if (
            BookingStatus::PENDING
            !== $booking->getBookingStatus()
        ) {
            $this->addFlash(
                'warning',
                'admin.flash.booking_no_longer_pending',
            );

            return $this->redirect(
                $this->getIndexUrl(),
            );
        }
        $booking->setStripeCheckoutUrl($this->stripePaymentService->createPaymentLink($booking->getTotalPrice(), $booking->getUserBooking()->getId(), $booking->getId()));
        $booking->setBookingStatus(
            BookingStatus::APPROVED,
        );
        $this->mailerService->sendBookingConfirmationEmail($booking->getUserBooking(), $booking);

        $this->entityManager->flush();

        $this->addFlash(
            'success',
            'admin.flash.booking_approved',
        );

        return $this->redirect(
            $this->getIndexUrl(),
        );
    }

    #[AdminRoute(
        path: '/decline-booking',
        name: 'decline_booking',
    )]
    public function declineBooking(
        AdminContext $context,
    ): RedirectResponse {
        /** @var Booking|null $booking */
        $booking = $context
            ->getEntity()
            ?->getInstance();

        if (!$booking instanceof Booking) {
            $this->addFlash(
                'error',
                'admin.flash.booking_not_found',
            );

            return $this->redirect(
                $this->getIndexUrl(),
            );
        }

        if (
            BookingStatus::PENDING
            !== $booking->getBookingStatus()
        ) {
            $this->addFlash(
                'warning',
                'admin.flash.booking_no_longer_pending',
            );

            return $this->redirect(
                $this->getIndexUrl(),
            );
        }

        $booking->setBookingStatus(
            BookingStatus::DECLINED,
        );
        $this->mailerService->sendBookingDeniedEmail($booking->getUserBooking(), $booking);
        $this->entityManager->flush();

        $this->addFlash(
            'success',
            'admin.flash.booking_declined',
        );

        return $this->redirect(
            $this->getIndexUrl(),
        );
    }
}
