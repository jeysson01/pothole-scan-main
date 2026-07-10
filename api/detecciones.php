<?php
require_once dirname(__DIR__) . '/includes/functions.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$db = ps_db();
$input = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    if ($method === 'GET') {
        ps_json_response(['ok' => true, 'data' => ps_list_detecciones()]);
    }

    if ($method === 'POST') {
        $id = ps_uuid();
        $stmt = $db->prepare('INSERT INTO detecciones (id, via_id, image_url, annotated_url, severidad, confianza, cantidad_baches, analisis_ia, ubicacion, fecha_deteccion) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $id,
            ($input['via_id'] ?? '') ?: null,
            trim($input['image_url'] ?? ''),
            $input['annotated_url'] ?? null,
            $input['severidad'] ?? 'media',
            (float) ($input['confianza'] ?? 0),
            (int) ($input['cantidad_baches'] ?? 0),
            $input['analisis_ia'] ?? null,
            ($input['ubicacion'] ?? '') ?: null,
            $input['fecha_deteccion'] ?? date('Y-m-d H:i:s'),
        ]);
        ps_json_response(['ok' => true, 'id' => $id]);
    }

    if ($method === 'DELETE') {
        $id = $input['id'] ?? ($_GET['id'] ?? '');
        $db->prepare('DELETE FROM detecciones WHERE id = ?')->execute([$id]);
        ps_json_response(['ok' => true]);
    }

    ps_json_response(['ok' => false, 'error' => 'Método no permitido'], 405);
} catch (Throwable $e) {
    ps_json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
