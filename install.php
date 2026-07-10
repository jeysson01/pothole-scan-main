<?php
/**
 * Instalador — ejecutar una vez (XAMPP o InfinityFree)
 */
require_once __DIR__ . '/includes/config.php';

$sqlFile = __DIR__ . '/sql/schema.sql';
$messages = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dsn = 'mysql:host=' . PS_DB_HOST . ';charset=' . PS_DB_CHARSET;
        $pdo = new PDO($dsn, PS_DB_USER, PS_DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . PS_DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . PS_DB_NAME . '`');

        $sql = file_get_contents($sqlFile);
        $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
        foreach ($statements as $stmt) {
            if ($stmt !== '' && !preg_match('/^--/', $stmt)) {
                $pdo->exec($stmt);
            }
        }

        $count = (int) $pdo->query('SELECT COUNT(*) FROM vias')->fetchColumn();
        if ($count === 0) {
            $samples = [
                [ps_uuid(), 'Av. Reforma', 'Ciudad de México', 'avenida', 12.5, 'Eje central poniente'],
                [ps_uuid(), 'Calle 72', 'Bogotá', 'calle', 3.2, 'Sector Chapinero'],
                [ps_uuid(), 'Autopista Norte', 'Medellín', 'autopista', 28.0, 'Tramo norte'],
            ];
            $ins = $pdo->prepare('INSERT INTO vias (id, nombre, ciudad, tipo, longitud_km, descripcion) VALUES (?,?,?,?,?,?)');
            foreach ($samples as $s) {
                $ins->execute($s);
            }
            $messages[] = '3 vías de ejemplo insertadas.';
        }

        $col = $pdo->query("SHOW COLUMNS FROM detecciones LIKE 'annotated_url'")->fetch();
        if (!$col) {
            $pdo->exec('ALTER TABLE detecciones ADD COLUMN annotated_url VARCHAR(500) NULL AFTER image_url');
            $messages[] = 'Columna annotated_url añadida (imagen con segmentación YOLO).';
        }

        if (!is_dir(PS_UPLOAD_DIR)) {
            mkdir(PS_UPLOAD_DIR, 0755, true);
        }

        $messages[] = 'Base de datos «' . PS_DB_NAME . '» instalada correctamente.';
        $messages[] = 'Motor YOLO: ejecuta scanner\\setup.bat en XAMPP para instalar Python/ultralytics.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

function ps_uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Instalar Pothole Scan</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(rtrim(PS_BASE_URL, '/')) ?>/assets/css/app.css">
</head>
<body class="container" style="min-height:100vh;display:flex;align-items:center;justify-content:center">
  <div class="panel" style="padding:2rem;max-width:32rem;width:100%">
    <h1 class="display" style="font-size:2rem;margin:0 0 1rem">Instalación Pothole Scan</h1>
    <?php if ($error): ?>
      <div class="alert alert-err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php foreach ($messages as $m): ?>
      <div class="alert alert-ok">✓ <?= htmlspecialchars($m) ?></div>
    <?php endforeach; ?>
    <?php if ($messages): ?>
      <a href="index.php" class="btn btn-primary" style="margin-top:1rem">Ir al dashboard →</a>
    <?php else: ?>
      <p class="muted" style="font-size:.9rem;line-height:1.5;margin-bottom:1.5rem">
        Asegúrate de haber configurado <code>includes/config.php</code> (MySQL de XAMPP o InfinityFree)
        y de haber iniciado Apache + MySQL.
      </p>
      <p class="mono muted" style="margin-bottom:1rem">BD: <?= htmlspecialchars(PS_DB_NAME) ?> @ <?= htmlspecialchars(PS_DB_HOST) ?></p>
      <form method="post">
        <button type="submit" class="btn btn-primary" style="width:100%">Crear tablas en MySQL</button>
      </form>
    <?php endif; ?>
    <p style="margin-top:1.5rem;font-size:.85rem"><a href="index.php">← Volver</a></p>
  </div>
</body>
</html>
