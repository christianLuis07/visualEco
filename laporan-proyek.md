# Draf Pemetaan Laporan Proyek UAS — Visueco

> Dokumen ini adalah **panduan isi** untuk menyusun Laporan Proyek di Microsoft Word.
> Setiap bab dipetakan ke file ringkasan-perubahan yang sudah ada sebagai sumber data.

---

## BAB 1 — PENDAHULUAN

### 1.1 Latar Belakang

Indonesia menghasilkan 68,5 juta ton sampah per tahun (SIPSN, 2023), dengan hanya 7,5% yang berhasil didaur ulang. SDGs Butir 12 (*Responsible Consumption and Production*) menuntut pengelolaan sampah yang berkelanjutan di tingkat komunitas. Namun, tantangan utama di lingkungan RT/RW adalah **minimnya insentif bagi warga untuk memilah sampah** dan **tidak adanya sistem pencatatan transparan** atas kontribusi masing-masing warga.

Visueco hadir sebagai solusi teknologi berupa **aplikasi web audit sampah berbasis AI** yang memungkinkan warga memindai sampah melalui kamera ponsel, mendapatkan poin reward secara real-time, dan menukarkannya dengan hadiah nyata melalui pengurus RT — seluruhnya tercatat dalam buku besar digital yang transparan.

### 1.2 Rumusan Masalah

1. Bagaimana merancang sistem identifikasi sampah otomatis yang mampu mengklasifikasikan jenis sampah melalui analisis gambar?
2. Bagaimana membangun sistem poin dan reward yang aman dari manipulasi (anti-fraud) serta tahan terhadap race condition?
3. Bagaimana menyediakan portal administrasi yang memungkinkan verifikasi fisik voucher secara real-time?

### 1.3 Tujuan

1. Membangun aplikasi web multi-container (Docker) yang dapat dijalankan instan tanpa instalasi runtime tambahan.
2. Mengimplementasikan pipeline scan sampah AI → poin reward → redeem voucher secara end-to-end.
3. Menerapkan standar keamanan OWASP pada seluruh lapisan aplikasi (autentikasi, otorisasi, file upload, race condition).

### 1.4 Metodologi

Specification-Driven Development (SDD) — setiap fitur diawali dengan spesifikasi teknis tertulis, diimplementasikan secara bertahap (11 tahap), dan diverifikasi melalui automated test suite (15 skenario, 89 assertions).

> **Sumber data:** Ringkasan seluruh tahap (1.5 s/d 5.0) di folder `ringkasan-perubahan/`

---

## BAB 2 — ANALISIS SISTEM

### 2.1 Arsitektur Multi-Container Docker

> **Sumber:** `ringkasan-perubahan/1.5.md` bagian header, `Dockerfile`, `docker-compose.yml`

Gambarkan diagram arsitektur tiga container:

```
┌─────────────┐     ┌───────────────┐     ┌──────────────┐
│ visueco-web │────▶│ visueco-app   │────▶│ visueco-db   │
│ Nginx:alpine│     │ PHP 8.2-FPM   │     │ MySQL 8.0    │
│ Port: 8000  │     │ Port: 9000    │     │ Port: 3306   │
└─────────────┘     └───────────────┘     └──────────────┘
```

Jelaskan:
- **Nginx** sebagai reverse proxy yang meneruskan request ke PHP-FPM
- **PHP-FPM** menjalankan Laravel dengan user non-root `www` (UID 1000)
- **MySQL 8.0** dengan named volume `visueco-db-data` untuk persistensi data
- Ketiga container terhubung via `visueco-network` (bridge mode, terisolasi)

### 2.2 Alur State Machine UI — Dashboard Scan

> **Sumber:** `ringkasan-perubahan/3.2.md` bagian "State Machine UI"

```
IDLE ──[file dipilih]──▶ PREVIEW ──[klik Analisis]──▶ LOADING ──▶ SUCCESS
                                                         │            │
                                                         ▼            ▼
                                                       ERROR     Reset input
                                                                 & update poin
```

Jelaskan transisi state: IDLE → PREVIEW → LOADING → SUCCESS/ERROR, elemen UI yang visible/hidden di setiap state.

### 2.3 Alur Bisnis Lengkap

```
Warga scan sampah → AI analisis → Poin masuk → Tukar reward → Voucher dicetak
     │                                              │
     └──── Dashboard (/dashboard) ─────────── Rewards (/rewards)

Pengurus RT verifikasi voucher → Serahkan hadiah → Status completed
     │
     └──── Admin Panel (/admin)
```

---

## BAB 3 — IMPLEMENTASI

### 3.1 Skema Database — ERD Berbasis UUID

> **Sumber:** File migrasi di `database/migrations/`

Gambarkan Entity Relationship Diagram dengan 6 tabel:

| Tabel | Primary Key | Tipe |
|-------|------------|------|
| `users` | UUID | Transaksional |
| `waste_scans` | UUID | Transaksional |
| `point_ledgers` | UUID | Transaksional |
| `reward_redeems` | UUID | Transaksional |
| `waste_categories` | Auto-increment Integer | Master Data |
| `rewards` | Auto-increment Integer | Master Data |

Relasi:
- `users` 1:N `waste_scans` (FK: user_id)
- `users` 1:N `point_ledgers` (FK: user_id)
- `users` 1:N `reward_redeems` (FK: user_id)
- `waste_categories` 1:N `waste_scans` (FK: waste_category_id)
- `rewards` 1:N `reward_redeems` (FK: reward_id)
- `point_ledgers` ←→ morphable (polymorphic ke waste_scans atau reward_redeems)

Jelaskan **mengapa UUID** pada tabel transaksional: mencegah ID enumeration attack dan aman untuk distributed system.

### 3.2 Kontrak Response API

> **Sumber:** `ringkasan-perubahan/1.5.md` dan `ringkasan-perubahan/2.0.md`

#### POST /api/v1/scan — Response 201

```json
{
    "success": true,
    "message": "Scan berhasil diproses dan poin telah ditambahkan.",
    "data": {
        "scan_id": "uuid",
        "detected_item": "Botol Plastik",
        "category_name": "Plastik",
        "confidence_score": 0.92,
        "is_recyclable": true,
        "instructions": ["Kosongkan isi botol", "..."],
        "points_awarded": 10,
        "points_balance": 110
    }
}
```

#### POST /api/v1/redeem — Response 201

```json
{
    "success": true,
    "message": "Penukaran reward berhasil.",
    "data": {
        "redeem_id": "uuid",
        "reward_title": "Voucher Sembako Rp20.000",
        "points_spent": 40,
        "redemption_code": "VSEC-ABCD1234",
        "status": "pending",
        "points_balance": 60
    }
}
```

### 3.3 Implementasi Service Layer — AiPredictorService

> **Sumber:** `ringkasan-perubahan/1.5.md`

Jelaskan pola **Service Layer** yang memisahkan business logic AI dari controller. Service menerima `UploadedFile`, mengirim ke endpoint AI eksternal via `Http::timeout()->attach()->post()`, dan mengembalikan array terstruktur.

---

## BAB 4 — ANALISIS KEAMANAN

### 4.1 Mitigasi Broken Function Level Authorization (OWASP BFLA)

> **Sumber:** `ringkasan-perubahan/2.2.md`

Jelaskan middleware `EnsureUserHasRole`:
- Route admin API dilindungi `middleware('role:admin')` di dalam group `auth:sanctum`
- Route admin web dilindungi `middleware('role:admin')` di dalam group `auth`
- User biasa yang mengakses endpoint admin mendapat 403 Forbidden
- Dual-response: JSON untuk API request, abort(403) untuk web request

### 4.2 File Upload Security — Defense in Depth

> **Sumber:** `ringkasan-perubahan/3.2.md` bagian "Keamanan Upload File"

| Layer | Mekanisme | Tujuan |
|-------|----------|--------|
| 1 — Client | `accept="image/jpeg,image/png"`, JS MIME + size check | UX feedback instan |
| 2 — Server | `required\|image\|mimes:jpeg,png,jpg\|max:4096` | Validasi ketat (cek header bytes) |
| 3 — Storage | `->store('visueco-scans', 'public')` nama hash | Anti path traversal |
| 4 — Business | confidence >= 60% + is_recyclable check | Anti-fraud gaming poin |
| 5 — Output | `escapeHtml()` + `textContent` | XSS prevention |

### 4.3 Race Condition & TOCTOU Prevention

> **Sumber:** `ringkasan-perubahan/4.0.md` bagian "Mekanisme Keamanan Otorisasi State"

Jelaskan dua titik kritis race condition:

**A. Redeem Reward (Tahap 2.0):**
```php
DB::transaction(function () {
    $user = $request->user()->lockForUpdate()->find(...);
    $reward = Reward::lockForUpdate()->findOrFail(...);
    // Cek stok + saldo → decrement atomik
});
```

**B. Complete Voucher (Tahap 4.0):**
```php
DB::transaction(function () {
    $redeem = RewardRedeem::lockForUpdate()->findOrFail($id);
    if ($redeem->status !== 'pending') throw DomainException;
    $redeem->update(['status' => 'completed']);
});
```

Jelaskan mengapa `lockForUpdate()` (pessimistic locking) menghilangkan TOCTOU gap.

### 4.4 Keamanan Autentikasi

> **Sumber:** `ringkasan-perubahan/3.1.md`

- Pesan error generik → anti username enumeration
- CSRF token (`@csrf` + `X-XSRF-TOKEN`) → anti CSRF
- Session regeneration → anti session fixation
- Bcrypt hashing → password storage aman

---

## BAB 5 — PENGUJIAN

### 5.1 Matriks Skenario Pengujian Otomatis

> **Sumber:** `ringkasan-perubahan/1.6.md` dan `ringkasan-perubahan/2.1.md`

| No | File Test | Skenario | Assertion | Status |
|----|-----------|----------|-----------|--------|
| 1 | ScanTrashTest | Scan berhasil → 201, poin bertambah | JSON structure, DB record, points increment | PASS |
| 2 | ScanTrashTest | Request tanpa gambar → 422 | Validation error 'image required' | PASS |
| 3 | ScanTrashTest | Format file tidak valid → 422 | Validation error 'image mimes' | PASS |
| 4 | ScanTrashTest | Ukuran file melebihi batas → 422 | Validation error 'image max' | PASS |
| 5 | ScanTrashTest | Objek non-recyclable → 422 anti-fraud | is_recyclable = false ditolak | PASS |
| 6 | ScanTrashTest | Confidence rendah (< 0.60) → 422 anti-fraud | Low confidence ditolak | PASS |
| 7 | ScanTrashTest | AI service timeout → 503 | ConnectionException → AiServiceException | PASS |
| 8 | ScanTrashTest | AI service error 500 → 503 | Server error → AiServiceException | PASS |
| 9 | ScanTrashTest | User tidak terautentikasi → 401 | Unauthenticated response | PASS |
| 10 | RedeemRewardTest | Redeem berhasil → 201, poin & stok berkurang | JSON structure, DB assertions | PASS |
| 11 | RedeemRewardTest | Poin tidak cukup → 422 | DomainException saldo | PASS |
| 12 | RedeemRewardTest | Stok habis → 422 | DomainException stok | PASS |
| 13 | RedeemRewardTest | User tidak terautentikasi → 401 | Unauthenticated response | PASS |
| 14 | ScanTrashTest | File tersimpan di storage disk 'public' | Storage::disk('public')->assertExists | PASS |
| 15 | ScanTrashTest | PointLedger record terbentuk (morphable) | DB assertion morphable_type + id | PASS |

**Total: 15 tests, 89 assertions — ALL PASSED**

### 5.2 Perintah Eksekusi Test

```bash
docker exec -it visueco-app php artisan test
```

Test berjalan di dalam container `visueco-app` terhadap database `visueco_test` di container `visueco-db` — lingkungan identik dengan production.

---

## BAB 6 — KESIMPULAN

### 6.1 Evaluasi Pencapaian

| Tujuan | Status | Bukti |
|--------|--------|-------|
| Aplikasi multi-container Docker instan | Tercapai | README Quick Start, 3 container running |
| Pipeline scan AI → poin → redeem end-to-end | Tercapai | 15 test scenarios passed |
| Standar keamanan OWASP pada seluruh lapisan | Tercapai | BFLA, CSRF, XSS, race condition mitigated |
| UI Clean minimalis responsif | Tercapai | Tailwind CSS v4, 4 halaman blade |
| Methodology SDD terlaksana bertahap | Tercapai | 11 tahap (1.1 → 5.0) terdokumentasi |

### 6.2 Keterbatasan

1. **AI Service Mock:** Endpoint AI eksternal (`ai-ecosort`) belum terhubung ke model ML sesungguhnya — test menggunakan `Http::fake()`. Untuk produksi diperlukan integrasi ke API ML yang sesungguhnya.
2. **Pagination:** Tabel riwayat poin dan daftar voucher pending belum memiliki pagination — akan menjadi bottleneck jika data melebihi ratusan record.
3. **Email Notification:** Belum ada notifikasi email saat voucher berhasil ditukar atau diserahkan.

### 6.3 Rekomendasi Pengembangan

1. Integrasi model ML (TensorFlow/PyTorch) untuk klasifikasi sampah real-time.
2. Implementasi pagination dan infinite scroll pada halaman yang menampilkan data banyak.
3. Penambahan fitur notifikasi (email/push) untuk status voucher.
4. Dashboard analitik admin dengan grafik tren scan harian/mingguan.
5. Implementasi rate limiting pada endpoint scan untuk mencegah abuse.

---

> **Catatan:** Setiap bab di atas telah dipetakan ke file sumber di folder `ringkasan-perubahan/`. Salin narasi dan tabel ke Microsoft Word, lalu tambahkan screenshot UI sebagai lampiran visual.
