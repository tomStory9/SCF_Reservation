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
            ->setTitle('SCF Reservation');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-users');
        yield MenuItem::linkTo(BookingCrudController::class, 'Réservations', 'fa fa-book');
        yield MenuItem::linkTo(LocationCrudController::class, 'Lieux', 'fa fa-map-marker');
        yield MenuItem::linkTo(PricingCrudController::class, 'Pricing', 'fa fa-money');
        yield MenuItem::linkTo(TimeSlotCrudController::class, 'Créneaux de reservation', 'fa fa-clock-o');
        yield MenuItem::linkTo(PendingUserCrudController::class, 'Utilisateurs en attente', 'fa fa-user-clock');
    }
}
