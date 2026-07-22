<?php

namespace App\DataFixtures;

use App\Entity\Facility;
use App\Entity\Zone;
use App\Enum\ZoneType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ZoneFixtures extends Fixture implements DependentFixtureInterface
{
    public const string KUMA_CUBE = 'cube';
    public const string KUMA_LAB = 'lab';
    public const string KUMA_YADO = 'yado';
    public const string KANDA1A = 'kanda1a';
    public const string KANDA1B = 'kanda1b';
    public const string KANDA1C = 'kanda1c';
    public const string KANDA1D = 'kanda1d';

    public function load(ObjectManager $manager): void
    {
        $yamaichi = $this->getReference(FacilityFixtures::YAMAICHI, Facility::class);

        $lab = new Zone();
        $lab->setName('KUMA LAB');
        $lab->setCode('LAB');
        $lab->setTypeZone(ZoneType::TRAINING);
        $lab->setFacility($yamaichi);
        $manager->persist($lab);
        $this->addReference(self::KUMA_LAB, $lab);

        $cube = new Zone();
        $cube->setName('KUMA CUBE');
        $cube->setCode('CUBE');
        $cube->setTypeZone(ZoneType::TRAINING);
        $cube->setFacility($yamaichi);
        $manager->persist($cube);
        $this->addReference(self::KUMA_CUBE, $cube);

        $yado = new Zone();
        $yado->setName('KUMA YADO');
        $yado->setTypeZone(ZoneType::BEDROOM);
        $yado->setFacility($yamaichi);
        $manager->persist($yado);
        $this->addReference(self::KUMA_YADO, $yado);

        $koda = $this->getReference(FacilityFixtures::KODA, Facility::class);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            FacilityFixtures::class,
        ];
    }
}
