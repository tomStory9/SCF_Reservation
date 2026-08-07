<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Transaction;
use App\Entity\User;
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

    public function sendApprovedEmail(User $user): void
    {
        $email = new TemplatedEmail()
           ->from($this->mailerAddress)
           ->to($user->getEmail())
           ->subject('アカウント作成が承認されました')
           ->htmlTemplate('security/mails/account_creation_accepted.html.twig')
           ->context(
               [
                   'user' => $user]
           );
        $this->mailerInterface->send($email);
    }

    public function sendDeniedEmail(User $user): void
    {
        $email = new TemplatedEmail()
           ->from($this->mailerAddress)
           ->to($user->getEmail())
           ->subject('アカウント作成が拒否されました')
           ->htmlTemplate('security/mails/account_creation_denied.html.twig')
           ->context(
               [
                   'user' => $user]
           );
        $this->mailerInterface->send($email);
    }

    public function sendPaymentConfirmationEmail(User $user, Transaction $transaction): void
    {
        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($user->getEmail())
            ->subject('Payment Confirmation')
            ->htmlTemplate('mails/payment_confirmation.html.twig')
            ->context([
                'user' => $user,
                'transaction' => $transaction,
            ]);

        $this->mailerInterface->send($email);
    }

    public function sendBookingConfirmationEmail(User $user, Booking $booking): void
    {
        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($user->getEmail())
            ->subject('Booking Confirmation')
            ->htmlTemplate('mails/booking_confirmation.html.twig')
            ->context([
                'user' => $user,
                'booking' => $booking,
                'stripe_checkout_url' => $booking->getStripeCheckoutUrl(),
            ]);

        $this->mailerInterface->send($email);
    }

    public function sendBookingDeniedEmail(User $user, Booking $booking): void
    {
        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($user->getEmail())
            ->subject('Booking Cancellation')
            ->htmlTemplate('mails/booking_cancellation.html.twig')
            ->context([
                'user' => $user,
                'booking' => $booking,
            ]);

        $this->mailerInterface->send($email);
    }
}
