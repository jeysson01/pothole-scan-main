import { createServerClient } from './supabase';

export type Via = {
  id: string;
  nombre: string;
  ciudad: string;
  tipo: string;
  longitud_km: number;
  descripcion: string | null;
  created_at: string;
  updated_at: string;
};

export type Deteccion = {
  id: string;
  via_id: string | null;
  image_url: string;
  annotated_url: string | null;
  severidad: string;
  confianza: number;
  cantidad_baches: number;
  analisis_ia: string | null;
  ubicacion: string | null;
  fecha_deteccion: string;
  created_at: string;
  via_nombre?: string;
  via_ciudad?: string;
};

export async function listVias(): Promise<Via[]> {
  const { data, error } = await createServerClient().from('vias').select('*').order('created_at', { ascending: false });
  if (error) throw new Error(error.message);
  return data ?? [];
}

export async function listDetecciones(): Promise<Deteccion[]> {
  const { data, error } = await createServerClient()
    .from('detecciones')
    .select('*, vias(nombre, ciudad)')
    .order('created_at', { ascending: false });
  if (error) throw new Error(error.message);
  return (data ?? []).map((d) => {
    const v = d.vias as { nombre: string; ciudad: string } | null;
    return {
      ...d,
      via_nombre: v?.nombre,
      via_ciudad: v?.ciudad,
      vias: undefined,
    };
  });
}

export async function dashboardStats() {
  const supabase = createServerClient();
  const [dets, vias] = await Promise.all([
    supabase.from('detecciones').select('cantidad_baches, severidad, confianza'),
    supabase.from('vias').select('id', { count: 'exact', head: true }),
  ]);
  if (dets.error) throw new Error(dets.error.message);
  if (vias.error) throw new Error(vias.error.message);

  const rows = dets.data ?? [];
  const total = rows.length;
  const baches = rows.reduce((s, r) => s + (r.cantidad_baches ?? 0), 0);
  const criticos = rows.filter((r) => r.severidad === 'alta' || r.severidad === 'critica').length;
  const avg = total ? rows.reduce((s, r) => s + Number(r.confianza ?? 0), 0) / total : 0;

  return {
    detecciones: total,
    baches,
    criticos,
    confianza_media: total ? Math.round(avg) : 0,
    vias: vias.count ?? 0,
  };
}
