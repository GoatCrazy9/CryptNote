<?php
// get.php
// Endpoint to retrieve an encrypted note by its ID.

declare(strict_types=1);
require __DIR__.'/utils.php';

$DATA_DIR = __DIR__.'/data';

// Retrieve "id" parameter from query string
$id = $_GET['id'] ?? '';

// Validate ID format (must be at least 6 characters, alphanumeric + "-" or "_")
if (!preg_match('~^[A-Za-z0-9_-]{6,}$~', $id)) {
    json_out(['ok' => false, 'error' => 'Invalid ID'], 422);
}

$path = "$DATA_DIR/$id.json";

// Check if file exists for this ID
if (!is_file($path)) {
    json_out(['ok' => false, 'error' => 'Not found'], 404);
}

// Read record from JSON file
$record = json_decode(file_get_contents($path), true);
if (!$record) {
    json_out(['ok' => false, 'error' => 'Corrupted data'], 500);
}

// --- Expiration check ---
$expire = (int)($record['expire'] ?? 0);       // expiration time (seconds)
$createdAt = (int)($record['createdAt'] ?? 0); // creation timestamp (Unix)
if ($expire > 0 && time() > $createdAt + $expire) {
    // Optional: auto-delete expired notes
    @unlink($path);
    json_out(['ok' => false, 'error' => 'Expired'], 410);
}

// If valid and not expired, return encrypted data
json_out([
    'ok' => true,
    'iv' => $record['iv'],
    'ciphertext' => $record['ciphertext']
]);
