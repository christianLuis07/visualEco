# Fitur Selanjutnya — Self-Hosted Machine Learning Service (Visueco AI)

> Dokumen perencanaan. Berisi cetak biru lengkap fitur ML yang dikerjakan menggantikan
> ketergantungan pada API AI pihak ketiga. Dipakai sebagai acuan implementasi.

**Tanggal Dibuat:** 15 Juni 2026
**Status:** Sedang dikerjakan (Tahap 5.1)

---

## Latar Belakang Masalah

Sebelum fitur ini, `AI_ECOSORT_ENDPOINT` menunjuk ke URL fiktif (`ai-ecosort.local`). Fitur scan
hanya lolos di test karena `Http::fake()` — di browser sungguhan selalu gagal `ConnectionException`.

**Keinginan user:** ML harus kita-handle sendiri (self-hosted), tanpa pihak ketiga.

---

## Arsitektur Solusi

Tambah container ke-4 (`visueco-ml`) yang menjalankan microservice Python untuk klasifikasi
gambar sampah. Laravel memanggilnya lewat HTTP internal di `visueco-network`.

```
┌─────────────┐   ┌───────────────┐   ┌──────────────┐
│ visueco-web │──▶│ visueco-app   │──▶│ visueco-db   │
│ Nginx       │   │ PHP 8.2-FPM   │   │ MySQL 8.0    │
└─────────────┘   └───────┬───────┘   └──────────────┘
                          │ HTTP POST /predict
                          ▼
                  ┌────────────────┐
                  │ visueco-ml     │
                  │ FastAPI +      │
                  │ TensorFlow CPU │
                  │ MobileNetV2    │
                  │ Port 8001      │
                  └────────────────┘
```

### Stack ML

- **Base image:** `python:3.11-slim` (~500 MB final)
- **Framework:** FastAPI + Uvicorn (async, auto Swagger di `/docs`)
- **Model:** MobileNetV2 pre-trained ImageNet (CPU-only, ~14 MB, ~100-300ms/gambar, tanpa GPU)
- **Image lib:** Pillow

---

## Strategi Klasifikasi (ImageNet → 5 Kategori)

MobileNetV2 mengeluarkan 1000 kelas ImageNet. Ambil top-5 prediksi, cari label pertama yang
cocok dengan keyword map ke 5 kategori internal:

| category_id | Kategori | Contoh label ImageNet | is_recyclable |
|-------------|----------|----------------------|---------------|
| 1 | Plastik | water_bottle, pop_bottle, plastic_bag, packet | true |
| 2 | Kertas | carton, envelope, notebook, paper_towel, book_jacket | true |
| 3 | Logam | tin_can, beer_can, soda_can, nail, can_opener | true |
| 4 | Kaca | wine_bottle, beer_glass, goblet, vase | true |
| 5 | Organik | banana, orange, apple, corn, broccoli (food classes) | false |

**Fallback:** jika top-5 tidak ada yang cocok → kembalikan `is_recyclable: false` agar gerbang
anti-fraud di `ScanController` (`confidence < 0.60 || !is_recyclable`) menolaknya secara alami.
Tidak pernah melempar error ke user.

---

## Kontrak Response (WAJIB sama dengan `AiPredictorService::mapResponse()`)

```json
{
  "detected_item": "water bottle",
  "category_id": 1,
  "category_name": "Plastik",
  "type_detail": "water_bottle",
  "confidence_score": 0.87,
  "is_recyclable": true,
  "instructions": ["Kosongkan isi wadah", "Lepaskan label jika memungkinkan", "..."]
}
```

Karena kunci JSON identik, **`AiPredictorService.php` dan `ScanController.php` TIDAK perlu diubah**.
Cukup ubah endpoint di `.env`.

---

## Daftar File yang Akan Dibuat / Diubah

| Aksi | Path | Keterangan |
|------|------|------------|
| Buat | `ml-service/requirements.txt` | Pin deps: tensorflow-cpu, fastapi, uvicorn, pillow, python-multipart |
| Buat | `ml-service/category_map.py` | Keyword map ImageNet → 5 kategori + instructions per kategori |
| Buat | `ml-service/app.py` | FastAPI: endpoint `POST /predict` + `GET /health` |
| Buat | `ml-service/Dockerfile` | python:3.11-slim, install deps, jalankan uvicorn |
| Buat | `.dockerignore` | Exclude node_modules, vendor, .git, public/build |
| Ubah | `docker-compose.yml` | Tambah service `visueco-ml` di visueco-network |
| Ubah | `.env.example` | `AI_ECOSORT_ENDPOINT=http://visueco-ml:8001/predict` |
| Ubah | `README.md` | Tambah langkah `npm install && npm run build`, info container ML |
| Buat | `ringkasan-perubahan/5.1.md` | Dokumentasi final tahap ini |

---

## Sudah Selesai Dikerjakan (Prasyarat)

- [x] **Node.js 20 ditambahkan ke `Dockerfile`** (visueco-app) — via NodeSource setup_20.x
- [x] **Container di-rebuild** — `node v20.20.2` terverifikasi
- [x] **`npm install` + `npm run build`** — Tailwind tercompile ke `public/build/` (CSS 59.82 kB)
- [x] **Test suite tetap hijau** — 15 passed, 89 assertions

---

## Langkah Verifikasi Akhir (setelah ML jadi)

1. `docker compose up -d --build` — 4 container jalan (app, web, db, ml)
2. `docker exec visueco-app php artisan migrate --seed`
3. Buka `http://localhost:8000/login` — halaman ber-styling (Tailwind OK)
4. Login `warga@visueco.test` / `password` → Dashboard → upload foto botol plastik
5. ML klasifikasi → poin bertambah real-time
6. `docker exec visueco-app php artisan test` — 15 test tetap pass

---

## Catatan Penting

- Container ML pertama kali start akan **download bobot MobileNetV2** (~14 MB) — perlu internet
  saat build/first run. Bisa di-bake ke image agar offline-ready.
- Endpoint AI dibaca dari `config/services.php` → `services.ai_ecosort.endpoint` (sudah ada,
  tidak perlu diubah).
- Test memakai `Http::fake()` sehingga tidak menyentuh ML container sungguhan — aman.
