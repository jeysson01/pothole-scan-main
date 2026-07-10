import { NextRequest } from 'next/server';
import { listVias } from '@/lib/db';
import { createServerClient } from '@/lib/supabase';
import { corsHeaders, jsonResponse, uuid } from '@/lib/utils';

export async function OPTIONS() {
  return new Response(null, { headers: corsHeaders() });
}

export async function GET() {
  try {
    return jsonResponse({ ok: true, data: await listVias() });
  } catch (e) {
    return jsonResponse({ ok: false, error: (e as Error).message }, 500);
  }
}

export async function POST(req: NextRequest) {
  try {
    const input = await req.json();
    const id = uuid();
    const supabase = createServerClient();
    const { error } = await supabase.from('vias').insert({
      id,
      nombre: String(input.nombre ?? '').trim(),
      ciudad: String(input.ciudad ?? '').trim(),
      tipo: String(input.tipo ?? 'avenida').trim(),
      longitud_km: Number(input.longitud_km ?? 0),
      descripcion: input.descripcion?.trim() || null,
    });
    if (error) throw new Error(error.message);
    const { data } = await supabase.from('vias').select('*').eq('id', id).single();
    return jsonResponse({ ok: true, data });
  } catch (e) {
    return jsonResponse({ ok: false, error: (e as Error).message }, 500);
  }
}

export async function PUT(req: NextRequest) {
  try {
    const input = await req.json();
    const id = input.id;
    if (!id) return jsonResponse({ ok: false, error: 'ID requerido' }, 400);
    const supabase = createServerClient();
    const { error } = await supabase
      .from('vias')
      .update({
        nombre: String(input.nombre ?? '').trim(),
        ciudad: String(input.ciudad ?? '').trim(),
        tipo: String(input.tipo ?? 'avenida').trim(),
        longitud_km: Number(input.longitud_km ?? 0),
        descripcion: input.descripcion?.trim() || null,
      })
      .eq('id', id);
    if (error) throw new Error(error.message);
    const { data } = await supabase.from('vias').select('*').eq('id', id).single();
    return jsonResponse({ ok: true, data });
  } catch (e) {
    return jsonResponse({ ok: false, error: (e as Error).message }, 500);
  }
}

export async function DELETE(req: NextRequest) {
  try {
    const input = await req.json().catch(() => ({}));
    const url = new URL(req.url);
    const id = input.id || url.searchParams.get('id') || '';
    const { error } = await createServerClient().from('vias').delete().eq('id', id);
    if (error) throw new Error(error.message);
    return jsonResponse({ ok: true });
  } catch (e) {
    return jsonResponse({ ok: false, error: (e as Error).message }, 500);
  }
}
