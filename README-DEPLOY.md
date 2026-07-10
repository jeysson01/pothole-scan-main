# Pothole Scan — XAMPP e InfinityFree

PHP + MySQL + **detección YOLOv8** integrada desde `v1-bache` (segmentación real de baches).

## 1. XAMPP — instalación completa

1. Carpeta en `C:\xampp\htdocs\pothole-scan-main`
2. Inicia **Apache** y **MySQL**
3. Edita `includes/config.php` → `PS_BASE_URL = http://localhost/pothole-scan-main`
4. Abre `http://localhost/pothole-scan-main/install.php` → **Crear tablas**
5. **Instala el motor YOLO** (solo una vez):

```bat
cd C:\xampp\htdocs\pothole-scan-main\scanner
setup.bat
```

O manualmente:

```bat
pip install -r scanner\requirements.txt
```

6. Si `python` no está en PATH, en `config.php`:

```php
define('PS_PYTHON_BIN', 'C:\\Users\\TU_USUARIO\\AppData\\Local\\Programs\\Python\\Python311\\python.exe');
```

7. Usa la app: `http://localhost/pothole-scan-main/detecciones.php`

Al escanear verás la imagen con **máscaras de segmentación** (como en v1-bache) y el conteo real de baches.

## 2. InfinityFree (subdominio)

InfinityFree **no ejecuta Python**. La web PHP va en el subdominio; el escáner YOLO corre en otro sitio.

### Paso A — Subir la web PHP

1. Sube todo `pothole-scan-main` a la raíz del subdominio
2. Configura MySQL en `includes/config.php` (datos del panel InfinityFree)
3. `PS_BASE_URL` = `https://tusubdominio.infinityfreeapp.com`
4. `.htaccess` → `RewriteBase /`
5. Ejecuta `install.php` una vez

### Paso B — Scanner remoto (YOLO)

Opciones gratuitas: **PythonAnywhere**, PC con **ngrok**, o un VPS.

En tu máquina o PythonAnywhere:

```bash
cd scanner
pip install -r requirements.txt
python api_server.py
```

En `includes/config.php` del hosting:

```php
define('PS_SCANNER_URL', 'https://TU-SERVIDOR/scan');
```

La web enviará la imagen al scanner y guardará el resultado + imagen anotada.

## Configuración (`includes/config.php`)

| Constante | Uso |
|-----------|-----|
| `PS_PYTHON_BIN` | Ruta a `python.exe` (XAMPP local) |
| `PS_YOLO_CONF` | Umbral 0.0–1.0 (default 0.3) |
| `PS_SCANNER_URL` | URL remota `/scan` (InfinityFree) |
| `PS_LOVABLE_API_KEY` | Opcional, solo si no hay YOLO |

## API REST

| Endpoint | Descripción |
|----------|-------------|
| `/api/upload.php` | Subir imagen |
| `/api/scan.php` | Escanear por `image_url` |
| `/api/detecciones.php` | CRUD detecciones |
