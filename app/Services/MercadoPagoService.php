<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoPagoService
{
public function createPreapproval(Subscription $subscription): array
{
    $plan = $this->plan($subscription->plan);

    $response = $this->client()->post('/preapproval', [
        'reason' => $plan['label'],
        'external_reference' => $subscription->external_reference,
        'payer_email' => 'test_user_1368433647093373060@testuser.com',
        'back_url' => config('services.mercado_pago.back_url'),
        'notification_url' => config('services.mercado_pago.webhook_url'),
        'auto_recurring' => [
            'frequency' => $plan['frequency'],
            'frequency_type' => $plan['frequency_type'],
            'transaction_amount' => (float) $plan['amount'],
            'currency_id' => $plan['currency'],
        ],
    ]);


    return $response->json();
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
