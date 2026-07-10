@echo off
cd /d "%~dp0"
echo Instalando dependencias YOLOv8 para Pothole Scan...
python -m pip install --upgrade pip
python -m pip install -r requirements.txt
echo.
echo Descargando modelo (primera vez, ~6.5 MB)...
python -c "from scan_baches import ensure_model; ensure_model(); print('Modelo OK')"
if exist "..\uploads\baches" (
  echo Carpeta uploads OK
) else (
  mkdir "..\uploads\baches"
)
echo Listo. Configura PS_PYTHON_BIN en includes\config.php si python no esta en PATH.
pause
