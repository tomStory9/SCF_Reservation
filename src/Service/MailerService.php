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
        #[Autowire(env: 'ADMIN_ADRESS')]
        private string $adminAdress
    ) {
    }

    public function sendReminderEmail(Booking $booking, string $reminderLabel): void
    {
        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($booking->getUserBooking()->getEmail())
            ->subject('予約リマインダー')
            ->htmlTemplate('mails/booking_reminder.html.twig')
            ->locale($$booking->getUserBooking()->getLanguage())
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
            ->locale($user->getLanguage())
            ->subject('アカウントが承認されました')
            ->htmlTemplate('security/mails/account_creation_accepted.html.twig')
            ->context(['user' => $user]);
        $this->mailerInterface->send($email);
    }

    public function sendDeniedEmail(User $user): void
    {
        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($user->getEmail())
            ->subject('アカウント申請が承認されませんでした')
            ->locale($user->getLanguage())
            ->htmlTemplate('security/mails/account_creation_denied.html.twig')
            ->context(['user' => $user]);
        $this->mailerInterface->send($email);
    }

    public function sendPaymentConfirmationEmail(User $user, Transaction $transaction): void
    {
        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($user->getEmail())
            ->subject('お支払い確認')
            ->locale($user->getLanguage())
            ->htmlTemplate('mails/payment_confirmation.html.twig')
            ->context([
                'user' => $user,
                'transaction' => $transaction,
            ]);

        $this->mailerInterface->send($email);
    }

    public function sendBookingPending(Booking $booking, User $user): void
    {
        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($this->adminAdress)
            ->subject('Votre demande de reservation à bien été prise en compte')
            ->locale($user->getLanguage())
            ->htmlTemplate('mails/booking_pending.html.twig')
            ->context([
                'user' => $user,
                'booking' => $booking,
            ]);

        $this->mailerInterface->send($email);
    }

    public function sendBookingConfirmationEmail(User $user, Booking $booking): void
    {
        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($user->getEmail())
            ->subject('予約確認')
            ->locale($user->getLanguage())
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
            ->subject('予約に関するお知らせ')
            ->locale($user->getLanguage())
            ->htmlTemplate('mails/booking_rejected.html.twig')
            ->context([
                'user' => $user,
                'booking' => $booking,
            ]);

        $this->mailerInterface->send($email);
    }

    public function sendNewUserAdmin(User $user): void
    {
        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($this->adminAdress)
            ->subject('Un nouvel utilisateur a été crée')
            ->locale($user->getLanguage())
            ->htmlTemplate('mails/new_user_created.html.twig')
            ->context([
                'user' => $user,
            ]);

        $this->mailerInterface->send($email);
    }

    public function sendNewBookingAdmin(Booking $booking, User $user): void
    {
        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($this->adminAdress)
            ->subject('Une nouvelle demande de reservation à été crée')
            ->locale($user->getLanguage())
            ->htmlTemplate('mails/new_booking_created.html.twig')
            ->context([
                'user' => $user,
                'booking' => $booking,
            ]);

        $this->mailerInterface->send($email);
    }
}
