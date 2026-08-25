<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\SettingsRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MailerService
{
    public function __construct(
        private MailerInterface $mailerInterface,
        #[Autowire(env: 'MAILER_ADDRESS')]
        private string $mailerAddress,
        #[Autowire(env: 'ADMIN_ADDRESS')]
        private string $adminAddress,
        private SettingsRepository $settingsRepository,
        private TranslatorInterface $translator,
    ) {
    }

    public function sendReminderEmail(Booking $booking, string $reminderKey): void
    {
        $locale = $this->getUserLocale($booking->getUserBooking());

        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($booking->getUserBooking()->getEmail())
            ->subject($this->translate('subject.booking_reminder', $locale))
            ->htmlTemplate('mails/booking_reminder.html.twig')
            ->locale($locale)
            ->context([
                'booking' => $booking,
                'user' => $booking->getUserBooking(),
                'reminderLabel' => $this->translate('reminder.'.$reminderKey, $locale),
                'timezone' => 'Asia/Tokyo',
                'emailLocale' => $locale,
            ]);

        $this->mailerInterface->send($email);
    }

    public function sendApprovedEmail(User $user): void
    {
        $locale = $this->getUserLocale($user);

        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($user->getEmail())
            ->locale($locale)
            ->subject($this->translate('subject.account_approved', $locale))
            ->htmlTemplate('security/mails/account_creation_accepted.html.twig')
            ->context(['user' => $user, 'emailLocale' => $locale]);
        $this->mailerInterface->send($email);
    }

    public function sendDeniedEmail(User $user): void
    {
        $locale = $this->getUserLocale($user);

        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($user->getEmail())
            ->subject($this->translate('subject.account_denied', $locale))
            ->locale($locale)
            ->htmlTemplate('security/mails/account_creation_denied.html.twig')
            ->context(['user' => $user, 'emailLocale' => $locale]);
        $this->mailerInterface->send($email);
    }

    public function sendPaymentConfirmationEmail(User $user, Transaction $transaction): void
    {
        $locale = $this->getUserLocale($user);

        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($user->getEmail())
            ->subject($this->translate('subject.payment_confirmation', $locale))
            ->locale($locale)
            ->htmlTemplate('mails/payment_confirmation.html.twig')
            ->context([
                'user' => $user,
                'transaction' => $transaction,
                'emailLocale' => $locale,
            ]);

        $this->mailerInterface->send($email);
    }

    public function sendBookingPending(Booking $booking, User $user): void
    {
        $locale = $this->getUserLocale($user);

        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($user->getEmail())
            ->subject($this->translate('subject.booking_pending', $locale))
            ->locale($locale)
            ->htmlTemplate('mails/booking_pending.html.twig')
            ->context([
                'user' => $user,
                'booking' => $booking,
                'emailLocale' => $locale,
            ]);

        $this->mailerInterface->send($email);
    }

    public function sendBookingConfirmationEmail(User $user, Booking $booking): void
    {
        $locale = $this->getUserLocale($user);

        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($user->getEmail())
            ->subject($this->translate('subject.booking_confirmation', $locale))
            ->locale($locale)
            ->htmlTemplate('mails/booking_confirmation.html.twig')
            ->context([
                'user' => $user,
                'booking' => $booking,
                'stripe_checkout_url' => $booking->getStripeCheckoutUrl(),
                'emailLocale' => $locale,
            ]);

        $this->mailerInterface->send($email);
    }

    public function sendBookingDeniedEmail(User $user, Booking $booking): void
    {
        $locale = $this->getUserLocale($user);

        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($user->getEmail())
            ->subject($this->translate('subject.booking_rejected', $locale))
            ->locale($locale)
            ->htmlTemplate('mails/booking_rejected.html.twig')
            ->context([
                'user' => $user,
                'booking' => $booking,
                'emailLocale' => $locale,
            ]);

        $this->mailerInterface->send($email);
    }

    public function sendNewUserAdmin(User $user): void
    {
        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($this->adminAddress)
            ->subject($this->translate('subject.admin_new_user', 'ja'))
            ->locale('ja')
            ->htmlTemplate('mails/new_user_created.html.twig')
            ->context([
                'user' => $user,
                'emailLocale' => 'ja',
            ]);

        $this->mailerInterface->send($email);
    }

    public function sendNewBookingAdmin(Booking $booking, User $user): void
    {
        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($this->adminAddress)
            ->subject($this->translate('subject.admin_new_booking', 'ja'))
            ->locale('ja')
            ->htmlTemplate('mails/new_booking_created.html.twig')
            ->context([
                'user' => $user,
                'booking' => $booking,
                'emailLocale' => 'ja',
            ]);

        $this->mailerInterface->send($email);
    }

    public function sendRoomBookingConfirmationEmail(Booking $booking, User $user): void
    {
        $locale = $this->getUserLocale($user);
        $checkInHour = $this->settingsRepository->getSettings()->getHourCheckInRoom();
        $checkOutHour = $this->settingsRepository->getSettings()->getHourCheckOut();
        $email = new TemplatedEmail()
            ->from($this->mailerAddress)
            ->to($user->getEmail())
            ->subject($this->translate('subject.room_booking_pending', $locale))
            ->locale($locale)
            ->htmlTemplate('mails/new_room_booking.html.twig')
            ->context([
                'user' => $user,
                'booking' => $booking,
                'checkInHour' => $checkInHour,
                'checkOutHour' => $checkOutHour,
                'emailLocale' => $locale,
            ]);

        $this->mailerInterface->send($email);
    }

    private function getUserLocale(User $user): string
    {
        return $user->getLanguage();
    }

    private function translate(string $key, string $locale): string
    {
        return $this->translator->trans($key, domain: 'emails', locale: $locale);
    }
}
