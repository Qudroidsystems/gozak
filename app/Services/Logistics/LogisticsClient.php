<?php

namespace App\Services\Logistics;

use App\Models\Logistics\LogisticsSetting;
use Illuminate\Support\Facades\Http;

/**
 * Thin client for the logistics marketplace partner API (/api/v1/partner).
 * Amounts on the wire are kobo; this class leaves them as they come — LogisticsService converts.
 */
class LogisticsClient
{
    public function __construct(private ?LogisticsSetting $settings = null)
    {
        $this->settings ??= LogisticsSetting::current();
    }

    public function me(): array
    {
        return $this->call('get', '/me');
    }

    public function catalog(): array
    {
        return $this->call('get', '/catalog');
    }

    public function options(array $body): array
    {
        return $this->call('post', '/quotes/options', $body);
    }

    public function createDelivery(array $body): array
    {
        return $this->call('post', '/deliveries', $body);
    }

    public function delivery(string $externalOrderId): array
    {
        return $this->call('get', '/deliveries/' . rawurlencode($externalOrderId));
    }

    public function cancel(string $externalOrderId, ?string $reason = null): array
    {
        return $this->call('post', '/deliveries/' . rawurlencode($externalOrderId) . '/cancel', array_filter(['reason' => $reason]));
    }

    private function call(string $method, string $path, array $body = []): array
    {
        $s = $this->settings;
        if (!$s->base_url || !$s->api_key) {
            throw new LogisticsException('The logistics marketplace is not connected (base URL / API key missing).');
        }
        $url = rtrim($s->base_url, '/') . $path;
        try {
            $res = Http::timeout(15)->acceptJson()->withToken($s->api_key)->{$method}($url, $body);
        } catch (\Throwable $e) {
            throw new LogisticsException('Could not reach the logistics marketplace: ' . $e->getMessage());
        }
        $json = $res->json() ?? [];
        if ($res->failed()) {
            $msg = $json['message'] ?? ($json['error'] ?? ('HTTP ' . $res->status()));
            if ($res->status() === 401) {
                $msg = 'The API key was refused (revoked or wrong).';
            }
            throw new LogisticsException(is_string($msg) ? $msg : json_encode($msg));
        }
        return is_array($json) ? $json : [];
    }
}
