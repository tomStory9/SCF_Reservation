<?php

namespace App\Controller\Admin;

use App\Entity\Settings;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class SettingsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Settings::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.entity.settings')
            ->setEntityLabelInPlural('admin.entity.settings')
            ->setPageTitle(Crud::PAGE_EDIT, 'admin.settings.title');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('admin.field.gestion_chambre')
            ->setIcon('fas fa-bed');

        yield BooleanField::new('isRoomBookingEnabled', 'admin.field.reserver_chambre')
            ->setHelp('admin.help.help_room_enable')
            ->renderAsSwitch();

        yield IntegerField::new('minDayRoomBooking', 'admin.field.min_day_room_booking')
            ->setHelp('admin.help.help_min_day_room_booking');

        yield IntegerField::new('HourCheckInRoom', 'admin.field.hour_checkin_room')
            ->setHelp('admin.help.help_hour_checkin_room');

        yield IntegerField::new('HourCheckOut', 'admin.field.hour_checkout')
            ->setHelp('admin.help.help_hour_checkout');

        yield BooleanField::new('IsPendingRoomBlocking', 'admin.field.pending_room_blocking')
            ->setHelp('admin.help.help_pending_room_blocking')
            ->renderAsSwitch();

        yield FormField::addFieldset('admin.field.securite_inscription')
            ->setIcon('fas fa-user-shield');

        yield BooleanField::new('isUserValidationRequired', 'admin.field.validation_manuelle')
            ->setHelp('admin.help.help_verification_user')
            ->renderAsSwitch();

        yield FormField::addFieldset('admin.field.gestion_reservations')
            ->setIcon('fas fa-calendar-check');

        yield IntegerField::new('minDayBooking', 'admin.field.min_day_booking')
            ->setHelp('admin.help.help_min_day_booking');

        yield BooleanField::new('IsPendingBookingBlocking', 'admin.field.pending_booking_blocking')
            ->setHelp('admin.help.help_pending_booking_blocking')
            ->renderAsSwitch();
    }
}
