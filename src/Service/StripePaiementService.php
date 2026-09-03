<?php

namespace App\Service;

use Stripe\Exception\ApiErrorException;
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

    /**
     * @throws ApiErrorException
     */
    public function createPaymentLink(
        int $price,
        int $userId,
        int $reservationId,
        string $currency = 'jpy',
        ?string $name = null,
        ?string $description = null,
        bool $isHold = true
    ): string {
        $name ??= $this->translator->trans('stripe.booking_name');
        $description ??= $this->translator->trans('stripe.booking_description');

        $sessionData = [
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
                'is_hold' => $isHold ? 'true' : 'false',
            ],
            'success_url' => $this->defaultUri.'/paiement/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->defaultUri.'/paiement/cancel',
        ];

        if ($isHold) {
            $sessionData['payment_intent_data'] = [
                'capture_method' => 'manual',
            ];
        }

        $session = $this->stripeClient->checkout->sessions->create($sessionData);

        return $session->url;
    }

    /**
     * @throws ApiErrorException
     */
    public function captureHoldAndGetFee(string $paymentIntentId): ?int
    {
        $pi = $this->stripeClient->paymentIntents->capture($paymentIntentId);

        if ($pi->latest_charge) {
            $charge = $this->stripeClient->charges->retrieve(
                is_string($pi->latest_charge) ? $pi->latest_charge : $pi->latest_charge->id,
                ['expand' => ['balance_transaction']]
            );

            return $charge->balance_transaction->fee ?? null;
        }

        return null;
    }

    /**
     * @throws ApiErrorException
     */
    public function releaseHold(string $paymentIntentId): \Stripe\PaymentIntent
    {
        return $this->stripeClient->paymentIntents->cancel($paymentIntentId);
    }
}
