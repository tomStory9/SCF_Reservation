<?php

namespace App\Service;

use Stripe\StripeClient;
use Symfony\Contracts\Translation\TranslatorInterface;

class StripePaiementService
{
    private StripeClient $stripeClient;
    private string $defaultUri;

    public function __construct(private readonly TranslatorInterface $translator)
    {
        $this->stripeClient = new StripeClient($_ENV['STRIPE_SECRET_KEY']);
        $this->defaultUri = $_ENV['DEFAULT_URI'];
    }

    public function createPaymentLink(
        int $price,
        int $userId,
        int $reservationId,
        string $currency = 'jpy',
        ?string $name = null,
        ?string $description = null,
    ): string {
        $name ??= $this->translator->trans('stripe.booking_name');
        $description ??= $this->translator->trans('stripe.booking_description');

        $session = $this->stripeClient->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $price,
                    'product_data' => [
                        'name' => $name,
                        'description' => $description,
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'booking_id' => $reservationId,
                'user_id' => $userId,
            ],
            'success_url' => $this->defaultUri.'/paiement/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->defaultUri.'/paiement/cancel',
        ]);

        return $session->url;
    }

    private function createSubscriptionInvoice()
    {
        throw new \Exception('Not implemented yet');
    }
}
