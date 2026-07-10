import { NextRequest } from 'next/server';
import { uploadImage } from '@/lib/scan';
import { corsHeaders, jsonResponse } from '@/lib/utils';

export async function OPTIONS() {
  return new Response(null, { headers: corsHeaders() });
}

export async function POST(req: NextRequest) {
  try {
    const form = await req.formData();
    const file = form.get('image');
    if (!file || !(file instanceof File)) {
      return jsonResponse({ ok: false, error: 'Campo image requerido' }, 400);
    }
    const image_url = await uploadImage(file);
    return jsonResponse({ ok: true, image_url });
  } catch (e) {
    return jsonResponse({ ok: false, error: (e as Error).message }, 500);
  }
}
