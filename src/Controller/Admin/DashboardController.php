<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        // return parent::index();

        return $this->render('admin/dashboard/index.html.twig', []);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SCF Reservation')
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
        yield MenuItem::linkTo(PricingCrudController::class, 'admin.menu.pricing', 'fa fa-money');
        yield MenuItem::linkTo(TimeSlotCrudController::class, 'admin.menu.time_slots', 'fa fa-clock-o');
        yield MenuItem::linkTo(PendingUserCrudController::class, 'admin.menu.pending_users', 'fa fa-user-clock');
        yield MenuItem::linkTo(PendingBookingCrudController::class, 'admin.menu.pending_bookings', 'fa fa-book');
    }
}
