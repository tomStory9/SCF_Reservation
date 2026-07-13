<?php

namespace App\DataFixtures;

use App\Entity\Location;
use App\Entity\PriceByType;
use App\Entity\StayType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PriceByTypeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $matin = $this->getReference(StayTypeFxitures::MATIN, StayType::class);
        $apresmidi = $this->getReference(StayTypeFxitures::APRESMIDI, StayType::class);
        $soir = $this->getReference(StayTypeFxitures::SOIR, StayType::class);
        $journee = $this->getReference(StayTypeFxitures::JOURNEE, StayType::class);
        $nuit = $this->getReference(StayTypeFxitures::NUIT, StayType::class);

        // CUBE
        $cube = $this->getReference(LocationFixtures::CUBE, Location::class);

        $cubeMatin = new PriceByType();
        $cubeMatin->setStayType($matin);
        $cubeMatin->setLocation($cube);
        $cubeMatin->setPrice(3000);

        $cubeApresmidi = new PriceByType();
        $cubeApresmidi->setStayType($apresmidi);
        $cubeApresmidi->setLocation($cube);
        $cubeApresmidi->setPrice(3000);

        $cubeSoir = new PriceByType();
        $cubeSoir->setStayType($soir);
        $cubeSoir->setLocation($cube);
        $cubeSoir->setPrice(3000);

        $cubeJournee = new PriceByType();
        $cubeJournee->setStayType($journee);
        $cubeJournee->setLocation($cube);
        $cubeJournee->setPrice(8000);

        // LAB
        $lab = $this->getReference(LocationFixtures::LAB, Location::class);

        $labMatin = new PriceByType();
        $labMatin->setStayType($matin);
        $labMatin->setLocation($lab);
        $labMatin->setPrice(3000);

        $labApresmidi = new PriceByType();
        $labApresmidi->setStayType($apresmidi);
        $labApresmidi->setLocation($lab);
        $labApresmidi->setPrice(3000);

        $labSoir = new PriceByType();
        $labSoir->setStayType($soir);
        $labSoir->setLocation($lab);
        $labSoir->setPrice(3000);

        $labJournee = new PriceByType();
        $labJournee->setStayType($journee);
        $labJournee->setLocation($lab);
        $labJournee->setPrice(8000);

        // CHAMBRE 1
        $room1 = $this->getReference(LocationFixtures::ROOM1, Location::class);

        $chambre1Nuit = new PriceByType();
        $chambre1Nuit->setStayType($matin);
        $chambre1Nuit->setLocation($room1);
        $chambre1Nuit->setPrice(4000);

        // CHAMBRE 2
        $room2 = $this->getReference(LocationFixtures::ROOM2, Location::class);

        $chambre2Nuit = new PriceByType();
        $chambre2Nuit->setStayType($apresmidi);
        $chambre2Nuit->setLocation($room2);
        $chambre2Nuit->setPrice(4000);

        // CHAMBRE 3
        $room3 = $this->getReference(LocationFixtures::ROOM3, Location::class);

        $chambre3Nuit = new PriceByType();
        $chambre3Nuit->setStayType($soir);
        $chambre3Nuit->setLocation($room3);
        $chambre3Nuit->setPrice(4000);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            StayTypeFxitures::class,
            LocationFixtures::class,
        ];
    }
}
