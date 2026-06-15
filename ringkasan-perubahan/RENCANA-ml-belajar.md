# RENCANA — ML Manual yang Belajar dari Tiap Upload (Tahap 6.0)

> Dokumen rencana untuk divalidasi user SEBELUM implementasi.
> Status: MENUNGGU PERSETUJUAN.

**Tanggal:** 15 Juni 2026

---

## 1. Diagnosis Bug "Objek Tidak Dikenali"

**Bukti reproduksi** (foto `error/bug-objek-tidak-dikenali.png` dijalankan langsung di container ML):

```
Crop area foto sampah → prediksi MobileNetV2:
0.624  gasmask        ← tertinggi, TIDAK ada di keyword map
0.185  oxygen_mask    ← TIDAK ada di keyword map
0.022  hamper
0.020  screw          ← Logam (tapi skor cuma 0.02)
0.008  water_bottle   ← Plastik (skor sangat rendah)
```

**Akar masalah:** MobileNetV2 dilatih ImageNet untuk mengenali **satu objek bersih dominan**.
Foto tumpukan sampah campuran (botol + kaleng + plastik sekaligus) membuat confidence
tersebar dan jatuh ke kelas asing (`gasmask`). Lalu gerbang anti-fraud
(`!is_recyclable || confidence < 0.60`) menolaknya → "Objek tidak dikenali".

**Kesimpulan:** ini keterbatasan model generik, bukan bug kode. Solusinya: model yang
**belajar dari data sampah asli yang di-upload warga**.

---

## 2. Keputusan Desain (sudah dikonfirmasi user)

| Aspek | Pilihan |
|-------|---------|
| Sumber label | **User konfirmasi hasil** — setelah scan, user lihat tebakan & konfirmasi benar / koreksi kategori |
| Metode belajar | **Fine-tune berkala** — latih ulang head classifier saat data cukup terkumpul |
| Third party | **Dihapus semua** (AWS/SES/Postmark/Resend/Slack/S3) — murni self-hosted |

---

## 3. Arsitektur Pembelajaran

### Alur Data
```
1. User upload foto → ML /predict → tebakan kategori + confidence
2. Frontend tampilkan tebakan + tombol: [Benar] atau [Pilih kategori yang tepat]
3. User konfirmasi → tersimpan sebagai TRAINING SAMPLE (gambar + label benar)
4. Sampel menumpuk di storage + tabel training_samples
5. Saat jumlah sampel baru >= threshold (mis. 20) ATAU admin klik "Latih Model":
   → ML service fine-tune head classifier MobileNetV2 pakai semua sampel
   → simpan bobot baru (model_vX.keras)
6. /predict berikutnya pakai model hasil latih → makin akurat untuk sampah asli
```

### Model: Transfer Learning
- **Base MobileNetV2 (beku)** sebagai feature extractor — tetap dipakai
- **Head baru** = Dense layer → 5 output (Plastik/Kertas/Logam/Kaca/Organik)
- Hanya head yang dilatih (cepat di CPU, butuh sedikit data)
- Jika head terlatih belum ada → fallback ke keyword-map ImageNet lama (perilaku sekarang)

---

## 4. Komponen yang Dibangun

### A. Database (Laravel migration)
- Tabel baru `training_samples`:
  - `id` (UUID), `user_id`, `image_path`, `waste_category_id` (label benar),
    `predicted_category_id`, `confidence_score`, `is_confirmed`, `used_in_training` (bool),
    `created_at`
- Tabel baru `model_versions`:
  - `id`, `version`, `sample_count`, `accuracy`, `trained_at`, `is_active`

### B. ML Service (Python, diperluas)
- `POST /predict` — sekarang muat head terlatih jika ada, else keyword-map
- `POST /train` — terima daftar (image_path, label) → fine-tune head → simpan bobot + return akurasi
- `GET /model/info` — versi model aktif, jumlah sampel, akurasi
- File: `trainer.py` (logika fine-tune), `model_store/` (bobot per versi)
- Volume Docker `visueco-ml-data` untuk persistensi bobot & dataset

### C. Laravel Backend
- `POST /api/v1/scan/confirm` — user konfirmasi/koreksi hasil → buat training_sample
- `app/Services/ModelTrainerService.php` — panggil ML `/train`, catat model_versions
- `app/Http/Controllers/Api/v1/ScanConfirmController.php`
- Admin: `POST /admin/model/train` + tampilan status model di dashboard admin
- Modifikasi `ScanController` — simpan predicted_category sementara untuk dikonfirmasi

### D. Frontend
- `dashboard.blade.php` — setelah hasil scan muncul, tambah blok konfirmasi:
  "Apakah ini **Plastik**? [Ya, benar] [Bukan, pilih: dropdown 5 kategori]"
- Kirim konfirmasi via AJAX ke `/api/v1/scan/confirm`
- Admin dashboard — kartu "Status Model AI": versi, jumlah sampel, tombol "Latih Ulang Model"

### E. Hapus Third Party (sesuai permintaan)
- `config/services.php` — hapus blok `postmark`, `resend`, `ses`, `slack`
- `config/filesystems.php` — hapus disk `s3`
- `.env.example` & `.env` — hapus `AWS_*`
- Pastikan `FILESYSTEM_DISK=public` / `local` (murni lokal)

---

## 5. Daftar File

| Aksi | Path |
|------|------|
| Buat | `database/migrations/xxxx_create_training_samples_table.php` |
| Buat | `database/migrations/xxxx_create_model_versions_table.php` |
| Buat | `app/Models/TrainingSample.php` |
| Buat | `app/Models/ModelVersion.php` |
| Buat | `app/Http/Controllers/Api/v1/ScanConfirmController.php` |
| Buat | `app/Services/ModelTrainerService.php` |
| Buat | `app/Http/Controllers/Web/Admin/ModelController.php` |
| Ubah | `app/Http/Controllers/Api/v1/ScanController.php` (simpan prediksi utk konfirmasi) |
| Ubah | `app/Services/AiPredictorService.php` (tambah method train + modelInfo) |
| Ubah | `routes/api.php` (route confirm) |
| Ubah | `routes/web.php` (route admin train) |
| Ubah | `resources/views/dashboard.blade.php` (blok konfirmasi) |
| Ubah | `resources/views/admin/dashboard.blade.php` (kartu status model) |
| Ubah | `ml-service/app.py` (+/train, +/model/info, muat head terlatih) |
| Buat | `ml-service/trainer.py` (logika fine-tune) |
| Ubah | `ml-service/Dockerfile` (siapkan model_store, scikit deps) |
| Ubah | `docker-compose.yml` (volume visueco-ml-data) |
| Hapus | blok AWS/SES/Postmark/Resend/Slack di config + .env |
| Buat | `ringkasan-perubahan/6.0.md` (dokumentasi final) |

---

## 6. Perbaikan Cepat Bug (bisa langsung, sebelum fitur belajar)

Agar foto sampah campuran tidak langsung ditolak:
- Turunkan/atur ulang gerbang: jika top-5 ada kategori daur-ulang dengan skor rendah,
  tetap terima dengan confidence apa adanya (poin proporsional), JANGAN langsung tolak.
- Atau: ambil **jumlah kategori daur ulang** yang terdeteksi di top-5, bukan cuma top-1.

> Catatan: ini opsional. Setelah model belajar dari data asli, akurasi naik sendiri.

---

## 7. Urutan Implementasi

1. **Hapus third party** (cepat, bersih-bersih dulu)
2. **Migration + Model** training_samples & model_versions
3. **ML service**: tambah /train, /model/info, muat head terlatih, trainer.py
4. **Laravel**: confirm endpoint + ModelTrainerService
5. **Frontend**: blok konfirmasi di dashboard + kartu model di admin
6. **Quick-fix gerbang** anti-fraud (opsional)
7. **Test** — pastikan 15 test lama tetap hijau + test baru utk confirm
8. **Ringkasan 6.0.md + commit**

---

## 8. Risiko / Catatan

- **Cold start**: sebelum ada data latih, akurasi = MobileNetV2 generik (seperti sekarang).
  Model membaik setelah warga mengonfirmasi puluhan foto sampah asli.
- **CPU training**: fine-tune head saja (~beberapa detik–menit untuk ratusan sampel). Berat
  jika dataset ribuan — bisa dijalankan async/queue.
- **Persistensi**: bobot model & dataset wajib di named volume agar tidak hilang saat container down.
- **Test hermetik**: test scan tetap pakai Http::fake, tidak menyentuh ML asli.
