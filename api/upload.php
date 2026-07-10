<?php
require_once dirname(__DIR__) . '/includes/functions.php';

header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ps_json_response(['ok' => false, 'error' => 'POST requerido'], 405);
}

try {
    if (empty($_FILES['image'])) {
        ps_json_response(['ok' => false, 'error' => 'Campo image requerido'], 400);
    }
    $url = ps_save_uploaded_image($_FILES['image']);
    ps_json_response(['ok' => true, 'image_url' => $url]);
} catch (Throwable $e) {
    ps_json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
