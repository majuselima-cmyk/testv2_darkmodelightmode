# Trading System - Nuxt.js + Supabase

Aplikasi Trading Management System yang di-convert dari PHP ke Nuxt.js 3 + Supabase.

## Setup

### 1. Install Dependencies

```bash
npm install
```

### 2. Setup Supabase Database

1. Buat project baru di [Supabase](https://supabase.com)
2. Buka SQL Editor di Supabase Dashboard
3. Jalankan file `supabase-migration.sql` untuk membuat semua tables:
   - `trading_positions`
   - `ea_control`
   - `lot_sizes`

### 3. Setup Environment Variables

1. Copy file `.env.example` menjadi `.env`
2. Edit file `.env` dan isi dengan credentials Supabase kamu:
```
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_ANON_KEY=your_anon_key_here
SUPABASE_SERVICE_KEY=your_service_key_here
API_TOKEN=abc321Xyz
DEFAULT_ACCOUNT=206943771
```

Ambil credentials dari Settings > API di Supabase Dashboard.

### 4. Run Development Server

```bash
npm run dev
```

Buka [http://localhost:3000](http://localhost:3000) di browser kamu.

## Fitur

- ✅ Dashboard - Overview status EA & schedules
- ✅ Lot Management - Tracking lot aktif per schedule (martingale)
- ✅ EA Control - ON/OFF EA per schedule (S1-S9, SX)
- ✅ Pendapatan - Grafik pendapatan harian
- ✅ Admin Lot Sizes - CRUD untuk lot sizes
- ✅ History API - Sync/get trading positions
- ✅ Signal API - Generate sinyal trading

## API Endpoints

- `/api/lot` - Get lot size per schedule
- `/api/control` - EA control (get/set)
- `/api/history` - Sync/get positions

## Database Tables

1. **trading_positions** - Trading positions data
2. **ea_control** - EA control per schedule
3. **lot_sizes** - Lot sizes management

## Tech Stack

- **Nuxt.js 3** - Vue.js framework
- **Supabase** - Backend as a Service (PostgreSQL)
- **Chart.js** - Charts untuk grafik pendapatan
- **TypeScript** - Type safety

## Struktur Project

```
├── pages/              # Halaman aplikasi
├── components/         # Vue components
├── composables/        # Composables untuk reusable logic
├── server/             # API routes
│   └── api/           # API endpoints
├── assets/            # Static assets
└── supabase-migration.sql  # Database migration
```

## Deployment ke Vercel

### 1. Push ke GitHub
Pastikan semua kode sudah di-push ke repository GitHub kamu.

### 2. Deploy di Vercel
1. Masuk ke [Vercel Dashboard](https://vercel.com)
2. Klik **"New Project"**
3. Import repository GitHub kamu (`testv1`)
4. Vercel akan otomatis detect Nuxt.js dan mengatur konfigurasi

### 3. Setup Environment Variables
**PENTING:** Set environment variables di Vercel Dashboard **SEBELUM** deploy pertama kali!

**Cara Set Environment Variables di Vercel:**
1. Buka project di [Vercel Dashboard](https://vercel.com)
2. Klik **Settings** (di menu atas)
3. Klik **Environment Variables** (di sidebar kiri)
4. Tambahkan variables berikut satu per satu:
   
   | Name | Value | Environment |
   |------|-------|-------------|
   | `SUPABASE_URL` | `https://your-project.supabase.co` | Production, Preview, Development |
   | `SUPABASE_ANON_KEY` | `your_anon_key_here` | Production, Preview, Development |
   | `SUPABASE_SERVICE_KEY` | `your_service_key_here` | Production, Preview, Development |
   | `API_TOKEN` | `abc321Xyz` | Production, Preview, Development (opsional) |
   | `DEFAULT_ACCOUNT` | `206943771` | Production, Preview, Development (opsional) |

5. **WAJIB:** Centang semua environment (Production, Preview, Development)
6. Klik **Save** untuk setiap variable
7. **Setelah semua variables di-set**, redeploy project:
   - Klik tab **Deployments**
   - Klik **...** (three dots) pada deployment terbaru
   - Klik **Redeploy**

**Catatan:** Environment variables dari Supabase bisa didapatkan di Supabase Dashboard → Settings → API

### 4. Deploy
- Vercel akan otomatis build dan deploy
- Setelah deploy selesai, aplikasi akan live di URL yang diberikan Vercel

### 5. Troubleshooting Error 500

Jika dashboard menampilkan "Loading..." atau error 500 pada `/api/control`, kemungkinan penyebab:

#### A. Environment Variables Belum Di-set
**Gejala:** Error 500 dengan message "Missing Supabase environment variables"

**Solusi:**
1. Buka Vercel Dashboard → Project → Settings → Environment Variables
2. Pastikan sudah ada:
   - `SUPABASE_URL`
   - `SUPABASE_ANON_KEY`
   - `SUPABASE_SERVICE_KEY` (opsional)
3. Centang semua environment (Production, Preview, Development)
4. Redeploy project

#### B. Database Tables Belum Dibuat
**Gejala:** Error 500 dengan message "Table 'ea_control' does not exist"

**Solusi:**
1. Buka Supabase Dashboard → SQL Editor
2. Jalankan file `supabase-migration.sql` untuk membuat semua tables:
   - `ea_control`
   - `trading_positions`
   - `lot_sizes`
3. Refresh dashboard setelah tables dibuat

#### C. Cek Log Error di Vercel
1. Buka Vercel Dashboard → Project → Deployments
2. Klik deployment terbaru
3. Klik tab "Functions" → pilih `/api/control`
4. Lihat error log untuk detail error

#### D. Verifikasi Supabase Connection
1. Buka Supabase Dashboard → Settings → API
2. Copy `URL` dan `anon public` key
3. Pastikan sama dengan yang di-set di Vercel Environment Variables
4. Test connection dengan SQL Editor

### Catatan Deployment
- ✅ Build sudah ditest dan berhasil
- ✅ Vercel otomatis detect Nuxt.js 3
- ✅ Server API routes akan berjalan sebagai serverless functions
- ⚠️ **WAJIB** set environment variables sebelum deploy pertama kali
- ⚠️ Pastikan Supabase database sudah setup dan tables sudah dibuat
- ✅ Error handling sudah diperbaiki untuk memberikan error message yang lebih jelas

## Notes

- Pastikan semua tables sudah dibuat di Supabase sebelum menjalankan aplikasi
- Default account bisa diubah di `.env` file atau environment variables di Vercel
- API token untuk keamanan API endpoints

