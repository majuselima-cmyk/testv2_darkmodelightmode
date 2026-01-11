# Troubleshooting Environment Variables di Vercel

## Masalah: Environment Variables Sudah Di-Set Tapi Masih Error

Jika kamu sudah set environment variables di Vercel tapi masih muncul error, cek berikut:

### 1. **WAJIB REDEPLOY Setelah Set Environment Variables** ⚠️

Environment variables hanya berlaku untuk deployment baru. Jadi **WAJIB redeploy** setelah set variables.

**Cara Redeploy:**
1. Buka Vercel Dashboard → Project kamu
2. Klik tab **Deployments**
3. Klik **...** (three dots) pada deployment terbaru
4. Klik **Redeploy**
5. Tunggu deployment selesai (1-2 menit)

### 2. **Format SUPABASE_ANON_KEY Harus Benar** 

**Format yang BENAR:**
- Anon key biasanya JWT token yang PANJANG (200+ karakter)
- Dimulai dengan `eyJ...`
- Contoh: `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InlvdXItcHJvamVjdC1pZCIsInJvbGUiOiJhbm9uIiwiaWF0IjoxNjk4NzY1NDMyLCJleHAiOjIwMTQzNDE0MzJ9...`

**Format yang SALAH:**
- `sb_publishable_...` ❌ (ini bukan format Supabase standard)
- Key yang pendek (< 100 karakter) ❌

**Cara Ambil Key yang Benar:**
1. Buka Supabase Dashboard → **Settings** → **API**
2. Di section **Project API keys**, ambil yang **"anon public"** (bukan service_role)
3. Copy seluruh key (biasanya panjang sekali)

### 3. **Pastikan Environment Variables Enabled untuk Semua Environment**

Di Vercel, saat set environment variable, **WAJIB centang**:
- ✅ Production
- ✅ Preview  
- ✅ Development

Jika tidak dicentang, variable tidak akan tersedia di environment tersebut.

### 4. **Check Vercel Function Logs untuk Debugging**

Setelah redeploy, cek logs untuk melihat apakah environment variables sudah terbaca:

1. Buka Vercel Dashboard → Project → **Deployments**
2. Klik deployment terbaru
3. Klik tab **Functions**
4. Klik **`/api/control`**
5. Lihat logs - akan muncul debug info tentang environment variables

### 5. **Verifikasi Environment Variables di Vercel**

Pastikan semua variables sudah di-set dengan benar:

| Variable Name | Contoh Value | Wajib? |
|--------------|--------------|--------|
| `SUPABASE_URL` | `https://xxxxx.supabase.co` | ✅ WAJIB |
| `SUPABASE_ANON_KEY` | `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...` | ✅ WAJIB |
| `SUPABASE_SERVICE_KEY` | `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...` | ⚠️ Opsional |
| `API_TOKEN` | `abc321Xyz` | ⚠️ Opsional (default sudah ada) |
| `DEFAULT_ACCOUNT` | `270787386` | ⚠️ Opsional (default sudah ada) |

### 6. **Test API Langsung**

Setelah redeploy, test API langsung:
```
https://testv1-rho.vercel.app/api/control?token=abc321Xyz&account=270787386&action=get
```

Jika masih error 500, cek Vercel function logs untuk detail error.

## Checklist Troubleshooting

- [ ] Environment variables sudah di-set di Vercel Dashboard
- [ ] Format `SUPABASE_ANON_KEY` sudah benar (JWT token panjang)
- [ ] Semua environment (Production, Preview, Development) sudah dicentang
- [ ] Sudah REDEPLOY setelah set environment variables
- [ ] Sudah tunggu deployment selesai
- [ ] Sudah refresh browser (clear cache jika perlu)
- [ ] Sudah cek Vercel function logs untuk debug info

Jika semua sudah dilakukan tapi masih error, cek Vercel function logs untuk detail error message.


