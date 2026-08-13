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
    public const string KUMA_YADO_1 = 'yado1';
    public const string KUMA_YADO_2 = 'yado2';
    public const string KUMA_YADO_3 = 'yado3';
    public const string KODA1A = 'koda1a';
    public const string KODA1B = 'koda1b';
    public const string KODA1C = 'koda1c';
    public const string KODA1D = 'koda1d';
    public const string KODA_AB = 'koda_ab';
    public const string KODA_CD = 'koda_cd';
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

        $yado1 = new Zone();
        $yado1->setName('KUMA YADO 1');
        $yado1->setTypeZone(ZoneType::BEDROOM);
        $yado1->setFacility($yamaichi);
        $manager->persist($yado1);
        $this->addReference(self::KUMA_YADO_1, $yado1);

        $yado2 = new Zone();
        $yado2->setName('KUMA YADO 2');
        $yado2->setTypeZone(ZoneType::BEDROOM);
        $yado2->setFacility($yamaichi);
        $manager->persist($yado2);
        $this->addReference(self::KUMA_YADO_2, $yado2);

        $yado3 = new Zone();
        $yado3->setName('KUMA YADO 3');
        $yado3->setTypeZone(ZoneType::BEDROOM);
        $yado3->setFacility($yamaichi);
        $manager->persist($yado3);
        $this->addReference(self::KUMA_YADO_3, $yado3);

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

        $kodaDemiAB = new Zone();
        $kodaDemiAB->setName('KODA A+B (2/4)');
        $kodaDemiAB->setCode('ZONE_AB');
        $kodaDemiAB->setTypeZone(ZoneType::TRAINING);
        $kodaDemiAB->setFacility($koda);
        $kodaDemiAB->setMaxCapacity(2);
        $manager->persist($kodaDemiAB);
        $this->addReference(self::KODA_AB, $kodaDemiAB);

        $kodaDemiCD = new Zone();
        $kodaDemiCD->setName('KODA C+D (2/4)');
        $kodaDemiCD->setCode('ZONE_CD');
        $kodaDemiCD->setTypeZone(ZoneType::TRAINING);
        $kodaDemiCD->setFacility($koda);
        $kodaDemiCD->setMaxCapacity(2);
        $manager->persist($kodaDemiCD);
        $this->addReference(self::KODA_CD, $kodaDemiCD);

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
