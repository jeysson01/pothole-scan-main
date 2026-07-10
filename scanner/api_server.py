#!/usr/bin/env python3
"""
Servidor HTTP para escaneo remoto (InfinityFree → apunta PS_SCANNER_URL aquí).
Ejecutar en PC/VPS/PythonAnywhere:
  pip install -r requirements.txt
  python api_server.py
Escucha en http://127.0.0.1:5050/scan
"""
from __future__ import annotations

import os
import tempfile
from pathlib import Path

from flask import Flask, jsonify, request

from scan_baches import escanear

app = Flask(__name__)
CONF = float(os.environ.get("PS_YOLO_CONF", "0.3"))


@app.route("/health")
def health():
    return jsonify({"ok": True, "engine": "yolov8"})


@app.route("/scan", methods=["POST"])
def scan():
    if "image" not in request.files:
        return jsonify({"ok": False, "error": "Campo image requerido"}), 400
    f = request.files["image"]
    if not f.filename:
        return jsonify({"ok": False, "error": "Archivo vacío"}), 400

    with tempfile.TemporaryDirectory() as tmp:
        src = Path(tmp) / "input.jpg"
        f.save(src)
        out = Path(tmp) / "out"
        out.mkdir()
        try:
            result = escanear(str(src), str(out), CONF)
        except Exception as exc:
            return jsonify({"ok": False, "error": str(exc)}), 500

        ann = out / result["annotated_file"]
        if ann.is_file():
            import base64

            result["annotated_base64"] = base64.b64encode(ann.read_bytes()).decode("ascii")
        return jsonify(result)


if __name__ == "__main__":
    port = int(os.environ.get("PORT", "5050"))
    app.run(host="0.0.0.0", port=port, debug=False)
