<?php
require_once __DIR__ . '/functions.php';
$psCurrent = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(PS_APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars(ps_base_url()) ?>/assets/css/app.css">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a href="<?= htmlspecialchars(ps_base_url()) ?>/index.php" class="brand">
      <span class="brand-mark">VA</span>
      <span>
        <span class="display brand-title">Visión Artificial</span>
        <span class="mono muted">Detección de baches</span>
      </span>
    </a>
    <nav class="nav">
      <a href="<?= htmlspecialchars(ps_base_url()) ?>/index.php" class="nav-link<?= $psCurrent === 'index.php' ? ' active' : '' ?>">Dashboard</a>
      <a href="<?= htmlspecialchars(ps_base_url()) ?>/vias.php" class="nav-link<?= $psCurrent === 'vias.php' ? ' active' : '' ?>">Vías</a>
      <a href="<?= htmlspecialchars(ps_base_url()) ?>/detecciones.php" class="nav-link<?= $psCurrent === 'detecciones.php' ? ' active' : '' ?>">Detecciones</a>
    </nav>
  </div>
</header>
<main>
