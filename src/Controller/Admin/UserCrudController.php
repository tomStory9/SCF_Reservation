<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('email', 'Mail');
        yield ChoiceField::new('roles', 'User Type')
            ->setChoices(UserRoleEnum::getChoices())
            ->allowMultipleChoices()
            ->renderExpanded()
            ->renderAsBadges();
        yield TextField::new('name', 'First Name');
        yield TextField::new('lastName', 'Last Name');
        yield TextField::new('phone', 'Phone');
        yield TextField::new('company', 'Company');
        yield IntegerField::new('googleId', 'ID Google');
        yield IntegerField::new('lineId', 'ID Line');
        yield BooleanField::new('isVerified', 'Verified User');
    }
}
