<?php

namespace App\Controller\Admin;

use App\Entity\Settings;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;

final class SettingsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Settings::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_EDIT, 'Configuration du site');
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

        yield FormField::addFieldset('admin.field.securite_inscription')
            ->setIcon('fas fa-user-shield');

        yield BooleanField::new('isUserValidationRequired', 'admin.field.validation_manuelle')
            ->setHelp('admin.help.help_verification_user')
            ->renderAsSwitch();
    }
}
