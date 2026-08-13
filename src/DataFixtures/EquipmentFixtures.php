<?php

namespace App\DataFixtures;

use App\Entity\Equipment;
use App\Entity\Zone;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EquipmentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer toutes les zones via les références
        $zoneRefs = [
            ZoneFixtures::KUMA_CUBE,
            ZoneFixtures::KUMA_LAB,
            ZoneFixtures::KODA1A,
            ZoneFixtures::KODA1B,
            ZoneFixtures::KODA1C,
            ZoneFixtures::KODA1D,
            ZoneFixtures::KODA_AB,
            ZoneFixtures::KODA_CD,
            ZoneFixtures::KODA_FULL,
        ];

        // Créer des équipements de cirque par zone
        $equipmentByZone = [
            // KUMA CUBE - Espace cubique pour acrobaties
            ZoneFixtures::KUMA_CUBE => [
                ['name' => 'Tapis de réception acrobatique', 'price' => 8000, 'maxQty' => 6],
                ['name' => 'Cube mousse de protection', 'price' => 3500, 'maxQty' => 12],
                ['name' => 'Mât d\'escalade', 'price' => 15000, 'maxQty' => 2],
            ],

            // KUMA LAB - Salle de formation/entraînement
            ZoneFixtures::KUMA_LAB => [
                ['name' => 'Tissu aérien', 'price' => 12000, 'maxQty' => 4],
                ['name' => 'Trapèze fixe', 'price' => 18000, 'maxQty' => 2],
                ['name' => 'Corde lisse', 'price' => 8500, 'maxQty' => 3],
                ['name' => 'Anneaux de gymnastique', 'price' => 4500, 'maxQty' => 6],
            ],

            // KODA zones A-D - Zones modulaires
            ZoneFixtures::KODA1A => [
                ['name' => 'Cerceau aérien (Hoop)', 'price' => 9500, 'maxQty' => 3],
                ['name' => 'Sangle de suspension', 'price' => 1800, 'maxQty' => 8],
            ],
            ZoneFixtures::KODA1B => [
                ['name' => 'Cerceau aérien (Hoop)', 'price' => 9500, 'maxQty' => 3],
                ['name' => 'Sangle de suspension', 'price' => 1800, 'maxQty' => 8],
            ],
            ZoneFixtures::KODA1C => [
                ['name' => 'Cerceau aérien (Hoop)', 'price' => 9500, 'maxQty' => 3],
                ['name' => 'Sangle de suspension', 'price' => 1800, 'maxQty' => 8],
            ],
            ZoneFixtures::KODA1D => [
                ['name' => 'Cerceau aérien (Hoop)', 'price' => 9500, 'maxQty' => 3],
                ['name' => 'Sangle de suspension', 'price' => 1800, 'maxQty' => 8],
            ],

            // KODA A+B - Demi-espace
            ZoneFixtures::KODA_AB => [
                ['name' => 'Filet de sécurité aérien', 'price' => 25000, 'maxQty' => 2],
                ['name' => 'Mini-trampoline', 'price' => 7500, 'maxQty' => 4],
            ],

            // KODA C+D - Demi-espace
            ZoneFixtures::KODA_CD => [
                ['name' => 'Filet de sécurité aérien', 'price' => 25000, 'maxQty' => 2],
                ['name' => 'Planche d\'équilibre', 'price' => 3200, 'maxQty' => 6],
            ],

            // KODA FULL - Espace complet
            ZoneFixtures::KODA_FULL => [
                ['name' => 'Portique trapèze complet', 'price' => 45000, 'maxQty' => 1],
                ['name' => 'Barre russe', 'price' => 22000, 'maxQty' => 2],
                ['name' => 'Piste de réception gonflable', 'price' => 35000, 'maxQty' => 1],
                ['name' => 'Diabolo professionnel', 'price' => 1200, 'maxQty' => 15],
            ],
        ];

        foreach ($zoneRefs as $zoneRef) {
            $zone = $this->getReference($zoneRef, Zone::class);

            if (isset($equipmentByZone[$zoneRef])) {
                foreach ($equipmentByZone[$zoneRef] as $equipmentData) {
                    $equipment = new Equipment();
                    $equipment->setName($equipmentData['name']);
                    $equipment->setUnitPrice($equipmentData['price']);
                    $equipment->setMaxQuantity($equipmentData['maxQty']);
                    $equipment->setZone($zone);

                    $manager->persist($equipment);
                }
            }
        }

        // Créer 5 équipements sans zone associée (équipements globaux de cirque)
        $globalEquipment = [
            ['name' => 'Jonglerie - Set de 3 balles', 'price' => 800, 'maxQty' => 30],
            ['name' => 'Jonglerie - Set de 3 massues', 'price' => 1500, 'maxQty' => 20],
            ['name' => 'Diabolo avec baguettes', 'price' => 1200, 'maxQty' => 25],
            ['name' => 'Rouleau américain (Rola Rola)', 'price' => 4500, 'maxQty' => 8],
            ['name' => 'Pieds de jonglage', 'price' => 2800, 'maxQty' => 10],
        ];

        foreach ($globalEquipment as $index => $data) {
            $equipment = new Equipment();
            $equipment->setName($data['name']);
            $equipment->setUnitPrice($data['price']);
            $equipment->setMaxQuantity($data['maxQty']);
            $equipment->setZone(null); // Pas de zone associée - disponible partout

            $manager->persist($equipment);
            $this->addReference('equipment-global-'.$index, $equipment);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ZoneFixtures::class,
        ];
    }
}
