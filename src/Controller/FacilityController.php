<?php

namespace App\Controller;

use App\Entity\Facility;
use App\Repository\ZoneRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class FacilityController extends AbstractController
{
    public function __construct(
        private readonly ZoneRepository $zoneRepository,
    ) {
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/facility/{id}/zones', name: 'app_booking_training_zone', methods: ['GET'])]
    public function getTrainingZoneByFacility(Facility $facility): JsonResponse
    {
        $zones = $this->zoneRepository->getTrainingZonesByFacility($facility);

        $zonesJson = [];
        foreach ($zones as $zone) {
            $zonesJson[] = [
                'id' => $zone->getId(),
                'name' => $zone->getName(),
                'code' => $zone->getCode(),
            ];
        }

        return new JsonResponse($zonesJson);
    }
}
