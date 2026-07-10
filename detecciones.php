<?php
require_once __DIR__ . '/includes/functions.php';

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        $db = ps_db();
        if ($action === 'scan') {
            if (empty($_FILES['imagen'])) {
                throw new RuntimeException('Selecciona una imagen');
            }
            $imageUrl = ps_save_uploaded_image($_FILES['imagen']);
            $scan = ps_scan_image($imageUrl);
            $id = ps_uuid();
            $stmt = $db->prepare('INSERT INTO detecciones (id, via_id, image_url, annotated_url, severidad, confianza, cantidad_baches, analisis_ia, ubicacion, fecha_deteccion) VALUES (?,?,?,?,?,?,?,?,?,NOW())');
            $stmt->execute([
                $id,
                ($_POST['via_id'] ?? '') ?: null,
                $imageUrl,
                $scan['annotated_url'] ?? null,
                $scan['severidad'],
                $scan['confianza'],
                $scan['cantidad_baches'],
                $scan['analisis_ia'],
                trim($_POST['ubicacion'] ?? '') ?: null,
            ]);
            $message = 'Detectados ' . $scan['cantidad_baches'] . ' baches · ' . strtoupper($scan['severidad']);
        }
        if ($action === 'delete') {
            $db->prepare('DELETE FROM detecciones WHERE id = ?')->execute([$_POST['id'] ?? '']);
            $message = 'Detección eliminada';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$scanner = ps_scanner_status();

try {
    $dets = ps_list_detecciones();
    $vias = ps_list_vias();
} catch (Throwable $e) {
    $dets = [];
    $vias = [];
    $error = $error ?: $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container page">
  <header class="page-header">
    <div>
      <div class="mono muted">// módulo 02 · CRUD + visión artificial</div>
      <h1 class="display page-title">Detecciones</h1>
    </div>
  </header>

  <?php if ($message): ?><div class="alert alert-ok">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-err"><?= htmlspecialchars($error) ?> — <a href="install.php">Instalar BD</a></div><?php endif; ?>

  <section class="panel scan-grid">
    <div>
      <div class="mono muted">Paso 01</div>
      <h2 class="display" style="font-size:1.75rem;margin:.5rem 0 1rem">Subir imagen para escaneo</h2>
      <p class="muted" style="font-size:.85rem;margin-bottom:1.5rem">
        Carga una foto del pavimento. Motor: <strong><?= htmlspecialchars($scanner['label']) ?></strong>
        (YOLOv8 segmentación, mismo modelo que v1-bache).
        <?php if (!$scanner['ready']): ?>
          <br><span style="color:#b91c1c">Ejecuta <code>scanner\setup.bat</code> o configura <code>PS_SCANNER_URL</code>.</span>
        <?php endif; ?>
      </p>
      <label class="upload-box" id="previewBox">
        <span id="previewPlaceholder" style="text-align:center">
          <span class="display" style="font-size:3rem">+</span>
          <span class="mono muted" style="display:block;margin-top:.5rem">Click para subir</span>
        </span>
        <img id="previewImg" alt="" style="display:none">
      </label>
    </div>
    <form method="post" enctype="multipart/form-data" id="scanForm">
      <input type="hidden" name="action" value="scan">
      <input type="file" name="imagen" id="imagenInput" accept="image/*" required style="display:none">
      <div style="margin-bottom:1rem">
        <label class="label">Vía asociada (opcional)</label>
        <select class="input" name="via_id">
          <option value="">— Sin asociar —</option>
          <?php foreach ($vias as $v): ?>
          <option value="<?= htmlspecialchars($v['id']) ?>"><?= htmlspecialchars($v['nombre'] . ' · ' . $v['ciudad']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="margin-bottom:1rem">
        <label class="label">Ubicación / referencia</label>
        <input class="input" name="ubicacion" placeholder="Cra 7 con Cl 45, sector norte…">
      </div>
      <div style="margin-top:auto;padding-top:1rem;border-top:1px solid var(--border)">
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Ejecutar escaneo IA →</button>
      </div>
    </form>
  </section>

  <section>
    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:1.5rem">
      <h2 class="display" style="font-size:1.75rem">Historial de detecciones</h2>
      <span class="mono muted"><?= count($dets) ?> registros</span>
    </div>

    <?php if (!$dets): ?>
      <div class="panel" style="padding:3rem;text-align:center">
        <div class="display" style="font-size:1.75rem">Sin detecciones aún</div>
        <p class="mono muted" style="margin-top:.75rem">Sube tu primera imagen arriba.</p>
      </div>
    <?php endif; ?>

    <div class="cards">
      <?php foreach ($dets as $d):
        $imgDet = !empty($d['annotated_url']) ? $d['annotated_url'] : $d['image_url'];
      ?>
      <article class="panel" style="display:flex;flex-direction:column;overflow:hidden">
        <div class="card-img">
          <img src="<?= htmlspecialchars($imgDet) ?>" alt="detección" class="<?= !empty($d['annotated_url']) ? 'img-detected' : '' ?>">
        </div>
        <div class="card-body">
          <div style="display:flex;justify-content:space-between;margin-bottom:.75rem">
            <span class="tag tag-<?= htmlspecialchars($d['severidad']) ?>"><?= htmlspecialchars($d['severidad']) ?></span>
            <span class="mono"><?= htmlspecialchars($d['confianza']) ?>% conf.</span>
          </div>
          <div class="display" style="font-size:2.25rem"><?= (int) $d['cantidad_baches'] ?><span class="muted" style="font-size:1rem"> baches</span></div>
          <?php if ($d['analisis_ia']): ?><p class="muted" style="font-size:.75rem;margin-top:.75rem;line-height:1.5"><?= htmlspecialchars($d['analisis_ia']) ?></p><?php endif; ?>
          <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
            <?php if ($d['via_nombre']): ?><div class="mono"><?= htmlspecialchars($d['via_nombre'] . ' · ' . $d['via_ciudad']) ?></div><?php endif; ?>
            <?php if ($d['ubicacion']): ?><div class="muted" style="font-size:.75rem"><?= htmlspecialchars($d['ubicacion']) ?></div><?php endif; ?>
            <div class="mono muted" style="margin-top:.25rem"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($d['fecha_deteccion']))) ?></div>
          </div>
          <form method="post" style="margin-top:1rem" onsubmit="return confirm('¿Eliminar detección?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= htmlspecialchars($d['id']) ?>">
            <button type="submit" class="btn">Eliminar</button>
          </form>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
</div>
<script>
const box = document.getElementById('previewBox');
const input = document.getElementById('imagenInput');
const img = document.getElementById('previewImg');
const ph = document.getElementById('previewPlaceholder');
const form = document.getElementById('scanForm');
box.addEventListener('click', () => input.click());
input.addEventListener('change', () => {
  const f = input.files?.[0];
  if (!f) return;
  img.src = URL.createObjectURL(f);
  img.style.display = 'block';
  ph.style.display = 'none';
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
