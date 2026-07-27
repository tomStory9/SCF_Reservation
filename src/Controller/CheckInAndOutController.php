<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CheckInAndOutController extends AbstractController
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
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
            $status = 'no_booking';
            $statusMessage = $this->translator->trans(
                'status.no_booking.message',
                [],
                'checkInAndOut'
            );

            $statusLabel = $this->translator->trans(
                'status.no_booking.label',
                [],
                'checkInAndOut'
            );

            $statusClass = 'bg-slate-100 text-slate-700 border-slate-200';
            $dotClass = 'bg-slate-400';

            return $this->render('check_in_out/index.html.twig', [
                'controller_name' => 'CheckInOutController',
                'booking' => null,
                'status' => $status,
                'status_label' => $statusLabel,
                'status_message' => $statusMessage,
                'remaining_time_message' => null,
                'status_class' => $statusClass,
                'dot_class' => $dotClass,
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
        $statusMessage = $this->translator->trans(
            'status.outside_window.message',
            [],
            'checkInAndOut'
        );
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
            $statusMessage = $this->translator->trans(
                'status.checked_in.message',
                [],
                'checkInAndOut'
            );
        } elseif ($canCheckOut) {
            $checkedOutAt = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $tokyoNow->format('Y-m-d H:i:s'),
                $utcTimezone
            );

            $nearestUserBooking->setCheckedOutAt($checkedOutAt);
            $this->entityManager->flush();

            $status = 'checked_out';
            $statusMessage = $this->translator->trans(
                'status.checked_out.message',
                [],
                'checkInAndOut'
            );
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

                $statusMessage = $this->translator->trans(
                    'status.checked_in_waiting_checkout.message_with_remaining_time',
                    ['%time%' => $remainingTimeMessage],
                    'checkInAndOut'
                );
            } else {
                $statusMessage = $this->translator->trans(
                    'status.checked_in_waiting_checkout.message',
                    [],
                    'checkInAndOut'
                );
            }
        } elseif (null !== $nearestUserBooking->getCheckedInAt() && null !== $nearestUserBooking->getCheckedOutAt()) {
            $status = 'already_done';
            $statusMessage = $this->translator->trans(
                'status.already_done.message',
                [],
                'checkInAndOut'
            );
        } elseif (null === $nearestUserBooking->getCheckedInAt()) {
            $status = 'waiting_check_in';
            $statusMessage = $this->translator->trans(
                'status.waiting_check_in.message',
                [],
                'checkInAndOut'
            );
        }

        $statusLabel = match ($status) {
            'checked_in' => $this->translator->trans('status.checked_in.label', [], 'checkInAndOut'),
            'checked_out' => $this->translator->trans('status.checked_out.label', [], 'checkInAndOut'),
            'checked_in_waiting_checkout' => $this->translator->trans('status.checked_in_waiting_checkout.label', [], 'checkInAndOut'),
            'waiting_check_in' => $this->translator->trans('status.waiting_check_in.label', [], 'checkInAndOut'),
            'waiting_check_out' => $this->translator->trans('status.waiting_check_out.label', [], 'checkInAndOut'),
            'already_done' => $this->translator->trans('status.already_done.label', [], 'checkInAndOut'),
            'outside_window' => $this->translator->trans('status.outside_window.label', [], 'checkInAndOut'),
            'no_booking' => $this->translator->trans('status.no_booking.label', [], 'checkInAndOut'),
            default => $this->translator->trans('status.unknown.label', [], 'checkInAndOut'),
        };

        $statusClass = match ($status) {
            'checked_in' => 'bg-primary text-white border-primary',
            'checked_out' => 'bg-secondary text-white border-secondary',
            'checked_in_waiting_checkout' => 'bg-primary text-white border-primary',
            'already_done' => 'bg-secondary text-white border-secondary',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };

        $dotClass = in_array($status, ['checked_in', 'checked_out', 'checked_in_waiting_checkout', 'already_done'], true)
            ? 'bg-white'
            : 'bg-slate-400';

        return $this->render('check_in_out/index.html.twig', [
            'controller_name' => 'CheckInOutController',
            'booking' => $nearestUserBooking,
            'status' => $status,
            'status_label' => $statusLabel,
            'status_message' => $statusMessage,
            'remaining_time_message' => $remainingTimeMessage,
            'status_class' => $statusClass,
            'dot_class' => $dotClass,
        ]);
    }
}
