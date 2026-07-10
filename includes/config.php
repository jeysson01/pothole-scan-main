<?php
define('PS_DB_HOST', 'localhost');
define('PS_DB_NAME', 'pothole_scan');
define('PS_DB_USER', 'root');
define('PS_DB_PASS', '');
define('PS_DB_CHARSET', 'utf8mb4');

define('PS_BASE_URL', 'http://localhost/pothole-scan-main');

define('PS_APP_NAME', 'Pothole Scan');
define('PS_TIMEZONE', 'America/Mexico_City');
define('PS_DEMO_MODE', false);
define('PS_LOVABLE_API_KEY', '');
define('PS_UPLOAD_DIR', __DIR__ . '/../uploads/baches');

/** YOLOv8 v1-bache — ruta a python.exe (vacío = buscar "python" en PATH) */
define('PS_PYTHON_BIN', '');
/** Umbral de confianza del modelo (0.0 - 1.0) */
define('PS_YOLO_CONF', 0.3);
/**
 * InfinityFree: URL del scanner remoto (api_server.py en PythonAnywhere/VPS).
 * Ej: http://127.0.0.1:5050/scan o https://tuusuario.pythonanywhere.com/scan
 * Vacío = usar Python local vía subprocess (XAMPP).
 */
define('PS_SCANNER_URL', '');
