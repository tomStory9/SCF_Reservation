<?php

namespace App\Controller\Admin;

use App\Enum\BookingStatus;
use App\Enum\UserStatus;
use App\Repository\BookingRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    private const DASHBOARD_TIMEZONE = 'Asia/Tokyo';

    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly UserRepository $userRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function index(): Response
    {
        $timezone = new \DateTimeZone(self::DASHBOARD_TIMEZONE);
        $now = new \DateTimeImmutable('now', $timezone);
        $today = $now->setTime(0, 0);
        $tomorrow = $today->modify('+1 day');
        $nextSevenDays = $today->modify('+7 days');
        $monthStart = $today->modify('first day of this month');
        $nextMonth = $monthStart->modify('+1 month');
        $calendarStart = $today->modify('monday this week')->modify('-4 weeks');
        $calendarEnd = $calendarStart->modify('+17 weeks');

        $visibleStatuses = [
            BookingStatus::PENDING,
            BookingStatus::APPROVED,
            BookingStatus::PAID,
        ];
        $confirmedStatuses = [
            BookingStatus::APPROVED,
            BookingStatus::PAID,
        ];

        $statusCounts = array_fill_keys(
            array_map(
                static fn (BookingStatus $status): string => $status->value,
                BookingStatus::cases(),
            ),
            0,
        );
        $statusCounts = array_replace(
            $statusCounts,
            $this->bookingRepository->countByStatus(),
        );

        $calendarEvents = array_map(
            fn (array $booking): array => $this->createCalendarEvent($booking),
            $this->bookingRepository->findCalendarRows(
                $calendarStart,
                $calendarEnd,
                $visibleStatuses,
            ),
        );

        $pendingBookings = $this->bookingRepository->findRecentPendingRows();
        $recentUsers = $this->userRepository->findRecent();

        $bookingDetailUrls = [];
        foreach ($pendingBookings as $booking) {
            $bookingDetailUrls[$booking['id']] = $this->generateAdminUrl(
                BookingCrudController::class,
                Action::DETAIL,
                (int) $booking['id'],
            );
        }

        $userDetailUrls = [];
        foreach ($recentUsers as $user) {
            $userDetailUrls[$user->getId()] = $this->generateAdminUrl(
                UserCrudController::class,
                Action::DETAIL,
                $user->getId(),
            );
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => [
                'pendingBookings' => $statusCounts[BookingStatus::PENDING->value],
                'todayBookings' => $this->bookingRepository->countOverlappingPeriod(
                    $today,
                    $tomorrow,
                    $visibleStatuses,
                ),
                'nextSevenDaysBookings' => $this->bookingRepository->countOverlappingPeriod(
                    $today,
                    $nextSevenDays,
                    $visibleStatuses,
                ),
                'monthValue' => $this->bookingRepository->sumValueStartingInPeriod(
                    $monthStart,
                    $nextMonth,
                    $confirmedStatuses,
                ),
                'pendingUsers' => $this->userRepository->count([
                    'userStatus' => UserStatus::PENDING,
                ]),
                'totalUsers' => $this->userRepository->count([]),
            ],
            'statusCounts' => $statusCounts,
            'statusTotal' => array_sum($statusCounts),
            'pendingBookings' => $pendingBookings,
            'recentUsers' => $recentUsers,
            'calendarEvents' => $calendarEvents,
            'bookingDetailUrls' => $bookingDetailUrls,
            'userDetailUrls' => $userDetailUrls,
            'urls' => [
                'bookings' => $this->generateAdminUrl(BookingCrudController::class),
                'pendingBookings' => $this->generateAdminUrl(PendingBookingCrudController::class),
                'users' => $this->generateAdminUrl(UserCrudController::class),
                'pendingUsers' => $this->generateAdminUrl(PendingUserCrudController::class),
            ],
        ]);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addWebpackEncoreEntry('admin_dashboard');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SCF Reservation')
            ->renderContentMaximized()
            ->setLocales([
                'fr' => 'Français',
                'en' => 'English',
                'ja' => '日本語',
            ]);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('admin.menu.dashboard', 'fa fa-home');
        yield MenuItem::linkTo(UserCrudController::class, 'admin.menu.users', 'fa fa-users');
        yield MenuItem::linkTo(UserRoleCrudController::class, 'admin.menu.user_roles', 'fa fa-users');
        yield MenuItem::linkTo(BookingCrudController::class, 'admin.menu.bookings', 'fa fa-book');
        yield MenuItem::linkTo(ZoneCrudController::class, 'admin.menu.zones', 'fa fa-map-marker');
        yield MenuItem::linkTo(EquipmentCrudController::class, 'admin.menu.equipments', 'fa fa-wrench');
        yield MenuItem::linkTo(PricingCrudController::class, 'admin.menu.pricing', 'fa fa-money');
        yield MenuItem::linkTo(TimeSlotCrudController::class, 'admin.menu.time_slots', 'fa fa-clock-o');
        yield MenuItem::linkTo(PendingUserCrudController::class, 'admin.menu.pending_users', 'fa fa-user-clock');
        yield MenuItem::linkTo(PendingBookingCrudController::class, 'admin.menu.pending_bookings', 'fa fa-book');
    }

    /**
     * @param array<string, mixed> $booking
     */
    private function createCalendarEvent(array $booking): array
    {
        $status = $booking['status'] instanceof BookingStatus
            ? $booking['status']
            : (BookingStatus::tryFrom((string) $booking['status']) ?? BookingStatus::PENDING);
        $zoneName = $booking['zoneName'] ?? $this->translator->trans('admin.dashboard.unknown_zone');
        $userName = trim(sprintf(
            '%s %s',
            $booking['userFirstName'] ?? '',
            $booking['userLastName'] ?? '',
        ));
        $userName = '' !== $userName
            ? $userName
            : $this->translator->trans('admin.dashboard.unknown_user');
        $colors = [
            BookingStatus::PENDING->value => '#e9b94d',
            BookingStatus::APPROVED->value => '#033862',
            BookingStatus::PAID->value => '#059669',
            BookingStatus::DECLINED->value => '#6b7280',
        ];

        return [
            'id' => (string) $booking['id'],
            'title' => trim(sprintf(
                '%s · %s',
                $zoneName,
                $userName,
            )),
            'start' => $booking['startDate']->format('Y-m-d\TH:i:s'),
            'end' => $booking['endDate']->format('Y-m-d\TH:i:s'),
            'allDay' => (bool) $booking['isFullDay'],
            'backgroundColor' => $colors[$status->value],
            'borderColor' => $colors[$status->value],
            'textColor' => BookingStatus::PENDING === $status ? '#033862' : '#ffffff',
            'url' => $this->generateAdminUrl(
                BookingCrudController::class,
                Action::DETAIL,
                (int) $booking['id'],
            ),
            'extendedProps' => [
                'status' => $this->translator->trans('admin.enum.booking_status.'.$status->value),
                'user' => $userName,
                'zone' => $zoneName,
                'facility' => $booking['facilityName'] ?? null,
                'guests' => $booking['guests'],
                'amount' => $booking['amount'],
            ],
        ];
    }

    private function generateAdminUrl(
        string $controller,
        string $action = Action::INDEX,
        ?int $entityId = null,
    ): string {
        $url = $this->adminUrlGenerator
            ->unsetAll()
            ->setController($controller)
            ->setAction($action);

        if (null !== $entityId) {
            $url->setEntityId($entityId);
        }

        return $url->generateUrl();
    }
}
