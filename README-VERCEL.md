# Pothole Scan — Vercel + Supabase

Versión Next.js del sistema PHP original. Misma UI, mismos módulos (Dashboard, Vías, Detecciones) y mismas APIs REST.

## Requisitos

- Cuenta [Vercel](https://vercel.com)
- Proyecto [Supabase](https://supabase.com) con las variables `SUPABASEV3_*`

## 1. Base de datos Supabase

Opción A — script automático (local):

```bash
npm install
npm run setup:db
```

Opción B — SQL manual: copia `sql/schema-supabase.sql` en **Supabase → SQL Editor → Run**.

## 2. Variables de entorno

Copia `.env.example` a `.env.local` y completa los valores de tu proyecto Supabase.

En **Vercel → Project → Settings → Environment Variables**, agrega:

| Variable | Uso |
|----------|-----|
| `NEXT_PUBLIC_SUPABASEV3_SUPABASE_URL` | URL del proyecto |
| `NEXT_PUBLIC_SUPABASEV3_SUPABASE_PUBLISHABLE_KEY` | Clave pública |
| `SUPABASEV3_SUPABASE_ANON_KEY` | Clave anon (servidor) |
| `SUPABASEV3_SUPABASE_SERVICE_ROLE_KEY` | Escritura en BD y Storage |
| `PS_SCANNER_URL` | URL del scanner YOLO remoto (`api_server.py`) |

> **Vercel no ejecuta Python.** Para escaneo real configura `PS_SCANNER_URL` apuntando a tu `scanner/api_server.py` (PythonAnywhere, VPS o ngrok). Alternativa: `PS_LOVABLE_API_KEY`.

## 3. Despliegue en Vercel

```bash
npm install
npm run build
```

Sube el repo a GitHub y conéctalo en Vercel, o usa CLI:

```bash
npx vercel --prod
```

## APIs (igual que PHP)

| Endpoint | Descripción |
|----------|-------------|
| `POST /api/upload` | Subir imagen → Supabase Storage |
| `POST /api/scan` | Escanear por `image_url` |
| `GET/POST/DELETE /api/detecciones` | CRUD detecciones |
| `GET/POST/PUT/DELETE /api/vias` | CRUD vías |

## Scanner YOLO remoto

En tu PC o servidor con Python:

```bash
cd scanner
pip install -r requirements.txt
python api_server.py
```

En Vercel:

```
PS_SCANNER_URL=https://tu-servidor/scan
```

La versión PHP/XAMPP sigue disponible en los archivos `.php` originales.
