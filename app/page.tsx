import Link from 'next/link';
import { dashboardStats } from '@/lib/db';

export default async function HomePage() {
  let stats = { detecciones: 0, baches: 0, criticos: 0, confianza_media: 0, vias: 0 };
  let dbError: string | null = null;

  try {
    stats = await dashboardStats();
  } catch (e) {
    dbError = (e as Error).message;
  }

  return (
    <div className="container page">
      {dbError && (
        <div className="alert alert-err">
          Base de datos no configurada: {dbError}. Ejecuta <code>npm run setup:db</code> o el SQL en Supabase.
        </div>
      )}

      <div className="hero-grid">
        <div>
          <div className="mono muted">// sistema v1.0 · visión artificial</div>
          <h1 className="display hero-title">
            Baches<br />
            <span className="muted" style={{ fontStyle: 'italic' }}>detectados</span>
            <br />
            automáticamente.
          </h1>
        </div>
        <div className="hero-side">
          <p style={{ fontSize: '.9rem', lineHeight: 1.6 }}>
            Sube una imagen de una vía urbana y el sistema analiza el pavimento para identificar
            y clasificar baches por severidad. Datos en Supabase (PostgreSQL).
          </p>
          <div style={{ display: 'flex', gap: '.5rem', marginTop: '1.5rem', flexWrap: 'wrap' }}>
            <Link href="/detecciones" className="btn btn-primary">Escanear ahora →</Link>
            <Link href="/vias" className="btn">Gestionar vías</Link>
          </div>
        </div>
      </div>

      <section className="stats">
        <div className="stat"><div className="mono muted">Detecciones</div><div className="display stat-value">{stats.detecciones}</div></div>
        <div className="stat"><div className="mono muted">Baches totales</div><div className="display stat-value">{stats.baches}</div></div>
        <div className="stat"><div className="mono muted">Casos críticos</div><div className="display stat-value">{stats.criticos}</div></div>
        <div className="stat"><div className="mono muted">Confianza media</div><div className="display stat-value">{stats.confianza_media}%</div></div>
      </section>

      <section className="modules">
        <Link href="/vias" className="module-card">
          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '2rem' }}>
            <span className="mono">01</span>
            <span className="mono">{stats.vias} registros</span>
          </div>
          <div className="display" style={{ fontSize: '1.75rem', marginBottom: '.75rem' }}>Módulo CRUD — Vías</div>
          <p style={{ fontSize: '.85rem', opacity: .85, marginBottom: '1.5rem' }}>Registra, edita y elimina las vías urbanas monitoreadas.</p>
          <span className="mono">Abrir módulo →</span>
        </Link>
        <Link href="/detecciones" className="module-card">
          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '2rem' }}>
            <span className="mono">02</span>
            <span className="mono">{stats.detecciones} registros</span>
          </div>
          <div className="display" style={{ fontSize: '1.75rem', marginBottom: '.75rem' }}>Módulo CRUD — Detecciones</div>
          <p style={{ fontSize: '.85rem', opacity: .85, marginBottom: '1.5rem' }}>Sube imágenes, ejecuta escaneo y administra resultados.</p>
          <span className="mono">Abrir módulo →</span>
        </Link>
      </section>
    </div>
  );
}
