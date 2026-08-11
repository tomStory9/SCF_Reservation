<?php

namespace App\Service;

use App\Repository\PricingRepository;
use App\Repository\ZoneRepository;

readonly class RoomBookingService
{
    public function __construct(
        private readonly ZoneRepository $zoneRepository,
        private readonly PricingRepository $pricingRepository,
    ) {
    }

    public function getBedRoomYamaichiWithPricing(): array
    {
        $bedRoomsWithPricing = [];
        $bedRooms = $this->zoneRepository->getBedRoomYamaichi();
        foreach ($bedRooms as $bedRoom) {
            $pricings = $this->pricingRepository->getPricingByBedRoom($bedRoom);
            $bedRoomsWithPricing[] = [
                'id' => $bedRoom->getId(),
                'name' => $bedRoom->getName(),
                'pricing' => $pricings,
            ];
        }

        return $bedRoomsWithPricing;
    }
}
