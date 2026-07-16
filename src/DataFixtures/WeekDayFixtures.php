<?php

namespace App\DataFixtures;

use App\Entity\WeekDay;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class WeekDayFixtures extends Fixture
{
    public const string LUNDI = 'lundi';
    public const string MARDI = 'mardi';
    public const string MERCREDI = 'mercredi';
    public const string JEUDI = 'jeudi';
    public const string VENDREDI = 'vendredi';
    public const string SAMEDI = 'samedi';
    public const string DIMANCHE = 'dimanche';

    public function load(ObjectManager $manager): void
    {
        $lundi = new WeekDay();
        $lundi->setLabel('Lundi');
        $lundi->setDayNumber(1);
        $manager->persist($lundi);
        $this->addReference(self::LUNDI, $lundi);

        $mardi = new WeekDay();
        $mardi->setLabel('Mardi');
        $mardi->setDayNumber(2);
        $manager->persist($mardi);
        $this->addReference(self::MARDI, $mardi);

        $mercredi = new WeekDay();
        $mercredi->setLabel('Mercredi');
        $mercredi->setDayNumber(3);
        $manager->persist($mercredi);
        $this->addReference(self::MERCREDI, $mercredi);

        $jeudi = new WeekDay();
        $jeudi->setLabel('Jeudi');
        $jeudi->setDayNumber(4);
        $manager->persist($jeudi);
        $this->addReference(self::JEUDI, $jeudi);

        $vendredi = new WeekDay();
        $vendredi->setLabel('Vendredi');
        $vendredi->setDayNumber(5);
        $manager->persist($vendredi);
        $this->addReference(self::VENDREDI, $vendredi);

        $samedi = new WeekDay();
        $samedi->setLabel('Samedi');
        $samedi->setDayNumber(6);
        $manager->persist($samedi);
        $this->addReference(self::SAMEDI, $samedi);

        $dimanche = new WeekDay();
        $dimanche->setLabel('Dimanche');
        $dimanche->setDayNumber(7);
        $manager->persist($dimanche);
        $this->addReference(self::DIMANCHE, $dimanche);

        $manager->flush();
    }
}
