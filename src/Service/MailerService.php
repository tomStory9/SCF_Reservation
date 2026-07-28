<?php

namespace App\Service;

use App\Entity\Booking;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;

class MailerService
{
    public function __construct(
        private MailerInterface $mailerInterface,
        #[Autowire(env: 'MAILER_ADDRESS')]
        private string $mailerAddress,
    ) {
    }

    public function sendReminderEmail(Booking $booking, string $reminderLabel): void
    {
        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($booking->getUserBooking()->getEmail())
            ->subject('Booking reminder')
            ->htmlTemplate('mails/booking_reminder.html.twig')
            ->context([
                'booking' => $booking,
                'user' => $booking->getUserBooking(),
                'reminderLabel' => $reminderLabel,
                'timezone' => 'Asia/Tokyo',
            ]);

        $this->mailerInterface->send($email);
    }
}
