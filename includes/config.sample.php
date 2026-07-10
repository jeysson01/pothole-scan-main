<?php
/**
 * Copia este archivo a config.php y ajusta según tu entorno.
 *
 * XAMPP local:
 *   PS_DB_HOST = localhost, PS_DB_USER = root, PS_DB_PASS = ''
 *
 * InfinityFree (panel → MySQL):
 *   PS_DB_HOST = sqlXXX.infinityfree.com
 *   PS_DB_NAME / PS_DB_USER / PS_DB_PASS = los del panel
 *   PS_BASE_URL = https://tusubdominio.infinityfreeapp.com
 */

define('PS_DB_HOST', 'localhost');
define('PS_DB_NAME', 'pothole_scan');
define('PS_DB_USER', 'root');
define('PS_DB_PASS', '');
define('PS_DB_CHARSET', 'utf8mb4');

/** Vacío = detectar automáticamente desde la URL */
define('PS_BASE_URL', 'http://localhost/pothole-scan-main');

define('PS_APP_NAME', 'Pothole Scan');
define('PS_TIMEZONE', 'America/Mexico_City');

define('PS_DEMO_MODE', false);
define('PS_LOVABLE_API_KEY', '');
define('PS_UPLOAD_DIR', __DIR__ . '/../uploads/baches');

/** Motor v1-bache / YOLOv8 */
define('PS_PYTHON_BIN', '');
define('PS_YOLO_CONF', 0.3);
define('PS_SCANNER_URL', '');
