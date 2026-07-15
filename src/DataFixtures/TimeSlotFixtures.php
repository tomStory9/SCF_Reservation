<?php

namespace App\DataFixtures;

use App\Entity\TimeSlot;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TimeSlotFixtures extends Fixture
{
    public const string H8 = '8heure';
    public const string H9 = '9heure';
    public const string H10 = '10heure';
    public const string H11 = '11heure';
    public const string H12 = '12heure';
    public const string H13 = '13heure';
    public const string H14 = '14heure';
    public const string H15 = '15heure';
    public const string H16 = '16heure';
    public const string H17 = '17heure';
    public const string H18 = '18heure';
    public const string H19 = '19heure';
    public const string H20 = '20heure';

    public const string MATIN_ETE = 'matin_ete';
    public const string MATIN_AUTRE = 'matin_autre';
    public const string APRES_MIDI = 'apres_midi';
    public const string SOIR = 'soir';

    public function load(ObjectManager $manager): void
    {
        $h8 = new TimeSlot();
        $h8->setStartTime(new \DateTimeImmutable('08:00:00'));
        $h8->setEndTime(new \DateTimeImmutable('09:00:00'));
        $manager->persist($h8);
        $this->addReference(self::H8, $h8);

        $h9 = new TimeSlot();
        $h9->setStartTime(new \DateTimeImmutable('09:00:00'));
        $h9->setEndTime(new \DateTimeImmutable('10:00:00'));
        $manager->persist($h9);
        $this->addReference(self::H9, $h9);

        $h10 = new TimeSlot();
        $h10->setStartTime(new \DateTimeImmutable('10:00:00'));
        $h10->setEndTime(new \DateTimeImmutable('11:00:00'));
        $manager->persist($h10);
        $this->addReference(self::H10, $h10);

        $h11 = new TimeSlot();
        $h11->setStartTime(new \DateTimeImmutable('11:00:00'));
        $h11->setEndTime(new \DateTimeImmutable('12:00:00'));
        $manager->persist($h11);
        $this->addReference(self::H11, $h11);

        $h12 = new TimeSlot();
        $h12->setStartTime(new \DateTimeImmutable('12:00:00'));
        $h12->setEndTime(new \DateTimeImmutable('13:00:00'));
        $manager->persist($h12);
        $this->addReference(self::H12, $h12);

        $h13 = new TimeSlot();
        $h13->setStartTime(new \DateTimeImmutable('13:00:00'));
        $h13->setEndTime(new \DateTimeImmutable('14:00:00'));
        $manager->persist($h13);
        $this->addReference(self::H13, $h13);

        $h14 = new TimeSlot();
        $h14->setStartTime(new \DateTimeImmutable('14:00:00'));
        $h14->setEndTime(new \DateTimeImmutable('15:00:00'));
        $manager->persist($h14);
        $this->addReference(self::H14, $h14);

        $h15 = new TimeSlot();
        $h15->setStartTime(new \DateTimeImmutable('15:00:00'));
        $h15->setEndTime(new \DateTimeImmutable('16:00:00'));
        $manager->persist($h15);
        $this->addReference(self::H15, $h15);

        $h16 = new TimeSlot();
        $h16->setStartTime(new \DateTimeImmutable('16:00:00'));
        $h16->setEndTime(new \DateTimeImmutable('17:00:00'));
        $manager->persist($h16);
        $this->addReference(self::H16, $h16);

        $h17 = new TimeSlot();
        $h17->setStartTime(new \DateTimeImmutable('17:00:00'));
        $h17->setEndTime(new \DateTimeImmutable('18:00:00'));
        $manager->persist($h17);
        $this->addReference(self::H17, $h17);

        $h18 = new TimeSlot();
        $h18->setStartTime(new \DateTimeImmutable('18:00:00'));
        $h18->setEndTime(new \DateTimeImmutable('19:00:00'));
        $manager->persist($h18);
        $this->addReference(self::H18, $h18);

        $h19 = new TimeSlot();
        $h19->setStartTime(new \DateTimeImmutable('19:00:00'));
        $h19->setEndTime(new \DateTimeImmutable('20:00:00'));
        $manager->persist($h19);
        $this->addReference(self::H19, $h19);

        $h20 = new TimeSlot();
        $h20->setStartTime(new \DateTimeImmutable('20:00:00'));
        $h20->setEndTime(new \DateTimeImmutable('21:00:00'));
        $manager->persist($h20);
        $this->addReference(self::H20, $h20);

        $matin_ete = new TimeSlot();
        $matin_ete->setStartTime(new \DateTimeImmutable('08:00:00'));
        $matin_ete->setEndTime(new \DateTimeImmutable('13:00:00'));
        $manager->persist($matin_ete);
        $this->addReference(self::MATIN_ETE, $matin_ete);

        $matin_autre = new TimeSlot();
        $matin_autre->setStartTime(new \DateTimeImmutable('09:00:00'));
        $matin_autre->setEndTime(new \DateTimeImmutable('13:00:00'));
        $manager->persist($matin_autre);
        $this->addReference(self::MATIN_AUTRE, $matin_autre);

        $apres_midi = new TimeSlot();
        $apres_midi->setStartTime(new \DateTimeImmutable('13:00:00'));
        $apres_midi->setEndTime(new \DateTimeImmutable('17:00:00'));
        $manager->persist($apres_midi);
        $this->addReference(self::APRES_MIDI, $apres_midi);

        $soir = new TimeSlot();
        $soir->setStartTime(new \DateTimeImmutable('17:00:00'));
        $soir->setEndTime(new \DateTimeImmutable('21:00:00'));
        $manager->persist($soir);
        $this->addReference(self::SOIR, $soir);

        $manager->flush();
    }
}
