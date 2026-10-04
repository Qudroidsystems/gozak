<?php

namespace App\Services\Auth;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Verifies a "Sign in with Apple" identity token (a JWT signed by Apple).
 *
 * Checks: Apple's signature (public keys from appleid.apple.com), issuer,
 * audience (your iOS bundle id / services id), expiry and — when the app
 * sends one — the nonce.
 */
class AppleIdentityVerifier
{
    private const ISSUER   = 'https://appleid.apple.com';
    private const KEYS_URL = 'https://appleid.apple.com/auth/keys';

    /**
     * @return array{sub:string,email:?string,email_verified:bool,is_private_email:bool}
     */
    public function verify(string $identityToken, ?string $rawNonce = null): array
    {
        $claims = $this->decode($identityToken);

        if (($claims->iss ?? null) !== self::ISSUER) {
            throw new \RuntimeException('Apple token has the wrong issuer.');
        }

        $audiences = $this->audiences();
        $aud       = (array) ($claims->aud ?? []);
        if (!array_intersect($aud, $audiences)) {
            throw new \RuntimeException('Apple token was not issued for this app.');
        }

        if ($rawNonce !== null && $rawNonce !== '') {
            $expected = hash('sha256', $rawNonce);
            if (!hash_equals($expected, (string) ($claims->nonce ?? ''))) {
                throw new \RuntimeException('Apple token nonce mismatch.');
            }
        }

        if (empty($claims->sub)) {
            throw new \RuntimeException('Apple token has no user id.');
        }

        return [
            'sub'              => (string) $claims->sub,
            'email'            => isset($claims->email) ? strtolower((string) $claims->email) : null,
            'email_verified'   => filter_var($claims->email_verified ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_private_email' => filter_var($claims->is_private_email ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    private function decode(string $token): object
    {
        JWT::$leeway = 60;
        try {
            return JWT::decode($token, JWK::parseKeySet($this->keys(), 'RS256'));
        } catch (\UnexpectedValueException $e) {
            // Apple rotated its keys — refresh once and retry.
            Cache::forget('apple_auth_keys');
            return JWT::decode($token, JWK::parseKeySet($this->keys(), 'RS256'));
        }
    }

    private function keys(): array
    {
        return Cache::remember('apple_auth_keys', now()->addHours(12), function () {
            $res = Http::timeout(10)->get(self::KEYS_URL);
            if ($res->failed() || !is_array($res->json('keys'))) {
                throw new \RuntimeException('Could not reach Apple to verify sign-in.');
            }
            return $res->json();
        });
    }

    /** Bundle id (iOS app) and optional Services ID (web/Android), comma separated. */
    private function audiences(): array
    {
        $raw = (string) config('services.apple.client_id', 'ng.gozakmart.shop');
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
