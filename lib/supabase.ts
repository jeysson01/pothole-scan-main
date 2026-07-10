import { createClient, SupabaseClient } from '@supabase/supabase-js';

function env(name: string): string {
  const v = process.env[name];
  if (!v) throw new Error(`Variable de entorno requerida: ${name}`);
  return v;
}

export function getSupabaseUrl(): string {
  return env('NEXT_PUBLIC_SUPABASEV3_SUPABASE_URL');
}

export function getSupabaseAnonKey(): string {
  return (
    process.env.SUPABASEV3_SUPABASE_ANON_KEY ||
    process.env.NEXT_PUBLIC_SUPABASEV3_SUPABASE_PUBLISHABLE_KEY ||
    ''
  );
}

export function createBrowserClient(): SupabaseClient {
  return createClient(getSupabaseUrl(), getSupabaseAnonKey());
}

export function createServerClient(): SupabaseClient {
  const key =
    process.env.SUPABASEV3_SUPABASE_SERVICE_ROLE_KEY ||
    process.env.SUPABASEV3_SUPABASE_ANON_KEY ||
    getSupabaseAnonKey();
  return createClient(getSupabaseUrl(), key, {
    auth: { persistSession: false, autoRefreshToken: false },
  });
}

export const STORAGE_BUCKET = 'baches';
