<?php
require_once __DIR__ . '/includes/header.php';

try {
    $stats = ps_dashboard_stats();
    $dbOk = true;
} catch (Throwable $e) {
    $dbOk = false;
    $stats = ['detecciones' => 0, 'baches' => 0, 'criticos' => 0, 'confianza_media' => 0, 'vias' => 0];
    $dbError = $e->getMessage();
}
?>
<div class="container page">
  <?php if (!$dbOk): ?>
    <div class="alert alert-err">
      Base de datos no configurada: <?= htmlspecialchars($dbError) ?>.
      <a href="<?= htmlspecialchars(ps_base_url()) ?>/install.php">Ir al instalador →</a>
    </div>
  <?php endif; ?>

  <div class="hero-grid">
    <div>
      <div class="mono muted">// sistema v1.0 · visión artificial</div>
      <h1 class="display hero-title">
        Baches<br>
        <span class="muted" style="font-style:italic">detectados</span><br>
        automáticamente.
      </h1>
    </div>
    <div class="hero-side">
      <p style="font-size:.9rem;line-height:1.6">
        Sube una imagen de una vía urbana y el sistema analiza el pavimento para identificar
        y clasificar baches por severidad. Datos en MySQL (XAMPP / InfinityFree).
      </p>
      <div style="display:flex;gap:.5rem;margin-top:1.5rem;flex-wrap:wrap">
        <a href="<?= htmlspecialchars(ps_base_url()) ?>/detecciones.php" class="btn btn-primary">Escanear ahora →</a>
        <a href="<?= htmlspecialchars(ps_base_url()) ?>/vias.php" class="btn">Gestionar vías</a>
      </div>
    </div>
  </div>

  <section class="stats">
    <div class="stat"><div class="mono muted">Detecciones</div><div class="display stat-value"><?= (int) $stats['detecciones'] ?></div></div>
    <div class="stat"><div class="mono muted">Baches totales</div><div class="display stat-value"><?= (int) $stats['baches'] ?></div></div>
    <div class="stat"><div class="mono muted">Casos críticos</div><div class="display stat-value"><?= (int) $stats['criticos'] ?></div></div>
    <div class="stat"><div class="mono muted">Confianza media</div><div class="display stat-value"><?= (int) $stats['confianza_media'] ?>%</div></div>
  </section>

  <section class="modules">
    <a href="<?= htmlspecialchars(ps_base_url()) ?>/vias.php" class="module-card">
      <div style="display:flex;justify-content:space-between;margin-bottom:2rem">
        <span class="mono">01</span>
        <span class="mono"><?= (int) $stats['vias'] ?> registros</span>
      </div>
      <div class="display" style="font-size:1.75rem;margin-bottom:.75rem">Módulo CRUD — Vías</div>
      <p style="font-size:.85rem;opacity:.85;margin-bottom:1.5rem">Registra, edita y elimina las vías urbanas monitoreadas.</p>
      <span class="mono">Abrir módulo →</span>
    </a>
    <a href="<?= htmlspecialchars(ps_base_url()) ?>/detecciones.php" class="module-card">
      <div style="display:flex;justify-content:space-between;margin-bottom:2rem">
        <span class="mono">02</span>
        <span class="mono"><?= (int) $stats['detecciones'] ?> registros</span>
      </div>
      <div class="display" style="font-size:1.75rem;margin-bottom:.75rem">Módulo CRUD — Detecciones</div>
      <p style="font-size:.85rem;opacity:.85;margin-bottom:1.5rem">Sube imágenes, ejecuta escaneo y administra resultados.</p>
      <span class="mono">Abrir módulo →</span>
    </a>
  </section>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
