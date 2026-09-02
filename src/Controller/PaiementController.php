<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PaiementController extends AbstractController
{
    #[Route('/paiement/success', name: 'app_paiement_success')]
    public function success(
        #[MapQueryParameter]
        string $session_id,
        #[Autowire(env: 'STRIPE_SECRET_KEY')]
        string $stripeSecretKey,
        EntityManagerInterface $entityManager,
        BookingRepository $bookingRepository,
        UserRepository $userRepository,
        MailerService $mailerService,
        TranslatorInterface $translator
    ): Response {
        $stripe = new StripeClient($stripeSecretKey);

        $session = $stripe->checkout->sessions->retrieve($session_id, [
            'expand' => ['payment_intent.latest_charge.balance_transaction'],
        ]);

        $booking = $bookingRepository->findOneById($session->metadata->booking_id);
        $user = $userRepository->findOneById($session->metadata->user_id);
        $isHold = ($session->metadata->is_hold ?? 'true') === 'true';

        if ($isHold) {
            $transaction = new Transaction();
            $transaction->setPaidPrice($session->amount_total);
            $transaction->setStripeFee(null);
            $transaction->setStripePaymentIntentId(is_string($session->payment_intent) ? $session->payment_intent : $session->payment_intent->id);
            $transaction->setTimestamp(new \DateTime());
            $transaction->setBooking($booking);

            $entityManager->persist($transaction);
            $entityManager->flush();

            $mailerService->sendBookingPending($booking, $user);

            $this->addFlash('success', 'Votre empreinte bancaire a bien été enregistrée. Votre demande est en attente de validation.');
        } else {
            $transaction = $booking->getTransaction() ?? new Transaction();
            $transaction->setPaidPrice($session->amount_total);
            $transaction->setTimestamp(new \DateTime());
            $transaction->setBooking($booking);

            if ($session->payment_intent && !is_string($session->payment_intent) && $session->payment_intent->latest_charge && !is_string($session->payment_intent->latest_charge) && $session->payment_intent->latest_charge->balance_transaction) {
                $transaction->setStripeFee($session->payment_intent->latest_charge->balance_transaction->fee);
            }

            $booking->setBookingStatus(BookingStatus::PAID);
            $booking->setStripeCheckoutUrl(null);

            $entityManager->persist($transaction);
            $entityManager->flush();

            $mailerService->sendPaymentConfirmationEmail($user, $transaction);

            $this->addFlash('success', 'Votre paiement a été validé avec succès.');
        }

        return $this->redirectToRoute('app_home_user');
    }

    #[Route('/paiement/cancel', name: 'app_paiement_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Le processus de paiement a été annulé.');

        return $this->redirectToRoute('app_home_user');
    }
}
