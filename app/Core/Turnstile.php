<?php

namespace App\Core;

/**
 * Cloudflare Turnstile (bot protection) helper.
 *
 * When enabled, forms render the widget (client) and the server verifies the
 * returned token against Cloudflare's siteverify API. If disabled (no keys),
 * verify() returns true so the app still works without configuration.
 *
 * Fails closed: a missing token or a network/verification error is treated as
 * a failed challenge.
 */
class Turnstile
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    private const API_JS = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

    private bool $enabled;
    private string $siteKey;
    private string $secretKey;

    public function __construct(array $config)
    {
        $this->siteKey = (string) ($config['site_key'] ?? '');
        $this->secretKey = (string) ($config['secret_key'] ?? '');
        $this->enabled = !empty($config['enabled']) && $this->siteKey !== '' && $this->secretKey !== '';
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function siteKey(): string
    {
        return $this->siteKey;
    }

    public function apiJs(): string
    {
        return self::API_JS;
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (!$this->enabled) {
            return true;
        }
        if (!is_string($token) || $token === '') {
            return false;
        }

        $payload = http_build_query(array_filter([
            'secret'   => $this->secretKey,
            'response' => $token,
            'remoteip' => $ip,
        ]));

        $result = $this->post(self::VERIFY_URL, $payload);
        return is_array($result) && !empty($result['success']);
    }

    private function post(string $url, string $body): ?array
    {
        $raw = null;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
            $raw = curl_exec($ch);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method'        => 'POST',
                    'header'        => 'Content-Type: application/x-www-form-urlencoded',
                    'content'       => $body,
                    'timeout'       => 5,
                    'ignore_errors' => true,
                ],
            ]);
            $raw = @file_get_contents($url, false, $context);
        }

        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
