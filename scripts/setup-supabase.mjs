import { readFileSync, existsSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import pg from 'pg';

const __dirname = dirname(fileURLToPath(import.meta.url));
const envPath = join(__dirname, '../.env.local');
if (existsSync(envPath)) {
  for (const raw of readFileSync(envPath, 'utf8').split('\n')) {
    const line = raw.replace(/\r$/, '').trim();
    if (!line || line.startsWith('#')) continue;
    const m = line.match(/^([A-Z0-9_]+)=(.*)$/);
    if (m && !process.env[m[1]]) process.env[m[1]] = m[2].replace(/^["']|["']$/g, '');
  }
}
const sql = readFileSync(join(__dirname, '../sql/schema-supabase.sql'), 'utf8');
const url = process.env.SUPABASEV3_POSTGRES_URL_NON_POOLING || process.env.SUPABASEV3_POSTGRES_URL;

if (!url) {
  console.error('Falta SUPABASEV3_POSTGRES_URL_NON_POOLING o SUPABASEV3_POSTGRES_URL en .env.local');
  process.exit(1);
}

const client = new pg.Client({
  connectionString: url,
  ssl: { rejectUnauthorized: false },
});
process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';
await client.connect();
try {
  await client.query(sql);
  console.log('✓ Tablas y bucket creados en Supabase');
} finally {
  await client.end();
}
