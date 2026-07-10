'use client';

import { FormEvent, useEffect, useState } from 'react';
import type { Via } from '@/lib/db';

export default function ViasPage() {
  const [vias, setVias] = useState<Via[]>([]);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [edit, setEdit] = useState<Via | null>(null);
  const [showForm, setShowForm] = useState(false);

  async function load() {
    const res = await fetch('/api/vias').then((r) => r.json());
    if (res.ok) setVias(res.data);
    else setError(res.error);
  }

  useEffect(() => { load(); }, []);

  async function onSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setMessage(null);
    setError(null);
    const fd = new FormData(e.currentTarget);
    const body = {
      id: edit?.id,
      nombre: fd.get('nombre'),
      ciudad: fd.get('ciudad'),
      tipo: fd.get('tipo'),
      longitud_km: fd.get('longitud_km'),
      descripcion: fd.get('descripcion'),
    };
    const res = await fetch('/api/vias', {
      method: edit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    }).then((r) => r.json());
    if (res.ok) {
      setMessage(edit ? 'Vía actualizada' : 'Vía registrada');
      setEdit(null);
      setShowForm(false);
      await load();
    } else setError(res.error);
  }

  async function onDelete(id: string) {
    if (!confirm('¿Eliminar?')) return;
    const res = await fetch('/api/vias', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id }),
    }).then((r) => r.json());
    if (res.ok) { setMessage('Vía eliminada'); await load(); }
    else setError(res.error);
  }

  return (
    <div className="container page">
      <header className="page-header">
        <div>
          <div className="mono muted">// módulo 01 · CRUD</div>
          <h1 className="display page-title">Vías urbanas</h1>
        </div>
        <button type="button" className="btn btn-primary" onClick={() => { setEdit(null); setShowForm(true); }}>+ Nueva vía</button>
      </header>

      {message && <div className="alert alert-ok">✓ {message}</div>}
      {error && <div className="alert alert-err">{error}</div>}

      {(showForm || edit) && (
        <form onSubmit={onSubmit} className="panel form-grid cols-2">
          <div style={{ gridColumn: '1/-1' }} className="mono muted">
            {edit ? `Editando: ${edit.nombre}` : 'Nueva vía'}
          </div>
          <div>
            <label className="label">Nombre</label>
            <input className="input" name="nombre" required defaultValue={edit?.nombre ?? ''} />
          </div>
          <div>
            <label className="label">Ciudad</label>
            <input className="input" name="ciudad" required defaultValue={edit?.ciudad ?? ''} />
          </div>
          <div>
            <label className="label">Tipo</label>
            <select className="input" name="tipo" defaultValue={edit?.tipo ?? 'avenida'}>
              {['avenida', 'calle', 'autopista', 'boulevard'].map((t) => (
                <option key={t} value={t}>{t.charAt(0).toUpperCase() + t.slice(1)}</option>
              ))}
            </select>
          </div>
          <div>
            <label className="label">Longitud (km)</label>
            <input className="input" type="number" step="0.01" name="longitud_km" defaultValue={edit?.longitud_km ?? 0} />
          </div>
          <div style={{ gridColumn: '1/-1' }}>
            <label className="label">Descripción</label>
            <textarea className="input" name="descripcion" rows={2} defaultValue={edit?.descripcion ?? ''} />
          </div>
          <div style={{ gridColumn: '1/-1', display: 'flex', gap: '.5rem' }}>
            <button type="submit" className="btn btn-primary">{edit ? 'Guardar cambios' : 'Crear vía'}</button>
            <button type="button" className="btn" onClick={() => { setEdit(null); setShowForm(false); }}>Cancelar</button>
          </div>
        </form>
      )}

      <div className="panel">
        <div className="table-head mono muted">
          <div>Nombre</div><div>Ciudad</div><div>Tipo</div><div>Longitud</div><div style={{ textAlign: 'right' }}>Acciones</div>
        </div>
        {!vias.length && <p className="mono muted" style={{ padding: '2rem' }}>Sin vías registradas.</p>}
        {vias.map((v) => (
          <div key={v.id} className="table-row">
            <div>
              <div className="display" style={{ fontSize: '1.25rem' }}>{v.nombre}</div>
              {v.descripcion && <div className="muted" style={{ fontSize: '.75rem', marginTop: '.25rem' }}>{v.descripcion}</div>}
            </div>
            <div>{v.ciudad}</div>
            <div className="mono">{v.tipo}</div>
            <div className="mono">{v.longitud_km} km</div>
            <div className="row-actions">
              <button type="button" className="btn" onClick={() => { setEdit(v); setShowForm(true); }}>Editar</button>
              <button type="button" className="btn" onClick={() => onDelete(v.id)}>×</button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
