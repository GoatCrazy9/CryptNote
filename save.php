<?php
/**
 * CryptNote - Store Encrypted Notes (API Endpoint)
 * 
 * This script receives encrypted data from the client (via POST JSON),
 * validates it, and stores it on the server in JSON format. 
 * The server **never sees the plaintext**, only the ciphertext.
 * 
 * Workflow:
 *  1. Client encrypts the note in the browser with AES-256.
 *  2. The encrypted data (ciphertext + IV) is sent here via JSON.
 *  3. The server stores the data securely in the /data directory.
 *  4. An auto-generated ID is returned to retrieve the note later.
 * 
 * License: GNU GPLv3
 * (c) 2025 GoatCrazy09
 */

declare(strict_types=1);
require __DIR__.'/utils.php';

// Path where encrypted notes are stored
$DATA_DIR = __DIR__.'/data';
ensure_data_dir($DATA_DIR);

// Read raw input JSON
$raw = file_get_contents('php://input');
$in = json_decode($raw, true);
if (!$in) json_out(['ok'=>false,'error'=>'Invalid JSON'], 400);

// Extract fields from request
$iv = $in['iv'] ?? '';
$ciphertext = $in['ciphertext'] ?? '';
$expire = (int)($in['expire'] ?? 0);
$createdAt = (int)($in['createdAt'] ?? time());

// Basic validations
if (!preg_match('~^[A-Za-z0-9_-]{16,}$~', $iv)) 
    json_out(['ok'=>false,'error'=>'Invalid IV'], 422);

if (!preg_match('~^[A-Za-z0-9_-]{24,}$~', $ciphertext)) 
    json_out(['ok'=>false,'error'=>'Invalid ciphertext'], 422);

if ($expire < 0) $expire = 0;

// Limit ciphertext size (~1MB encrypted data)
if (strlen($ciphertext) > 1400*1024) 
    json_out(['ok'=>false,'error'=>'Payload too large'], 413);

// Generate unique ID (11 base64url chars from 8 random bytes)
$id = b64url_encode(random_bytes(8));
$path = "$DATA_DIR/$id.json";

// Build record to store
$record = [
    'id' => $id,
    'iv' => $iv,
    'ciphertext' => $ciphertext,
    'createdAt' => $createdAt,
    'expire' => $expire, // in seconds; 0 = no expiration
];

// Save record in JSON file
file_put_contents($path, json_encode($record, JSON_UNESCAPED_SLASHES));
chmod($path, 0664);

// Respond with success
json_out(['ok'=>true,'id'=>$id]);
?>
