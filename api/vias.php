<?php
require_once dirname(__DIR__) . '/includes/functions.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$db = ps_db();
$input = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    if ($method === 'GET') {
        ps_json_response(['ok' => true, 'data' => ps_list_vias()]);
    }

    if ($method === 'POST') {
        $id = ps_uuid();
        $stmt = $db->prepare('INSERT INTO vias (id, nombre, ciudad, tipo, longitud_km, descripcion) VALUES (?,?,?,?,?,?)');
        $stmt->execute([
            $id,
            trim($input['nombre'] ?? ''),
            trim($input['ciudad'] ?? ''),
            trim($input['tipo'] ?? 'avenida'),
            (float) ($input['longitud_km'] ?? 0),
            trim($input['descripcion'] ?? '') ?: null,
        ]);
        $row = $db->prepare('SELECT * FROM vias WHERE id = ?');
        $row->execute([$id]);
        ps_json_response(['ok' => true, 'data' => $row->fetch()]);
    }

    if ($method === 'PUT') {
        $id = $input['id'] ?? '';
        if (!$id) {
            ps_json_response(['ok' => false, 'error' => 'ID requerido'], 400);
        }
        $stmt = $db->prepare('UPDATE vias SET nombre=?, ciudad=?, tipo=?, longitud_km=?, descripcion=? WHERE id=?');
        $stmt->execute([
            trim($input['nombre'] ?? ''),
            trim($input['ciudad'] ?? ''),
            trim($input['tipo'] ?? 'avenida'),
            (float) ($input['longitud_km'] ?? 0),
            trim($input['descripcion'] ?? '') ?: null,
            $id,
        ]);
        $row = $db->prepare('SELECT * FROM vias WHERE id = ?');
        $row->execute([$id]);
        ps_json_response(['ok' => true, 'data' => $row->fetch()]);
    }

    if ($method === 'DELETE') {
        $id = $input['id'] ?? ($_GET['id'] ?? '');
        $db->prepare('DELETE FROM vias WHERE id = ?')->execute([$id]);
        ps_json_response(['ok' => true]);
    }

    ps_json_response(['ok' => false, 'error' => 'Método no permitido'], 405);
} catch (Throwable $e) {
    ps_json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
