<?php
require_once __DIR__ . '/config.php';

date_default_timezone_set(PS_TIMEZONE);

function ps_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . PS_DB_HOST . ';dbname=' . PS_DB_NAME . ';charset=' . PS_DB_CHARSET;
        $pdo = new PDO($dsn, PS_DB_USER, PS_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function ps_base_url(): string
{
    if (defined('PS_BASE_URL') && PS_BASE_URL !== '') {
        return rtrim(PS_BASE_URL, '/');
    }
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($dir, -4) === '/api') {
        $dir = dirname($dir);
    }
    return $proto . '://' . $host . rtrim($dir, '/');
}
