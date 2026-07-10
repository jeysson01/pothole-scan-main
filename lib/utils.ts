export function uuid(): string {
  return crypto.randomUUID();
}

export function jsonResponse(data: unknown, status = 200): Response {
  return new Response(JSON.stringify(data), {
    status,
    headers: {
      'Content-Type': 'application/json; charset=utf-8',
      'Access-Control-Allow-Origin': '*',
    },
  });
}

export function corsHeaders(): HeadersInit {
  return {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type',
  };
}

export type ScanResult = {
  cantidad_baches: number;
  severidad: 'baja' | 'media' | 'alta' | 'critica';
  confianza: number;
  analisis_ia: string;
  annotated_url?: string;
};

export function normalizeScanResult(parsed: Record<string, unknown>): ScanResult {
  const sev = parsed.severidad as string;
  const severidad = ['baja', 'media', 'alta', 'critica'].includes(sev)
    ? (sev as ScanResult['severidad'])
    : 'media';

  const out: ScanResult = {
    cantidad_baches: Math.max(0, Number(parsed.cantidad_baches ?? 0)),
    severidad,
    confianza: Math.max(0, Math.min(100, Number(parsed.confianza ?? 0))),
    analisis_ia: String(parsed.analisis_ia ?? 'Sin análisis').slice(0, 500),
  };
  if (parsed.annotated_url) out.annotated_url = String(parsed.annotated_url);
  return out;
}
