import { createServerClient, STORAGE_BUCKET } from './supabase';
import { normalizeScanResult, ScanResult, uuid } from './utils';

const ALLOWED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

export async function uploadImage(file: File): Promise<string> {
  const mime = file.type || 'image/jpeg';
  if (!ALLOWED.includes(mime)) {
    throw new Error('Formato no permitido. Usa JPG, PNG o WebP.');
  }
  if (file.size > 8 * 1024 * 1024) {
    throw new Error('La imagen supera 8 MB');
  }

  const ext = mime === 'image/png' ? 'png' : mime === 'image/webp' ? 'webp' : mime === 'image/gif' ? 'gif' : 'jpg';
  const name = `${uuid()}.${ext}`;
  const supabase = createServerClient();
  const buffer = Buffer.from(await file.arrayBuffer());

  const { error } = await supabase.storage.from(STORAGE_BUCKET).upload(name, buffer, {
    contentType: mime,
    upsert: false,
  });
  if (error) throw new Error(error.message);

  const { data } = supabase.storage.from(STORAGE_BUCKET).getPublicUrl(name);
  return data.publicUrl;
}

export async function uploadBuffer(buffer: Buffer, filename: string, mime = 'image/jpeg'): Promise<string> {
  const supabase = createServerClient();
  const { error } = await supabase.storage.from(STORAGE_BUCKET).upload(filename, buffer, {
    contentType: mime,
    upsert: true,
  });
  if (error) throw new Error(error.message);
  const { data } = supabase.storage.from(STORAGE_BUCKET).getPublicUrl(filename);
  return data.publicUrl;
}

async function fetchImageBuffer(imageUrl: string): Promise<{ buffer: Buffer; mime: string; name: string }> {
  const res = await fetch(imageUrl);
  if (!res.ok) throw new Error('No se pudo descargar la imagen');
  const mime = res.headers.get('content-type') || 'image/jpeg';
  const buffer = Buffer.from(await res.arrayBuffer());
  const name = imageUrl.split('/').pop()?.split('?')[0] || 'image.jpg';
  return { buffer, mime, name };
}

export async function scanImage(imageUrl: string): Promise<ScanResult> {
  const scannerUrl = (process.env.PS_SCANNER_URL || '').trim();
  if (scannerUrl) return scanYoloRemote(imageUrl, scannerUrl);

  const lovableKey = (process.env.PS_LOVABLE_API_KEY || '').trim();
  if (lovableKey) return scanLovable(imageUrl, lovableKey);

  throw new Error(
    'Motor YOLOv8 no disponible en Vercel. Configura PS_SCANNER_URL (api_server.py remoto) o PS_LOVABLE_API_KEY.'
  );
}

async function scanYoloRemote(imageUrl: string, scannerUrl: string): Promise<ScanResult> {
  const { buffer, mime, name } = await fetchImageBuffer(imageUrl);
  let url = scannerUrl.replace(/\/$/, '');
  if (!url.endsWith('/scan')) url += '/scan';

  const form = new FormData();
  form.append('image', new Blob([new Uint8Array(buffer)], { type: mime }), name);

  const res = await fetch(url, { method: 'POST', body: form, signal: AbortSignal.timeout(120000) });
  if (!res.ok) throw new Error(`Scanner remoto no respondió (HTTP ${res.status})`);

  const data = await res.json();
  if (!data?.ok) throw new Error(`Scanner remoto: ${data?.error || 'respuesta inválida'}`);

  if (data.annotated_base64) {
    const base = name.replace(/\.[^.]+$/, '');
    const annotatedName = `${base}_detected.jpg`;
    const annotatedUrl = await uploadBuffer(
      Buffer.from(data.annotated_base64, 'base64'),
      annotatedName,
      'image/jpeg'
    );
    data.annotated_url = annotatedUrl;
  }

  return normalizeScanResult(data);
}

async function scanLovable(imageUrl: string, apiKey: string): Promise<ScanResult> {
  const prompt = `Eres un sistema de visión artificial especializado en detección de baches en vías urbanas.
Analiza la imagen y responde SOLO con un objeto JSON válido (sin markdown) con esta forma:
{"cantidad_baches":number,"severidad":"baja"|"media"|"alta"|"critica","confianza":number,"analisis_ia":string}`;

  const res = await fetch('https://ai.gateway.lovable.dev/v1/chat/completions', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${apiKey}`,
    },
    body: JSON.stringify({
      model: 'google/gemini-2.5-flash',
      messages: [
        {
          role: 'user',
          content: [
            { type: 'text', text: prompt },
            { type: 'image_url', image_url: { url: imageUrl } },
          ],
        },
      ],
    }),
    signal: AbortSignal.timeout(60000),
  });

  if (res.status === 429) throw new Error('Límite de uso IA alcanzado. Intenta más tarde.');
  if (res.status === 402) throw new Error('Sin créditos de IA disponibles.');
  if (!res.ok) throw new Error(`Error IA (${res.status})`);

  const json = await res.json();
  let content = String(json?.choices?.[0]?.message?.content ?? '{}');
  content = content.replace(/```json\s*|\s*```/g, '').trim();
  return normalizeScanResult(JSON.parse(content || '{}'));
}

export function scannerStatus() {
  if ((process.env.PS_SCANNER_URL || '').trim()) {
    return { mode: 'remote', label: 'YOLOv8 remoto', ready: true };
  }
  if ((process.env.PS_LOVABLE_API_KEY || '').trim()) {
    return { mode: 'lovable', label: 'IA Lovable (Gemini)', ready: true };
  }
  return { mode: 'none', label: 'YOLOv8 no configurado', ready: false };
}
