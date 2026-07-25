<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CheckInOutController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/check/in/out', name: 'app_check_in_out', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getUser();
        $now = new \DateTimeImmutable();

        $nearestUserBooking = $this->userRepository->findNearestBooking($user, $now);

        if (!$nearestUserBooking) {
            $this->addFlash('warning', 'Aucune réservation trouvée.');

            return $this->render('check_in_out/index.html.twig', [
                'controller_name' => 'CheckInOutController',
                'booking' => null,
                'status' => 'no_booking',
            ]);
        }

        $startAt = $nearestUserBooking->getStartDate();
        $endAt = $nearestUserBooking->getEndDate();

        $checkInWindowStart = $startAt->modify('-15 minutes');
        $checkInWindowEnd = $startAt->modify('+15 minutes');

        $checkOutWindowStart = $endAt->modify('-15 minutes');
        $checkOutWindowEnd = $endAt->modify('+15 minutes');

        $status = 'outside_window';

        if (
            null === $nearestUserBooking->getCheckedInAt()
            && $now >= $checkInWindowStart
            && $now <= $checkInWindowEnd
        ) {
            $nearestUserBooking->setCheckedInAt($now);
            $status = 'checked_in';
            $this->addFlash('success', 'Check-in enregistré.');
        } elseif (
            null !== $nearestUserBooking->getCheckedInAt()
            && null === $nearestUserBooking->getCheckedOutAt()
            && $now >= $checkOutWindowStart
            && $now <= $checkOutWindowEnd
        ) {
            $nearestUserBooking->setCheckedOutAt($now);
            $status = 'checked_out';
            $this->addFlash('success', 'Check-out enregistré.');
        } else {
            $this->addFlash('warning', 'Vous n’êtes pas dans une plage autorisée de check-in ou check-out.');
        }

        if (\in_array($status, ['checked_in', 'checked_out'], true)) {
            $this->entityManager->flush();
        }

        return $this->render('check_in_out/index.html.twig', [
            'controller_name' => 'CheckInOutController',
            'booking' => $nearestUserBooking,
            'status' => $status,
        ]);
    }
}
