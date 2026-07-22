<?php

namespace App\DataFixtures;

use App\Entity\Facility;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FacilityFixtures extends Fixture
{
    public const string YAMAICHI = 'yamaichi';
    public const string KODA = 'koda';

    public function load(ObjectManager $manager): void
    {
        $yamaichi = new Facility();
        $yamaichi->setName('Yamaichi Mokuzai');
        $yamaichi->setAddress('〒761-2406 香川県丸亀市綾歌町栗熊東3600-5');
        $yamaichi->setInternationalAddress('3600-5 Ayautacho Kurikumahigashi, Marugame, Kagawa 761-2406');
        $yamaichi->setMapLink('todo');
        $manager->persist($yamaichi);
        $this->addReference(self::YAMAICHI, $yamaichi);

        $koda = new Facility();
        $koda->setName('Koda');
        $koda->setAddress('todo');
        $koda->setInternationalAddress('todo');
        $koda->setMapLink('todo');
        $manager->persist($koda);
        $this->addReference(self::KODA, $koda);

        $manager->flush();
    }
}
