<?php

namespace App\Controller\Admin;

use App\Repository\BookingRepository;
use App\Repository\UserRepository;
use App\Repository\ZoneRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class StatisticsController extends AbstractController
{
    #[AdminRoute(path: '/statistics', name: 'admin_statistics')]
    public function statistics(
        Request $request,
        UserRepository $userRepository,
        BookingRepository $bookingRepository,
        ZoneRepository $zoneRepository
    ): Response {
        $currentYear = (int) date('Y');
        $selectedYear = $request->query->getInt('year', $currentYear);
        $oldestYear = $bookingRepository->getOldestBookingYear();

        $availableYears = range($currentYear, $oldestYear);
        rsort($availableYears);

        if (!in_array($selectedYear, $availableYears, true)) {
            $selectedYear = $currentYear;
        }

        $allZones = $zoneRepository->findAll();
        $allZoneNames = array_map(fn ($z) => $z->getName(), $allZones);

        $allUsers = $userRepository->findBy([], ['name' => 'ASC']);
        $usersForFilter = array_map(fn ($u) => ['id' => $u->getId(), 'name' => $u->getFullName()], $allUsers);

        $nationalities = $userRepository->getNationalityStats();
        $cities = $userRepository->getCityStats();
        $specialties = $userRepository->getSpecialtyStats();
        $avgPracticeYears = $userRepository->getAveragePracticeYears();
        $totalUsers = $userRepository->countTotalUsers();

        $bookings = $bookingRepository->getRawDataForStatistics();

        $monthlyStats = [];
        foreach ($availableYears as $year) {
            for ($i = 1; $i <= 12; ++$i) {
                $monthlyStats[$year][sprintf('%02d', $i)] = ['count' => 0, 'revenue' => 0];
            }
        }

        $zoneStats = [];
        $userStats = [];
        $totalBookings = count($bookings);
        $totalRevenue = 0;
        $totalBookedHours = 0;

        $serializedBookingsForJS = [];

        foreach ($bookings as $b) {
            /** @var \DateTimeImmutable $start */
            $start = $b['startDate'];
            /** @var \DateTimeImmutable $end */
            $end = $b['endDate'];

            $y = $start->format('Y');
            $m = $start->format('m');
            $price = (int) $b['price'];

            $totalRevenue += $price;

            if (isset($monthlyStats[$y][$m])) {
                ++$monthlyStats[$y][$m]['count'];
                $monthlyStats[$y][$m]['revenue'] += $price;
            }

            $hours = $b['isFullDay'] ? 12 : (($end->getTimestamp() - $start->getTimestamp()) / 3600);
            $totalBookedHours += $hours;

            $zoneName = $b['zoneName'];
            if (!isset($zoneStats[$zoneName])) {
                $zoneStats[$zoneName] = ['count' => 0, 'revenue' => 0];
            }
            ++$zoneStats[$zoneName]['count'];
            $zoneStats[$zoneName]['revenue'] += $price;

            $userId = $b['userId'];
            if (!isset($userStats[$userId])) {
                $userStats[$userId] = ['name' => $b['userFirstName'].' '.$b['userLastName'], 'count' => 0, 'revenue' => 0];
            }
            ++$userStats[$userId]['count'];
            $userStats[$userId]['revenue'] += $price;

            $serializedBookingsForJS[] = [
                'userId' => $userId,
                'zoneName' => $zoneName,
                'price' => $price,
                'startDate' => $start->format('Y-m-d'),
            ];
        }

        $currentMonthKey = date('Y-m');
        $currentMonthRevenue = $monthlyStats[$currentYear][date('m')]['revenue'] ?? 0;

        $currentYearRevenue = 0;
        if (isset($monthlyStats[$currentYear])) {
            foreach ($monthlyStats[$currentYear] as $monthData) {
                $currentYearRevenue += $monthData['revenue'];
            }
        }
        $avgMonthlyRevenue = $currentYearRevenue / 12;

        $avgBookingsPerUser = $totalUsers > 0 ? $totalBookings / $totalUsers : 0;

        usort($userStats, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);
        $topUsers = array_slice($userStats, 0, 15);

        $daysInCurrentMonth = (int) date('t');
        $numberOfZones = count($zoneStats) > 0 ? count($zoneStats) : 1;
        $monthlyCapacityHours = $daysInCurrentMonth * 12 * $numberOfZones;

        $currentMonthBookedHours = 0;
        if ($selectedYear === $currentYear) {
            foreach ($bookings as $b) {
                if ($b['startDate']->format('Y-m') === $currentMonthKey) {
                    $currentMonthBookedHours += $b['isFullDay'] ? 12 : (($b['endDate']->getTimestamp() - $b['startDate']->getTimestamp()) / 3600);
                }
            }
        }
        $occupancyRate = $monthlyCapacityHours > 0 ? ($currentMonthBookedHours / $monthlyCapacityHours) * 100 : 0;

        $chartData = [
            'nationalities' => $nationalities,
            'cities' => $cities,
            'specialties' => $specialties,
            'monthly' => $monthlyStats,
            'topUsers' => $topUsers,
            'allZoneNames' => $allZoneNames,
            'rawBookings' => $serializedBookingsForJS,
        ];

        return $this->render('admin/statistics/index.html.twig', [
            'chartData' => json_encode($chartData),
            'availableYears' => $availableYears,
            'selectedYear' => $selectedYear,
            'usersForFilter' => $usersForFilter,
            'kpis' => [
                'avgPracticeYears' => $avgPracticeYears,
                'avgBookingsPerUser' => round($avgBookingsPerUser, 1),
                'currentMonthRevenue' => $currentMonthRevenue,
                'avgMonthlyRevenue' => round($avgMonthlyRevenue),
                'occupancyRate' => round($occupancyRate, 1),
                'totalUsers' => $totalUsers,
            ],
        ]);
    }
}
