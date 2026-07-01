<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoPagoService
{
    public function createPaymentPreference(Subscription $subscription): array
    {
        $plan = $this->plan($subscription->plan);

        $response = $this->client()->post('/checkout/preferences', [
            'items' => [
                [
                    'title' => $plan['label'] . ' - Dead Simple Inventory',
                    'quantity' => 1,
                    'unit_price' => (float) $plan['amount'],
                    'currency_id' => $plan['currency'] ?? 'ARS',
                ]
            ],
            'external_reference' => $subscription->external_reference,
            'payer' => [
                'email' => config('services.mercado_pago.payer_email_override') ?? $subscription->user->email,
            ],
            'back_urls' => [
                'success' => config('services.mercado_pago.back_url'),
                'pending' => config('services.mercado_pago.back_url'),
                'failure' => config('services.mercado_pago.back_url'),
            ],
            'notification_url' => config('services.mercado_pago.webhook_url'),
            'auto_return' => 'approved',
        ]);

        return $response->json();
    }

    public function getPayment(string $paymentId): array
    {
        $response = $this->client()->get("/v1/payments/{$paymentId}");

        return $response->json();
    }

    public function searchPayments(string $externalReference): array
    {
        $response = $this->client()->get('/v1/payments/search', [
            'external_reference' => $externalReference,
            'sort' => 'date_created',
            'criteria' => 'desc',
        ]);

        return $response->json('results', []);
    }


    public function plan(string $plan): array
    {
        $plans = config('services.mercado_pago.plans', []);

        if (! isset($plans[$plan])) {
            throw new RuntimeException("Unsupported subscription plan [{$plan}].");
        }

        if ((float) $plans[$plan]['amount'] <= 0) {
            throw new RuntimeException("Subscription plan [{$plan}] must have an amount greater than zero.");
        }

        return $plans[$plan];
    }

    private function client(): PendingRequest
    {
        $accessToken = config('services.mercado_pago.access_token');

        if (! $accessToken) {
            throw new RuntimeException('Mercado Pago access token is not configured.');
        }

        return Http::baseUrl(config('services.mercado_pago.base_url'))
            ->acceptJson()
            ->asJson()
            ->withToken($accessToken);
    }
}
