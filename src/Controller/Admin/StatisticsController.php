<?php

namespace App\Controller\Admin;

use App\Repository\BookingRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class StatisticsController extends AbstractController
{
    #[AdminRoute(path: '/statistics', name: 'admin_statistics')]
    public function statistics(UserRepository $userRepository, BookingRepository $bookingRepository): Response
    {
        $currentYear = (int) date('Y');
        $oldestYear = $bookingRepository->getOldestBookingYear();

        $availableYears = range($currentYear, $oldestYear);
        rsort($availableYears);

        // --- 1. STATISTIQUES UTILISATEURS ---
        $nationalities = $userRepository->getNationalityStats();
        $cities = $userRepository->getCityStats();
        $specialties = $userRepository->getSpecialtyStats();
        $avgPracticeYears = $userRepository->getAveragePracticeYears();
        $totalUsers = $userRepository->countTotalUsers();

        // --- 2. STATISTIQUES RÉSERVATIONS ---
        $bookings = $bookingRepository->getRawDataForStatistics();

        $monthlyStats = [];
        // On initialise TOUS les mois de TOUTES les années à zéro
        foreach ($availableYears as $year) {
            for ($i = 1; $i <= 12; ++$i) {
                $monthlyStats[$year][sprintf('%02d', $i)] = ['count' => 0, 'revenue' => 0];
            }
        }

        $zoneStats = [];
        $userStats = [];
        $totalBookings = count($bookings);
        $totalRevenue = 0;

        foreach ($bookings as $b) {
            $start = $b['startDate'];
            $y = $start->format('Y');
            $m = $start->format('m');
            $price = (int) $b['price'];

            $totalRevenue += $price;

            // Ajout dans la bonne année / bon mois
            if (isset($monthlyStats[$y][$m])) {
                ++$monthlyStats[$y][$m]['count'];
                $monthlyStats[$y][$m]['revenue'] += $price;
            }

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
        }

        $currentMonthKey = date('m');
        // KPIs globaux
        $currentMonthRevenue = $monthlyStats[$currentYear][$currentMonthKey]['revenue'] ?? 0;
        $avgMonthlyRevenue = count($availableYears) > 0 ? $totalRevenue / (count($availableYears) * 12) : 0;
        $avgBookingsPerUser = $totalUsers > 0 ? $totalBookings / $totalUsers : 0;

        usort($userStats, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);
        $topUsers = array_slice($userStats, 0, 15);

        // --- 3. TAUX DE REMPLISSAGE (Mois en cours) ---
        $daysInCurrentMonth = (int) date('t');
        $numberOfZones = count($zoneStats) > 0 ? count($zoneStats) : 1;
        $monthlyCapacityHours = $daysInCurrentMonth * 12 * $numberOfZones;

        $currentMonthBookedHours = 0;
        foreach ($bookings as $b) {
            if ($b['startDate']->format('Y-m') === date('Y-m')) {
                $currentMonthBookedHours += $b['isFullDay'] ? 12 : (($b['endDate']->getTimestamp() - $b['startDate']->getTimestamp()) / 3600);
            }
        }
        $occupancyRate = $monthlyCapacityHours > 0 ? ($currentMonthBookedHours / $monthlyCapacityHours) * 100 : 0;

        $chartData = [
            'nationalities' => $nationalities,
            'cities' => $cities,
            'specialties' => $specialties,
            'monthly' => $monthlyStats, // Structure: { 2026: { "01": {...}, "02": {...} } }
            'zones' => $zoneStats,
            'topUsers' => $topUsers,
        ];

        return $this->render('admin/statistics/index.html.twig', [
            'chartData' => json_encode($chartData),
            'availableYears' => $availableYears,
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
