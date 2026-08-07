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

final class PendingBookingCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly BookingRepository $bookingRepository,
        private readonly MailerService $mailerService,
        private readonly StripePaiementService $stripePaymentService,
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
            ->setEntityLabelInSingular('Réservation')
            ->setEntityLabelInPlural('Réservations')
            ->setDefaultSort([
                'createdDate' => 'DESC',
            ])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $monthlyBookings = Action::new(
            'monthlyBookings',
            'Voir le mois',
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
            'Approuver',
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
            'Refuser',
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
        yield AssociationField::new('zone', 'Zone')
            ->formatValue(
                fn ($value) => $value?->getName(),
            )
            ->setFormTypeOption(
                'choice_label',
                'name',
            );

        yield AssociationField::new(
            'userBooking',
            'Utilisateur',
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
            'Date de début',
        );

        yield DateTimeField::new(
            'endDate',
            'Date de fin',
        );

        yield IntegerField::new(
            'price',
            'Prix',
        );

        yield IntegerField::new(
            'guestCount',
            'Nombre de visiteurs',
        );

        yield ChoiceField::new(
            'bookingStatus',
            'Statut',
        )
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => BookingStatus::class,
            ])
            ->formatValue(
                fn ($value) => $value?->value,
            );

        yield DateTimeField::new(
            'createdDate',
            'Date de réservation',
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
                'Réservation introuvable.',
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
                'Utilisateur ou date de réservation introuvable.',
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
                'Réservation introuvable.',
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
                'Cette réservation n’est plus en attente.',
            );

            return $this->redirect(
                $this->getIndexUrl(),
            );
        }
        $booking->setStripeCheckoutUrl($this->stripePaymentService->createPaymentLink($booking->getPrice(), $booking->getUserBooking()->getId(), $booking->getId()));
        $booking->setBookingStatus(
            BookingStatus::APPROVED,
        );
        $this->mailerService->sendBookingConfirmationEmail($booking->getUserBooking(), $booking);

        $this->entityManager->flush();

        $this->addFlash(
            'success',
            'La réservation a été approuvée.',
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
                'Réservation introuvable.',
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
                'Cette réservation n’est plus en attente.',
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
            'La réservation a été refusée.',
        );

        return $this->redirect(
            $this->getIndexUrl(),
        );
    }
}
