<?php
// Common utility functions for CryptNote backend.

declare(strict_types=1);

/**
 * Encode binary data into URL-safe Base64 (Base64URL).
 *
 * Replaces "+" with "-", "/" with "_", and removes "=" padding.
 *
 * @param string $bin Raw binary string to encode.
 * @return string Base64URL-encoded string.
 */
function b64url_encode(string $bin): string {
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

/**
 * Decode a Base64URL string back to binary data.
 *
 * Restores "+" and "/", adds missing "=" padding, then decodes.
 *
 * @param string $b64url Base64URL-encoded string.
 * @return string Decoded binary string (empty string on failure).
 */
function b64url_decode(string $b64url): string {
    $b64 = strtr($b64url, '-_', '+/');
    $pad = strlen($b64) % 4;
    if ($pad) $b64 .= str_repeat('=', 4 - $pad);
    return base64_decode($b64, true) ?: '';
}

/**
 * Output a JSON response and stop execution.
 *
 * @param array $payload The data to return as JSON.
 * @param int $code HTTP status code (default 200).
 */
function json_out(array $payload, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Ensure a writable data directory exists.
 *
 * Creates the directory if missing, and sets proper permissions.
 *
 * @param string $dir Directory path.
 */
function ensure_data_dir(string $dir): void {
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    if (!is_writable($dir)) chmod($dir, 0775);
}
