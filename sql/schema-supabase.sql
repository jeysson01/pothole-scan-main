-- Pothole Scan — Supabase (PostgreSQL)
-- Ejecutar en: Supabase Dashboard → SQL Editor → Run

CREATE TABLE IF NOT EXISTS vias (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  nombre VARCHAR(200) NOT NULL,
  ciudad VARCHAR(120) NOT NULL,
  tipo VARCHAR(50) NOT NULL DEFAULT 'avenida',
  longitud_km DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  descripcion TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_vias_ciudad ON vias (ciudad);

CREATE TABLE IF NOT EXISTS detecciones (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  via_id UUID REFERENCES vias(id) ON DELETE SET NULL,
  image_url VARCHAR(500) NOT NULL,
  annotated_url VARCHAR(500),
  severidad TEXT NOT NULL DEFAULT 'media' CHECK (severidad IN ('baja','media','alta','critica')),
  confianza DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  cantidad_baches INT NOT NULL DEFAULT 0,
  analisis_ia TEXT,
  ubicacion VARCHAR(255),
  fecha_deteccion TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_detecciones_fecha ON detecciones (fecha_deteccion);
CREATE INDEX IF NOT EXISTS idx_detecciones_severidad ON detecciones (severidad);

-- Bucket de imágenes (público para lectura)
INSERT INTO storage.buckets (id, name, public)
VALUES ('baches', 'baches', true)
ON CONFLICT (id) DO NOTHING;

CREATE POLICY "Lectura pública baches" ON storage.objects
  FOR SELECT USING (bucket_id = 'baches');

CREATE POLICY "Subida baches" ON storage.objects
  FOR INSERT WITH CHECK (bucket_id = 'baches');

CREATE POLICY "Actualizar baches" ON storage.objects
  FOR UPDATE USING (bucket_id = 'baches');

CREATE POLICY "Eliminar baches" ON storage.objects
  FOR DELETE USING (bucket_id = 'baches');

-- Datos de ejemplo
INSERT INTO vias (nombre, ciudad, tipo, longitud_km, descripcion)
SELECT * FROM (VALUES
  ('Av. Reforma', 'Ciudad de México', 'avenida', 12.5, 'Eje central poniente'),
  ('Calle 72', 'Bogotá', 'calle', 3.2, 'Sector Chapinero'),
  ('Autopista Norte', 'Medellín', 'autopista', 28.0, 'Tramo norte')
) AS t(nombre, ciudad, tipo, longitud_km, descripcion)
WHERE NOT EXISTS (SELECT 1 FROM vias LIMIT 1);
