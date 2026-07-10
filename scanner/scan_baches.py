#!/usr/bin/env python3
"""
Motor de detección YOLOv8 (mismo modelo que v1-bache / app_baches.py).
Uso CLI:
  python scan_baches.py <ruta_imagen> <carpeta_salida> [confianza]

Imprime JSON en stdout (una línea).
"""
from __future__ import annotations

import json
import sys
import urllib.request
from pathlib import Path

import numpy as np
from PIL import Image

MODEL_FILENAME = "pothole-segmentation-for-road-damage-assessment.pt"
MODEL_URL = (
    "https://raw.githubusercontent.com/FarzadNekouee/"
    "YOLOv8_Pothole_Segmentation_Road_Damage_Assessment/master/model/best.pt"
)

BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = BASE_DIR / MODEL_FILENAME


def ensure_model() -> Path:
    if MODEL_PATH.is_file():
        return MODEL_PATH
    urllib.request.urlretrieve(MODEL_URL, MODEL_PATH)
    return MODEL_PATH


def severidad_desde_conteo(n: int, conf_prom: float) -> str:
    if n >= 5:
        return "critica"
    if n >= 3:
        return "alta"
    if n >= 1:
        return "media" if conf_prom < 85 else "alta"
    return "baja"


def escanear(ruta_imagen: str, carpeta_salida: str, conf: float = 0.3) -> dict:
    from ultralytics import YOLO

    try:
        import cv2
    except ImportError:
        cv2 = None

    ruta = Path(ruta_imagen).resolve()
    if not ruta.is_file():
        raise FileNotFoundError(f"Imagen no encontrada: {ruta}")

    out_dir = Path(carpeta_salida).resolve()
    out_dir.mkdir(parents=True, exist_ok=True)

    modelo = YOLO(str(ensure_model()))
    img = Image.open(ruta).convert("RGB")
    arr = np.asarray(img)

    resultados = modelo.predict(source=arr, conf=conf, verbose=False)
    r0 = resultados[0]

    n = len(r0.boxes) if r0.boxes is not None else 0
    confidencias: list[float] = []
    if r0.boxes is not None and len(r0.boxes):
        confidencias = [float(x) for x in r0.boxes.conf.cpu().numpy()]

    conf_prom = (sum(confidencias) / len(confidencias) * 100.0) if confidencias else 0.0

    annotated_name = ruta.stem + "_detected.jpg"
    annotated_path = out_dir / annotated_name
    plotted = r0.plot()

    if cv2 is not None:
        cv2.imwrite(str(annotated_path), plotted)
    else:
        Image.fromarray(plotted[:, :, ::-1] if plotted.shape[-1] == 3 else plotted).save(
            annotated_path, quality=92
        )

    sev = severidad_desde_conteo(n, conf_prom)
    if n == 0:
        analisis = (
            f"YOLOv8: sin baches por encima del umbral ({conf:.0%}). "
            "Prueba otra foto o baja PS_YOLO_CONF."
        )
    else:
        analisis = (
            f"YOLOv8 segmentación: {n} región(es) de daño. "
            f"Confianza media {conf_prom:.1f}%."
        )

    return {
        "ok": True,
        "cantidad_baches": n,
        "severidad": sev,
        "confianza": round(conf_prom, 2),
        "analisis_ia": analisis,
        "annotated_file": annotated_name,
        "engine": "yolov8",
    }


def main() -> None:
    if len(sys.argv) < 3:
        print(
            json.dumps(
                {
                    "ok": False,
                    "error": "Uso: scan_baches.py <imagen> <carpeta_salida> [conf]",
                }
            )
        )
        sys.exit(1)

    ruta_imagen = sys.argv[1]
    carpeta = sys.argv[2]
    conf = float(sys.argv[3]) if len(sys.argv) > 3 else 0.3

    try:
        data = escanear(ruta_imagen, carpeta, conf)
        print(json.dumps(data, ensure_ascii=False))
    except Exception as exc:
        print(json.dumps({"ok": False, "error": str(exc)}, ensure_ascii=False))
        sys.exit(1)


if __name__ == "__main__":
    main()
