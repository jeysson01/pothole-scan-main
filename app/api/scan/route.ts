import { NextRequest } from 'next/server';
import { scanImage } from '@/lib/scan';
import { corsHeaders, jsonResponse } from '@/lib/utils';

export async function OPTIONS() {
  return new Response(null, { headers: corsHeaders() });
}

export async function POST(req: NextRequest) {
  try {
    const input = await req.json();
    const image_url = String(input.image_url ?? '').trim();
    if (!image_url) return jsonResponse({ ok: false, error: 'image_url requerido' }, 400);
    const data = await scanImage(image_url);
    return jsonResponse({ ok: true, data });
  } catch (e) {
    return jsonResponse({ ok: false, error: (e as Error).message }, 500);
  }
}
