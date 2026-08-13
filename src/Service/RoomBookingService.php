<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Repository\PricingRepository;
use App\Repository\UserRoleRepository;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class RoomBookingService
{
    public function __construct(
        private ZoneRepository $zoneRepository,
        private PricingRepository $pricingRepository,
        private UserRoleRepository $userRoleRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
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

    /**
     * @throws \DateMalformedStringException
     * @throws \Exception
     */
    public function createRoomBooking(array $data, User $user): void
    {
        $zone = $this->zoneRepository->find($data['roomId']);

        if (!$zone) {
            throw new \Exception('Chambre introuvable.');
        }

        $userRole = $this->userRoleRepository->findRoleForUser($user);

        $maxAdvanceDays = $userRole && null !== $userRole->getMaxAdvanceBookingDays()
            ? $userRole->getMaxAdvanceBookingDays()
            : 30;

        $limitDate = new \DateTimeImmutable('today')
            ->modify(sprintf('+%d days', $maxAdvanceDays))
            ->setTime(23, 59, 59);

        $start = new \DateTimeImmutable($data['startDate'])->setTime(0, 0, 0);
        $end = new \DateTimeImmutable($data['endDate']);

        if ($start > $limitDate) {
            throw new \Exception(sprintf('Votre statut vous permet de réserver au maximum %d jours à l\'avance (jusqu\'au %s).', $maxAdvanceDays, $limitDate->format('d/m/Y')));
        }

        $nights = (int) $data['nights'];
        $expectedPrice = 0;

        $pricings = $this->pricingRepository->getPricingByBedRoom($zone);

        $pricingMap = [];
        foreach ($pricings as $p) {
            $pricingMap[$p['dayNumber']] = $p['fullPrice'];
        }

        for ($i = 0; $i < $nights; ++$i) {
            $currentDate = $start->modify("+$i days");
            $dayNumber = (int) $currentDate->format('N');

            $expectedPrice += $pricingMap[$dayNumber] ?? 0;
        }

        $receivedPrice = (int) $data['price'];

        if ($receivedPrice !== $expectedPrice) {
            throw new \Exception(sprintf('Le tarif calculé (%d ¥) ne correspond pas au tarif affiché (%d ¥). Veuillez rafraîchir la page et recommencer.', $expectedPrice, $receivedPrice));
        }

        $booking = new Booking();
        $booking->setUserBooking($user);
        $booking->setZone($zone);
        $booking->setPrice($expectedPrice);
        $booking->setTotalPrice($expectedPrice);
        $booking->setEquipmentPrice(0);
        $booking->setGuestCount(1);
        $booking->setIsFullDay(true);
        $booking->setBookingStatus(BookingStatus::PENDING);
        $booking->setCreatedDate(new \DateTimeImmutable());

        $booking->setStartDate($start);
        $booking->setEndDate($end);

        $errors = $this->validator->validate($booking);

        if (count($errors) > 0) {
            throw new \Exception($errors[0]->getMessage());
        }

        $this->entityManager->persist($booking);
        $this->entityManager->flush();
    }
}
