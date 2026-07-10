<?php
require_once __DIR__ . '/db.php';

function ps_json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function ps_uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function ps_upload_dir(): string
{
    $dir = PS_UPLOAD_DIR;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function ps_public_upload_url(string $filename): string
{
    return ps_base_url() . '/uploads/baches/' . rawurlencode($filename);
}

function ps_save_uploaded_image(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir la imagen');
    }
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException('Formato no permitido. Usa JPG, PNG o WebP.');
    }
    if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
        throw new RuntimeException('La imagen supera 8 MB');
    }
    $ext = match ($mime) {
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        default => 'jpg',
    };
    $name = ps_uuid() . '.' . $ext;
    $dest = ps_upload_dir() . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('No se pudo guardar la imagen');
    }
    return ps_public_upload_url($name);
}

/**
 * Escaneo: YOLOv8 (v1-bache) → remoto → Lovable → error claro.
 */
function ps_scan_image(string $imageUrl): array
{
    $scannerUrl = defined('PS_SCANNER_URL') ? trim(PS_SCANNER_URL) : '';
    if ($scannerUrl !== '') {
        return ps_scan_yolo_remote($imageUrl, $scannerUrl);
    }

    if (ps_yolo_available()) {
        return ps_scan_yolo($imageUrl);
    }

    $key = defined('PS_LOVABLE_API_KEY') ? trim(PS_LOVABLE_API_KEY) : '';
    if ($key !== '') {
        return ps_scan_lovable($imageUrl, $key);
    }

    throw new RuntimeException(
        'Motor YOLOv8 no disponible. Ejecuta scanner\\setup.bat, configura PS_PYTHON_BIN en config.php, '
        . 'o define PS_SCANNER_URL con api_server.py (ver README-DEPLOY.md).'
    );
}

function ps_resolve_upload_path(string $imageUrl): string
{
    $path = parse_url($imageUrl, PHP_URL_PATH) ?: '';
    $name = basename($path);
    $local = ps_upload_dir() . DIRECTORY_SEPARATOR . $name;
    if (!is_file($local)) {
        throw new RuntimeException('Archivo de imagen no encontrado en uploads');
    }
    return $local;
}

function ps_python_bin(): string
{
    $bin = defined('PS_PYTHON_BIN') ? trim(PS_PYTHON_BIN) : '';
    return $bin !== '' ? $bin : 'python';
}

function ps_yolo_available(): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $script = dirname(__DIR__) . '/scanner/scan_baches.py';
    if (!is_file($script)) {
        $cache = false;
        return false;
    }
    $python = ps_python_bin();
    $out = [];
    $code = 1;
    @exec(escapeshellarg($python) . ' --version 2>&1', $out, $code);
    $cache = ($code === 0);
    return $cache;
}

function ps_scan_yolo(string $imageUrl): array
{
    $local = ps_resolve_upload_path($imageUrl);
    $python = ps_python_bin();
    $script = dirname(__DIR__) . '/scanner/scan_baches.py';
    $outDir = ps_upload_dir();
    $conf = defined('PS_YOLO_CONF') ? (float) PS_YOLO_CONF : 0.3;

    $cmd = escapeshellarg($python) . ' ' . escapeshellarg($script) . ' '
        . escapeshellarg($local) . ' ' . escapeshellarg($outDir) . ' '
        . escapeshellarg((string) $conf);

    $lines = [];
    $code = 1;
    @exec($cmd . ' 2>&1', $lines, $code);

    $raw = trim(implode("\n", $lines));
    $jsonLine = $raw;
    if (preg_match('/\{.*\}/s', $raw, $m)) {
        $jsonLine = $m[0];
    }
    $data = json_decode($jsonLine, true);
    if (!is_array($data) || empty($data['ok'])) {
        $err = is_array($data) ? ($data['error'] ?? $raw) : $raw;
        throw new RuntimeException('YOLOv8: ' . ($err ?: 'error desconocido'));
    }

    return ps_finalize_yolo_result($data);
}

function ps_scan_yolo_remote(string $imageUrl, string $scannerUrl): array
{
    $local = ps_resolve_upload_path($imageUrl);
    $url = rtrim($scannerUrl, '/');
    if (!str_ends_with($url, '/scan')) {
        $url .= '/scan';
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL requerido para PS_SCANNER_URL');
    }

    $cfile = new CURLFile($local, mime_content_type($local) ?: 'image/jpeg', basename($local));
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['image' => $cfile],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);
    $res = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false || $status < 200 || $status >= 300) {
        throw new RuntimeException('Scanner remoto no respondió (HTTP ' . $status . ')');
    }

    $data = json_decode($res, true);
    if (!is_array($data) || empty($data['ok'])) {
        throw new RuntimeException('Scanner remoto: ' . ($data['error'] ?? 'respuesta inválida'));
    }

    if (!empty($data['annotated_base64'])) {
        $name = pathinfo($local, PATHINFO_FILENAME) . '_detected.jpg';
        $dest = ps_upload_dir() . DIRECTORY_SEPARATOR . $name;
        file_put_contents($dest, base64_decode($data['annotated_base64']));
        $data['annotated_file'] = $name;
    }

    return ps_finalize_yolo_result($data);
}

function ps_finalize_yolo_result(array $data): array
{
    $result = ps_normalize_scan_result($data);
    if (!empty($data['annotated_file'])) {
        $result['annotated_url'] = ps_public_upload_url((string) $data['annotated_file']);
    }
    return $result;
}

function ps_scan_lovable(string $imageUrl, string $apiKey): array
{
    $prompt = 'Eres un sistema de visión artificial especializado en detección de baches en vías urbanas.
Analiza la imagen y responde SOLO con un objeto JSON válido (sin markdown) con esta forma:
{"cantidad_baches":number,"severidad":"baja"|"media"|"alta"|"critica","confianza":number,"analisis_ia":string}';

    $payload = json_encode([
        'model' => 'google/gemini-2.5-flash',
        'messages' => [[
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
            ],
        ]],
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
            'content' => $payload,
            'timeout' => 60,
            'ignore_errors' => true,
        ],
    ]);

    $res = @file_get_contents('https://ai.gateway.lovable.dev/v1/chat/completions', false, $ctx);
    if ($res === false) {
        throw new RuntimeException('No se pudo conectar al servicio de IA');
    }
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
        $status = (int) $m[0];
    }
    if ($status === 429) {
        throw new RuntimeException('Límite de uso IA alcanzado. Intenta más tarde.');
    }
    if ($status === 402) {
        throw new RuntimeException('Sin créditos de IA disponibles.');
    }
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException('Error IA (' . $status . ')');
    }

    $json = json_decode($res, true);
    $content = (string) ($json['choices'][0]['message']['content'] ?? '{}');
    $content = preg_replace('/```json\s*|\s*```/', '', $content);
    $content = trim($content);

    return ps_normalize_scan_result(json_decode($content, true) ?: []);
}

function ps_normalize_scan_result(array $parsed): array
{
    $sev = $parsed['severidad'] ?? 'media';
    if (!in_array($sev, ['baja', 'media', 'alta', 'critica'], true)) {
        $sev = 'media';
    }
    $out = [
        'cantidad_baches' => max(0, (int) ($parsed['cantidad_baches'] ?? 0)),
        'severidad' => $sev,
        'confianza' => max(0, min(100, (float) ($parsed['confianza'] ?? 0))),
        'analisis_ia' => mb_substr((string) ($parsed['analisis_ia'] ?? 'Sin análisis'), 0, 500),
    ];
    if (!empty($parsed['annotated_url'])) {
        $out['annotated_url'] = $parsed['annotated_url'];
    }
    return $out;
}

function ps_scanner_status(): array
{
    if (defined('PS_SCANNER_URL') && trim(PS_SCANNER_URL) !== '') {
        return ['mode' => 'remote', 'label' => 'YOLOv8 remoto', 'ready' => true];
    }
    if (ps_yolo_available()) {
        return ['mode' => 'local', 'label' => 'YOLOv8 local (v1-bache)', 'ready' => true];
    }
    return ['mode' => 'none', 'label' => 'YOLOv8 no configurado', 'ready' => false];
}

function ps_list_vias(): array
{
    return ps_db()->query('SELECT * FROM vias ORDER BY created_at DESC')->fetchAll();
}

function ps_list_detecciones(): array
{
    $sql = 'SELECT d.*, v.nombre AS via_nombre, v.ciudad AS via_ciudad
            FROM detecciones d
            LEFT JOIN vias v ON v.id = d.via_id
            ORDER BY d.created_at DESC';
    return ps_db()->query($sql)->fetchAll();
}

function ps_dashboard_stats(): array
{
    $db = ps_db();
    $total = (int) $db->query('SELECT COUNT(*) FROM detecciones')->fetchColumn();
    $baches = (int) $db->query('SELECT COALESCE(SUM(cantidad_baches),0) FROM detecciones')->fetchColumn();
    $criticos = (int) $db->query("SELECT COUNT(*) FROM detecciones WHERE severidad IN ('alta','critica')")->fetchColumn();
    $avg = (float) $db->query('SELECT COALESCE(AVG(confianza),0) FROM detecciones')->fetchColumn();
    $vias = (int) $db->query('SELECT COUNT(*) FROM vias')->fetchColumn();
    return [
        'detecciones' => $total,
        'baches' => $baches,
        'criticos' => $criticos,
        'confianza_media' => $total ? (int) round($avg) : 0,
        'vias' => $vias,
    ];
}
