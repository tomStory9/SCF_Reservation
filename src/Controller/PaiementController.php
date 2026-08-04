<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Repository\BookingRepository;
use App\Service\StripePaiementService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class PaiementController extends AbstractController
{
    #[Route('/paiement/success', name: 'app_paiement_success')]
    public function success(
        #[MapQueryParameter]
        string $session_id,
        #[Autowire(env: 'STRIPE_SECRET_KEY')]
        string $stripeSecretKey,
        EntityManagerInterface $entityManager,
        BookingRepository $bookingRepository
    ): Response {
        $stripe = new StripeClient($stripeSecretKey);
        $session = $stripe->checkout->sessions->retrieve(
            $session_id,
            [
                'expand' => [
                    'payment_intent.latest_charge.balance_transaction',
                ],
            ]
        );

        $transaction = new Transaction();
        $transaction->setPaidPrice($session->amount_total);
        $transaction->setStripeFee($session->payment_intent->latest_charge->balance_transaction->fee);
        $transaction->setTimestamp(new \DateTime());
        $transaction->setBooking($bookingRepository->findById($session->metadata->booking_id));

        $entityManager->persist($transaction);
        $entityManager->flush();

        return $this->render('paiement/success.html.twig', [
            'controller_name' => 'PaiementController',
        ]);
    }

    #[Route('/paiement/cancel', name: 'app_paiement_cancel')]
    public function cancel(): Response
    {
        return $this->render('paiement/cancel.html.twig', [
            'controller_name' => 'PaiementController',
        ]);
    }

    #[Route('/paiement/test', name: 'app_paiement_test')]
    public function test(StripePaiementService $test): Response
    {
        dd($test->createPaymentLink(2000, 3, 9));
    }
}
