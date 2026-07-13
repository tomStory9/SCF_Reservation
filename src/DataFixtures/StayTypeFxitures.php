<?php

namespace App\DataFixtures;

use App\Entity\StayType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StayTypeFxitures extends Fixture
{
    public const string MATIN = 'matin';
    public const string APRESMIDI = 'après-midi';
    public const string SOIR = 'soir';
    public const string JOURNEE = 'journee';
    public const string NUIT = 'nuit';

    public function load(ObjectManager $manager): void
    {
        $matin = new StayType();
        $matin->setName('Matin');
        $manager->persist($matin);
        $this->addReference(self::MATIN, $matin);

        $apresmidi = new StayType();
        $apresmidi->setName('Après-midi');
        $manager->persist($apresmidi);
        $this->addReference(self::APRESMIDI, $apresmidi);

        $soir = new StayType();
        $soir->setName('Soir');
        $manager->persist($soir);
        $this->addReference(self::SOIR, $soir);

        $journee = new StayType();
        $journee->setName('Journée');
        $manager->persist($journee);
        $this->addReference(self::JOURNEE, $journee);

        $nuit = new StayType();
        $nuit->setName('Nuit');
        $manager->persist($nuit);
        $this->addReference(self::NUIT, $nuit);

        $manager->flush();
    }
}
