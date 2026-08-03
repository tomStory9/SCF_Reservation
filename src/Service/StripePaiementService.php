<?php

namespace App\Service;

use Stripe\StripeClient;

class StripePaiementService
{
    private StripeClient $stripeClient;
    private string $defaultUri;

    public function __construct()
    {
        $this->stripeClient = new StripeClient($_ENV['STRIPE_SECRET_KEY']);
        $this->defaultUri = $_ENV['DEFAULT_URI'];
    }

    public function createPaymentLink(
        int $price,
        string $currency = 'jpy',
        string $name = 'Reservation',
        string $description = 'Booking entrainement'
    ): string {
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
