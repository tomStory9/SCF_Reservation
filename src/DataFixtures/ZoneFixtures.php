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
    public const string KODA1A = 'koda1a';
    public const string KODA1B = 'koda1b';
    public const string KODA1C = 'koda1c';
    public const string KODA1D = 'koda1d';
    public const string KODA_DEMI = 'koda_demi';
    public const string KODA_FULL = 'koda_full';

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

        $koda1A = new Zone();
        $koda1A->setName('KODA ZONE A (1/4)');
        $koda1A->setCode('ZONEA');
        $koda1A->setTypeZone(ZoneType::TRAINING);
        $koda1A->setFacility($koda);
        $koda1A->setMaxCapacity(1);
        $manager->persist($koda1A);
        $this->addReference(self::KODA1A, $koda1A);

        $koda1B = new Zone();
        $koda1B->setName('KODA ZONE B (1/4)');
        $koda1B->setCode('ZONEB');
        $koda1B->setTypeZone(ZoneType::TRAINING);
        $koda1B->setFacility($koda);
        $koda1B->setMaxCapacity(1);
        $manager->persist($koda1B);
        $this->addReference(self::KODA1B, $koda1B);

        $koda1C = new Zone();
        $koda1C->setName('KODA ZONE C (1/4)');
        $koda1C->setCode('ZONEC');
        $koda1C->setTypeZone(ZoneType::TRAINING);
        $koda1C->setFacility($koda);
        $koda1C->setMaxCapacity(1);
        $manager->persist($koda1C);
        $this->addReference(self::KODA1C, $koda1C);

        $koda1D = new Zone();
        $koda1D->setName('KODA ZONE D (1/4)');
        $koda1D->setCode('ZONED');
        $koda1D->setTypeZone(ZoneType::TRAINING);
        $koda1D->setFacility($koda);
        $koda1D->setMaxCapacity(1);
        $manager->persist($koda1D);
        $this->addReference(self::KODA1D, $koda1D);

        $kodaDemi = new Zone();
        $kodaDemi->setName('KODA DEMI (2/4)');
        $kodaDemi->setCode('ZONEDEMI');
        $kodaDemi->setTypeZone(ZoneType::TRAINING);
        $kodaDemi->setFacility($koda);
        $kodaDemi->setMaxCapacity(2);
        $manager->persist($kodaDemi);
        $this->addReference(self::KODA_DEMI, $kodaDemi);

        $kodaFull = new Zone();
        $kodaFull->setName('KODA FULL (4/4)');
        $kodaFull->setCode('ZONEFULL');
        $kodaFull->setTypeZone(ZoneType::TRAINING);
        $kodaFull->setFacility($koda);
        $manager->persist($kodaFull);
        $this->addReference(self::KODA_FULL, $kodaFull);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            FacilityFixtures::class,
        ];
    }
}
