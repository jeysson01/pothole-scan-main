<?php
require_once __DIR__ . '/includes/functions.php';

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        $db = ps_db();
        if ($action === 'create' || $action === 'update') {
            $nombre = trim($_POST['nombre'] ?? '');
            $ciudad = trim($_POST['ciudad'] ?? '');
            if ($nombre === '' || $ciudad === '') {
                throw new RuntimeException('Nombre y ciudad son obligatorios');
            }
            $data = [
                $nombre,
                $ciudad,
                trim($_POST['tipo'] ?? 'avenida'),
                (float) ($_POST['longitud_km'] ?? 0),
                trim($_POST['descripcion'] ?? '') ?: null,
            ];
            if ($action === 'update') {
                $id = $_POST['id'] ?? '';
                $data[] = $id;
                $db->prepare('UPDATE vias SET nombre=?, ciudad=?, tipo=?, longitud_km=?, descripcion=? WHERE id=?')->execute($data);
                $message = 'Vía actualizada';
            } else {
                $id = ps_uuid();
                array_unshift($data, $id);
                $db->prepare('INSERT INTO vias (id, nombre, ciudad, tipo, longitud_km, descripcion) VALUES (?,?,?,?,?,?)')->execute($data);
                $message = 'Vía registrada';
            }
        }
        if ($action === 'delete') {
            $db->prepare('DELETE FROM vias WHERE id = ?')->execute([$_POST['id'] ?? '']);
            $message = 'Vía eliminada';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

try {
    $vias = ps_list_vias();
} catch (Throwable $e) {
    $vias = [];
    $error = $error ?: $e->getMessage();
}

$edit = null;
if (!empty($_GET['edit'])) {
    foreach ($vias as $v) {
        if ($v['id'] === $_GET['edit']) {
            $edit = $v;
            break;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container page">
  <header class="page-header">
    <div>
      <div class="mono muted">// módulo 01 · CRUD</div>
      <h1 class="display page-title">Vías urbanas</h1>
    </div>
    <a href="?nueva=1" class="btn btn-primary">+ Nueva vía</a>
  </header>

  <?php if ($message): ?><div class="alert alert-ok">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-err"><?= htmlspecialchars($error) ?> — <a href="install.php">Instalar BD</a></div><?php endif; ?>

  <?php if ($edit || isset($_GET['nueva'])): ?>
  <form method="post" class="panel form-grid cols-2">
    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= htmlspecialchars($edit['id']) ?>"><?php endif; ?>
    <div style="grid-column:1/-1" class="mono muted"><?= $edit ? 'Editando: ' . htmlspecialchars($edit['nombre']) : 'Nueva vía' ?></div>
    <div>
      <label class="label">Nombre</label>
      <input class="input" name="nombre" required value="<?= htmlspecialchars($edit['nombre'] ?? '') ?>">
    </div>
    <div>
      <label class="label">Ciudad</label>
      <input class="input" name="ciudad" required value="<?= htmlspecialchars($edit['ciudad'] ?? '') ?>">
    </div>
    <div>
      <label class="label">Tipo</label>
      <select class="input" name="tipo">
        <?php foreach (['avenida','calle','autopista','boulevard'] as $t): ?>
        <option value="<?= $t ?>" <?= ($edit['tipo'] ?? 'avenida') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="label">Longitud (km)</label>
      <input class="input" type="number" step="0.01" name="longitud_km" value="<?= htmlspecialchars((string) ($edit['longitud_km'] ?? '0')) ?>">
    </div>
    <div style="grid-column:1/-1">
      <label class="label">Descripción</label>
      <textarea class="input" name="descripcion" rows="2"><?= htmlspecialchars($edit['descripcion'] ?? '') ?></textarea>
    </div>
    <div style="grid-column:1/-1;display:flex;gap:.5rem">
      <button type="submit" class="btn btn-primary"><?= $edit ? 'Guardar cambios' : 'Crear vía' ?></button>
      <a href="vias.php" class="btn">Cancelar</a>
    </div>
  </form>
  <?php endif; ?>

  <div class="panel">
    <div class="table-head mono muted">
      <div>Nombre</div><div>Ciudad</div><div>Tipo</div><div>Longitud</div><div style="text-align:right">Acciones</div>
    </div>
    <?php if (!$vias): ?>
      <p class="mono muted" style="padding:2rem">Sin vías registradas.</p>
    <?php endif; ?>
    <?php foreach ($vias as $v): ?>
    <div class="table-row">
      <div>
        <div class="display" style="font-size:1.25rem"><?= htmlspecialchars($v['nombre']) ?></div>
        <?php if ($v['descripcion']): ?><div class="muted" style="font-size:.75rem;margin-top:.25rem"><?= htmlspecialchars($v['descripcion']) ?></div><?php endif; ?>
      </div>
      <div><?= htmlspecialchars($v['ciudad']) ?></div>
      <div class="mono"><?= htmlspecialchars($v['tipo']) ?></div>
      <div class="mono"><?= htmlspecialchars($v['longitud_km']) ?> km</div>
      <div class="row-actions">
        <a href="?edit=<?= urlencode($v['id']) ?>" class="btn">Editar</a>
        <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= htmlspecialchars($v['id']) ?>">
          <button type="submit" class="btn">×</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
