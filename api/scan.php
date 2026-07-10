<?php
require_once dirname(__DIR__) . '/includes/functions.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ps_json_response(['ok' => false, 'error' => 'POST requerido'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$imageUrl = trim($input['image_url'] ?? '');

if ($imageUrl === '') {
    ps_json_response(['ok' => false, 'error' => 'image_url requerido'], 400);
}

try {
    $result = ps_scan_image($imageUrl);
    ps_json_response(['ok' => true, 'data' => $result]);
} catch (Throwable $e) {
    ps_json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
