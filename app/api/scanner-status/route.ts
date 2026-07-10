import { scannerStatus } from '@/lib/scan';
import { jsonResponse } from '@/lib/utils';

export async function GET() {
  return jsonResponse(scannerStatus());
}
