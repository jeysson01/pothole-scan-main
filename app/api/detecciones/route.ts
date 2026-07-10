import { NextRequest } from 'next/server';
import { listDetecciones } from '@/lib/db';
import { createServerClient } from '@/lib/supabase';
import { corsHeaders, jsonResponse, uuid } from '@/lib/utils';

export async function OPTIONS() {
  return new Response(null, { headers: corsHeaders() });
}

export async function GET() {
  try {
    return jsonResponse({ ok: true, data: await listDetecciones() });
  } catch (e) {
    return jsonResponse({ ok: false, error: (e as Error).message }, 500);
  }
}

export async function POST(req: NextRequest) {
  try {
    const input = await req.json();
    const id = uuid();
    const { error } = await createServerClient().from('detecciones').insert({
      id,
      via_id: input.via_id || null,
      image_url: String(input.image_url ?? '').trim(),
      annotated_url: input.annotated_url ?? null,
      severidad: input.severidad ?? 'media',
      confianza: Number(input.confianza ?? 0),
      cantidad_baches: Number(input.cantidad_baches ?? 0),
      analisis_ia: input.analisis_ia ?? null,
      ubicacion: input.ubicacion || null,
      fecha_deteccion: input.fecha_deteccion ?? new Date().toISOString(),
    });
    if (error) throw new Error(error.message);
    return jsonResponse({ ok: true, id });
  } catch (e) {
    return jsonResponse({ ok: false, error: (e as Error).message }, 500);
  }
}

export async function DELETE(req: NextRequest) {
  try {
    const input = await req.json().catch(() => ({}));
    const url = new URL(req.url);
    const id = input.id || url.searchParams.get('id') || '';
    const { error } = await createServerClient().from('detecciones').delete().eq('id', id);
    if (error) throw new Error(error.message);
    return jsonResponse({ ok: true });
  } catch (e) {
    return jsonResponse({ ok: false, error: (e as Error).message }, 500);
  }
}
