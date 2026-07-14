<?php

namespace App\DataFixtures;

use App\Entity\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LocationFixtures extends Fixture
{
    public const string CUBE = 'cube';
    public const string LAB = 'lab';
    public const string KANDA = 'kanda';
    public const string ROOM1 = 'room1';
    public const string ROOM2 = 'room2';
    public const string ROOM3 = 'room3';

    public function load(ObjectManager $manager): void
    {
        // Entrainement
        $lab = new Location();
        $lab->setName('Cirque-MA LAB');
        $lab->setTypeLocation('Entrainement');
        $manager->persist($lab);
        $this->addReference(self::LAB, $lab);

        $cube = new Location();
        $cube->setName('Cirque-MA CUBE');
        $cube->setTypeLocation('Entrainement');
        $manager->persist($cube);
        $this->addReference(self::CUBE, $cube);

        $kanda = new Location();
        $kanda->setName('Ecole primaire Kanda');
        $kanda->setTypeLocation('Entrainement');
        $kanda->setMaxCapacity(4);
        $manager->persist($kanda);
        $this->addReference(self::KANDA, $kanda);

        // Hébergement
        $chambre1 = new Location();
        $chambre1->setName('Chambre 1');
        $chambre1->setTypeLocation('Hébergement');
        $manager->persist($chambre1);
        $this->addReference(self::ROOM1, $chambre1);

        $chambre2 = new Location();
        $chambre2->setName('Chambre 2');
        $chambre2->setTypeLocation('Hébergement');
        $manager->persist($chambre2);
        $this->addReference(self::ROOM2, $chambre2);

        $chambre3 = new Location();
        $chambre3->setName('Chambre 3');
        $chambre3->setTypeLocation('Hébergement');
        $manager->persist($chambre3);
        $this->addReference(self::ROOM3, $chambre3);

        $manager->flush();
    }
}
