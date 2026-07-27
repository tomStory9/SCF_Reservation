<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CheckInAndOutController extends AbstractController
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/check/in/out', name: 'app_check_in_out')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getUser();

        $tokyoTimezone = new \DateTimeZone('Asia/Tokyo');
        $utcTimezone = new \DateTimeZone('UTC');

        $tokyoNow = new \DateTimeImmutable('now', $tokyoTimezone);

        $nearestUserBooking = $this->bookingRepository->findNearestBooking($user, $tokyoNow);

        if (!$nearestUserBooking) {
            return $this->render('check_in_out/index.html.twig', [
                'controller_name' => 'CheckInOutController',
                'booking' => null,
                'status' => 'no_booking',
                'status_message' => 'Aucune réservation trouvée.',
            ]);
        }

        $now = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $tokyoNow->format('Y-m-d H:i:s'),
            $utcTimezone
        );

        $startAt = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $nearestUserBooking->getStartDate()->format('Y-m-d H:i:s'),
            $utcTimezone
        );

        $endAt = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $nearestUserBooking->getEndDate()->format('Y-m-d H:i:s'),
            $utcTimezone
        );

        $checkInWindowStart = $startAt->modify('-15 minutes');
        $checkInWindowEnd = $startAt->modify('+15 minutes');

        $checkOutWindowStart = $endAt->modify('-15 minutes');
        $checkOutWindowEnd = $endAt->modify('+15 minutes');

        $canCheckIn =
            null === $nearestUserBooking->getCheckedInAt()
            && $now >= $checkInWindowStart
            && $now <= $checkInWindowEnd;

        $canCheckOut =
            null !== $nearestUserBooking->getCheckedInAt()
            && null === $nearestUserBooking->getCheckedOutAt()
            && $now >= $checkOutWindowStart
            && $now <= $checkOutWindowEnd;

        $status = 'outside_window';
        $statusMessage = 'Vous n’êtes pas dans une plage autorisée de check-in ou check-out.';
        $remainingTimeMessage = null;

        if ($canCheckIn) {
            $checkedInAt = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $tokyoNow->format('Y-m-d H:i:s'),
                $utcTimezone
            );

            $nearestUserBooking->setCheckedInAt($checkedInAt);
            $this->entityManager->flush();

            $status = 'checked_in';
            $statusMessage = 'Check-in enregistré avec succès.';
        } elseif ($canCheckOut) {
            $checkedOutAt = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $tokyoNow->format('Y-m-d H:i:s'),
                $utcTimezone
            );

            $nearestUserBooking->setCheckedOutAt($checkedOutAt);
            $this->entityManager->flush();

            $status = 'checked_out';
            $statusMessage = 'Check-out enregistré avec succès.';
        } elseif (null !== $nearestUserBooking->getCheckedInAt() && null === $nearestUserBooking->getCheckedOutAt()) {
            $status = 'checked_in_waiting_checkout';

            if ($now < $checkOutWindowStart) {
                $diff = $now->diff($checkOutWindowStart);

                $hours = ($diff->days * 24) + $diff->h;
                $minutes = $diff->i;

                if ($hours > 0) {
                    $remainingTimeMessage = sprintf('%dh %02dmin', $hours, $minutes);
                } else {
                    $remainingTimeMessage = sprintf('%dmin', $minutes);
                }

                $statusMessage = sprintf(
                    'Votre check-in est déjà enregistré. Vous devez encore attendre %s avant de pouvoir effectuer le check-out.',
                    $remainingTimeMessage
                );
            } else {
                $statusMessage = 'Votre check-in est déjà enregistré. Vous pouvez bientôt effectuer votre check-out.';
            }
        } elseif (null !== $nearestUserBooking->getCheckedInAt() && null !== $nearestUserBooking->getCheckedOutAt()) {
            $status = 'already_done';
            $statusMessage = 'Le check-in et le check-out sont déjà enregistrés.';
        } elseif (null === $nearestUserBooking->getCheckedInAt()) {
            $status = 'waiting_check_in';
            $statusMessage = 'Vous n’êtes pas encore dans la plage autorisée pour le check-in.';
        }

        return $this->render('check_in_out/index.html.twig', [
            'controller_name' => 'CheckInOutController',
            'booking' => $nearestUserBooking,
            'status' => $status,
            'status_message' => $statusMessage,
            'remaining_time_message' => $remainingTimeMessage,
        ]);
    }
}
