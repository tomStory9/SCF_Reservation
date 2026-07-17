<?php

namespace App\Enum;

enum UserRoleEnum: string
{
    case ADMIN = 'ROLE_ADMIN';
    case CA_USER = 'ROLE_CA_USER';
    case AA_USER = 'ROLE_AA_USER';
    case FA_USER = 'ROLE_FA_USER';
    case TM_USER = 'ROLE_TM_USER';
    case DEFAULT_USER = 'ROLE_DEFAULT_USER';

    public function getLabel(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::CA_USER => 'Collective Artist',
            self::AA_USER => 'Associate Artist',
            self::FA_USER => 'Friend Artist',
            self::TM_USER => 'Trainee Member',
            self::DEFAULT_USER => 'Default User',
        };
    }

    public static function getChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $role) {
            $choices[$role->getLabel()] = $role->value;
        }

        return $choices;
    }
}
