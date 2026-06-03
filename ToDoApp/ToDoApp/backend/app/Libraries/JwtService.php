<?php

namespace App\Libraries;

use Config\Api;

class JwtService
{
    private Api $config;

    public function __construct()
    {
        $this->config = config(Api::class);
    }

    public function encode(array $claims): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = array_merge([
            'iat' => time(),
            'exp' => time() + $this->config->jwtTtl,
        ], $claims);

        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];

        $segments[] = $this->signature($segments[0] . '.' . $segments[1]);

        return implode('.', $segments);
    }

    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;
        if (! hash_equals($this->signature($header . '.' . $payload), $signature)) {
            return null;
        }

        $claims = json_decode($this->base64UrlDecode($payload), true);
        if (! is_array($claims) || ! isset($claims['exp']) || (int) $claims['exp'] < time()) {
            return null;
        }

        return $claims;
    }

    private function signature(string $data): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $data, $this->config->jwtSecret, true));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
