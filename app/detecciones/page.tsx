'use client';

import { FormEvent, useEffect, useState } from 'react';
import type { Deteccion, Via } from '@/lib/db';

export default function DeteccionesPage() {
  const [dets, setDets] = useState<Deteccion[]>([]);
  const [vias, setVias] = useState<Via[]>([]);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [preview, setPreview] = useState<string | null>(null);
  const [file, setFile] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [scanner, setScanner] = useState({ label: '…', ready: false });

  async function load() {
    const [d, v, s] = await Promise.all([
      fetch('/api/detecciones').then((r) => r.json()),
      fetch('/api/vias').then((r) => r.json()),
      fetch('/api/scanner-status').then((r) => r.json()).catch(() => ({ label: 'YOLOv8', ready: false })),
    ]);
    if (d.ok) setDets(d.data);
    else setError(d.error);
    if (v.ok) setVias(v.data);
    if (s.label) setScanner(s);
  }

  useEffect(() => { load(); }, []);

  async function onScan(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setMessage(null);
    setError(null);
    if (!file) { setError('Selecciona una imagen'); return; }
    setLoading(true);
    try {
      const fd = new FormData();
      fd.append('image', file);
      const up = await fetch('/api/upload', { method: 'POST', body: fd }).then((r) => r.json());
      if (!up.ok) throw new Error(up.error);

      const scan = await fetch('/api/scan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ image_url: up.image_url }),
      }).then((r) => r.json());
      if (!scan.ok) throw new Error(scan.error);

      const form = e.currentTarget;
      const via_id = (form.elements.namedItem('via_id') as HTMLSelectElement).value;
      const ubicacion = (form.elements.namedItem('ubicacion') as HTMLInputElement).value;

      const save = await fetch('/api/detecciones', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          via_id: via_id || null,
          image_url: up.image_url,
          annotated_url: scan.data.annotated_url ?? null,
          severidad: scan.data.severidad,
          confianza: scan.data.confianza,
          cantidad_baches: scan.data.cantidad_baches,
          analisis_ia: scan.data.analisis_ia,
          ubicacion: ubicacion || null,
        }),
      }).then((r) => r.json());
      if (!save.ok) throw new Error(save.error);

      setMessage(`Detectados ${scan.data.cantidad_baches} baches · ${String(scan.data.severidad).toUpperCase()}`);
      setFile(null);
      setPreview(null);
      form.reset();
      await load();
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  }

  async function onDelete(id: string) {
    if (!confirm('¿Eliminar detección?')) return;
    const res = await fetch('/api/detecciones', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id }),
    }).then((r) => r.json());
    if (res.ok) { setMessage('Detección eliminada'); await load(); }
    else setError(res.error);
  }

  return (
    <div className="container page">
      <header className="page-header">
        <div>
          <div className="mono muted">// módulo 02 · CRUD + visión artificial</div>
          <h1 className="display page-title">Detecciones</h1>
        </div>
      </header>

      {message && <div className="alert alert-ok">✓ {message}</div>}
      {error && <div className="alert alert-err">{error}</div>}

      <section className="panel scan-grid">
        <div>
          <div className="mono muted">Paso 01</div>
          <h2 className="display" style={{ fontSize: '1.75rem', margin: '.5rem 0 1rem' }}>Subir imagen para escaneo</h2>
          <p className="muted" style={{ fontSize: '.85rem', marginBottom: '1.5rem' }}>
            Motor: <strong>{scanner.label}</strong> (YOLOv8 segmentación, mismo modelo que v1-bache).
            {!scanner.ready && (
              <><br /><span style={{ color: '#b91c1c' }}>Configura <code>PS_SCANNER_URL</code> en Vercel.</span></>
            )}
          </p>
          <label className="upload-box">
            {!preview ? (
              <span style={{ textAlign: 'center' }}>
                <span className="display" style={{ fontSize: '3rem' }}>+</span>
                <span className="mono muted" style={{ display: 'block', marginTop: '.5rem' }}>Click para subir</span>
              </span>
            ) : (
              <img src={preview} alt="preview" />
            )}
            <input
              type="file"
              accept="image/*"
              style={{ position: 'absolute', inset: 0, opacity: 0, cursor: 'pointer' }}
              onChange={(ev) => {
                const f = ev.target.files?.[0];
                if (!f) return;
                setFile(f);
                setPreview(URL.createObjectURL(f));
              }}
            />
          </label>
        </div>
        <form onSubmit={onScan}>
          <div style={{ marginBottom: '1rem' }}>
            <label className="label">Vía asociada (opcional)</label>
            <select className="input" name="via_id" defaultValue="">
              <option value="">— Sin asociar —</option>
              {vias.map((v) => (
                <option key={v.id} value={v.id}>{v.nombre} · {v.ciudad}</option>
              ))}
            </select>
          </div>
          <div style={{ marginBottom: '1rem' }}>
            <label className="label">Ubicación / referencia</label>
            <input className="input" name="ubicacion" placeholder="Cra 7 con Cl 45, sector norte…" />
          </div>
          <div style={{ marginTop: 'auto', paddingTop: '1rem', borderTop: '1px solid var(--border)' }}>
            <button type="submit" className="btn btn-primary" style={{ width: '100%', justifyContent: 'center' }} disabled={loading}>
              {loading ? 'Escaneando…' : 'Ejecutar escaneo IA →'}
            </button>
          </div>
        </form>
      </section>

      <section>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '1.5rem' }}>
          <h2 className="display" style={{ fontSize: '1.75rem' }}>Historial de detecciones</h2>
          <span className="mono muted">{dets.length} registros</span>
        </div>

        {!dets.length && (
          <div className="panel" style={{ padding: '3rem', textAlign: 'center' }}>
            <div className="display" style={{ fontSize: '1.75rem' }}>Sin detecciones aún</div>
            <p className="mono muted" style={{ marginTop: '.75rem' }}>Sube tu primera imagen arriba.</p>
          </div>
        )}

        <div className="cards">
          {dets.map((d) => {
            const img = d.annotated_url || d.image_url;
            return (
              <article key={d.id} className="panel card" style={{ display: 'flex', flexDirection: 'column', overflow: 'hidden' }}>
                <div className="card-img">
                  <img src={img} alt="detección" className={d.annotated_url ? 'img-detected' : ''} />
                </div>
                <div className="card-body">
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '.75rem' }}>
                    <span className={`tag tag-${d.severidad}`}>{d.severidad}</span>
                    <span className="mono">{d.confianza}% conf.</span>
                  </div>
                  <div className="display" style={{ fontSize: '2.25rem' }}>
                    {d.cantidad_baches}<span className="muted" style={{ fontSize: '1rem' }}> baches</span>
                  </div>
                  {d.analisis_ia && <p className="muted" style={{ fontSize: '.75rem', marginTop: '.75rem', lineHeight: 1.5 }}>{d.analisis_ia}</p>}
                  <div style={{ marginTop: '1rem', paddingTop: '1rem', borderTop: '1px solid var(--border)' }}>
                    {d.via_nombre && <div className="mono">{d.via_nombre} · {d.via_ciudad}</div>}
                    {d.ubicacion && <div className="muted" style={{ fontSize: '.75rem' }}>{d.ubicacion}</div>}
                    <div className="mono muted" style={{ marginTop: '.25rem' }}>
                      {new Date(d.fecha_deteccion).toLocaleString('es-MX')}
                    </div>
                  </div>
                  <button type="button" className="btn" style={{ marginTop: '1rem' }} onClick={() => onDelete(d.id)}>Eliminar</button>
                </div>
              </article>
            );
          })}
        </div>
      </section>
    </div>
  );
}
