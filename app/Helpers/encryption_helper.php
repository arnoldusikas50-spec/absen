<?php

/**
 * Helper utilities for generating URL-safe encrypted tokens.
 */
if (!function_exists('base64url_encode')) {
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('base64url_decode')) {
    function base64url_decode(string $data)
    {
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}

if (!function_exists('encrypt_id_token')) {
    /**
     * Encrypt numeric/string identifier for safe URL usage.
     */
    function encrypt_id_token($payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        try {
            $encrypter = service('encrypter');
            $ciphertext = $encrypter->encrypt((string) $payload);
            return base64url_encode($ciphertext);
        } catch (\Throwable $e) {
            log_message('error', 'encrypt_id_token failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('decrypt_id_token')) {
    /**
     * Decode encrypted identifier back to integer.
     */
    function decrypt_id_token(?string $token): ?int
    {
        if (!$token) {
            return null;
        }

        try {
            $binary = base64url_decode($token);
            if ($binary === false) {
                return null;
            }

            $encrypter = service('encrypter');
            $plain = $encrypter->decrypt($binary);

            if ($plain === false || !ctype_digit((string) $plain)) {
                return null;
            }

            return (int) $plain;
        } catch (\Throwable $e) {
            log_message('warning', 'decrypt_id_token failed: ' . $e->getMessage());
            return null;
        }
    }
}
