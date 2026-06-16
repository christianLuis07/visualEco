# LAPORAN PROYEK — Visueco
### Aplikasi Web Audit Sampah Berbasis AI dengan Penerapan Secure Coding & OWASP Top 10

**Mata Kuliah:** Praktikum Pemrograman Keamanan Web
**Program Studi:** Rekayasa Keamanan Siber — Politeknik Negeri Cilacap
**Tahun Akademik:** 2025/2026

> Dokumen ini mengikuti struktur 6 bab sesuai ketentuan Soal Praktikum UAS.
> Siap disalin ke Microsoft Word.

---

# BAB 1 — PENDAHULUAN

## 1.1 Latar Belakang

Indonesia menghasilkan puluhan juta ton sampah per tahun dengan tingkat daur
ulang yang masih rendah. Tantangan utama di tingkat lingkungan (RT/RW) adalah
minimnya insentif bagi warga untuk memilah sampah serta tidak adanya sistem
pencatatan yang transparan.

Sejalan dengan **Sustainable Development Goals (SDGs) Butir 12 — Konsumsi dan
Produksi yang Bertanggung Jawab**, dibangun **Visueco**: aplikasi web yang
memungkinkan warga memindai sampah melalui kamera, dikenali jenisnya oleh
Machine Learning, memperoleh poin, dan menukarkannya dengan hadiah nyata.

Sebagai proyek mata kuliah **Keamanan Web**, fokus utama bukan hanya
fungsionalitas, melainkan penerapan **Secure Coding** dan kesadaran **OWASP
Top 10** sejak perancangan hingga implementasi.

## 1.2 Tujuan Aplikasi

1. Membangun aplikasi web Laravel dengan autentikasi, manajemen data (CRUD),
   dan kontrol akses berbasis peran yang **aman**.
2. Menerapkan mitigasi kerentanan OWASP: autentikasi, otorisasi, validasi input,
   CSRF, XSS, keamanan unggah berkas, penanganan error, serta logging & monitoring.
3. Menyediakan fitur audit sampah berbasis AI **self-hosted** (tanpa pihak ketiga)
   sebagai nilai tambah inovatif yang tetap menjaga privasi data.

---

# BAB 2 — ANALISIS SISTEM

## 2.1 Kebutuhan Sistem

### Kebutuhan Fungsional
| Kode | Kebutuhan |
|------|-----------|
| F-01 | Registrasi, login, logout, reset password |
| F-02 | Manajemen data reward (Create, Read, Update, Delete) |
| F-03 | Kontrol akses dua peran: Administrator & User |
| F-04 | Scan sampah → klasifikasi AI → pemberian poin |
| F-05 | Penukaran poin dengan reward (redeem) |
| F-06 | Verifikasi & penyerahan voucher oleh admin |
| F-07 | Model AI belajar dari konfirmasi warga |

### Kebutuhan Non-Fungsional
| Kode | Kebutuhan |
|------|-----------|
| NF-01 | Keamanan sesuai OWASP Top 10 |
| NF-02 | Berjalan dalam container Docker (portabel) |
| NF-03 | ML berjalan di CPU tanpa GPU (transfer learning) |
| NF-04 | Logging audit keamanan terpisah |

## 2.2 Arsitektur Sistem (Multi-Container)

```
┌─────────────┐     ┌───────────────┐     ┌──────────────┐
│ visueco-web │────▶│ visueco-app   │────▶│ visueco-db   │
│ Nginx       │     │ PHP 8.2-FPM   │     │ MySQL 8.0    │
│ Port 8000   │     │ + Node.js 20  │     │ Port 3306    │
└─────────────┘     └───────┬───────┘     └──────────────┘
                            │ HTTP /predict, /train
                            ▼
                    ┌────────────────┐
                    │ visueco-ml     │
                    │ FastAPI +      │
                    │ MobileNetV2    │
                    └────────────────┘
```

Empat container terisolasi dalam jaringan bridge `visueco-network`. Tidak ada
ketergantungan layanan pihak ketiga — seluruh ML self-hosted.

## 2.3 Aktor Sistem
- **User (Warga):** scan sampah, kumpulkan poin, tukar reward.
- **Administrator (Pengurus RT):** kelola reward (CRUD), verifikasi voucher,
  latih model AI.

---

# BAB 3 — IMPLEMENTASI

## 3.1 Struktur Database

Aplikasi memiliki **lebih dari 3 tabel utama** (memenuhi syarat minimal CRUD):

| Tabel | Primary Key | Fungsi |
|-------|-------------|--------|
| `users` | UUID | Akun + peran (admin/user) + saldo poin |
| `rewards` | Auto-increment | Katalog hadiah (objek CRUD utama) |
| `waste_categories` | Integer (1-5) | Master kategori sampah |
| `waste_scans` | UUID | Riwayat scan sampah |
| `point_ledgers` | UUID | Buku besar mutasi poin (polymorphic) |
| `reward_redeems` | UUID | Transaksi penukaran voucher |
| `training_samples` | UUID | Data latih hasil konfirmasi warga |
| `model_versions` | Integer | Riwayat versi & akurasi model AI |
| `password_reset_tokens` | email | Token reset password |

**Keputusan desain:** UUID pada tabel transaksional untuk mencegah *ID
enumeration*; integer pada master data.

### Relasi Utama
- `users` 1—N `waste_scans`, `point_ledgers`, `reward_redeems`, `training_samples`
- `rewards` 1—N `reward_redeems`
- `point_ledgers` —polymorphic→ `waste_scans` / `reward_redeems`

## 3.2 Fitur Aplikasi

### A. Autentikasi (F-01)
- Register, Login, Logout — manual (tanpa Breeze), session-based.
- **Reset Password** — password broker Laravel, token kedaluwarsa 60 menit.

### B. Manajemen Data Reward — CRUD Penuh (F-02)
`AdminRewardController` (dilindungi `role:admin`):

| Operasi | Endpoint | Method |
|---------|----------|--------|
| Create | `/api/v1/admin/rewards` | POST |
| Read (list) | `/api/v1/admin/rewards` | GET |
| Read (detail) | `/api/v1/admin/rewards/{id}` | GET |
| **Update** | `/api/v1/admin/rewards/{id}` | PUT/PATCH |
| **Delete** | `/api/v1/admin/rewards/{id}` | DELETE |

Delete dilindungi integritas: reward dengan riwayat penukaran tidak bisa dihapus.

### C. Role & Hak Akses (F-03)
Middleware `EnsureUserHasRole` — user biasa mengakses area admin → 403 + dicatat.

### D. Fitur Inti
- **Scan AI** (F-04): unggah foto → ML klasifikasi → poin (anti-fraud).
- **Redeem** (F-05): tukar poin → voucher (lock anti race-condition).
- **Verifikasi voucher** (F-06): admin scan kode → serah hadiah.
- **Self-learning ML** (F-07): konfirmasi warga → data latih → latih ulang.

## 3.3 Kontrak Response API (Contoh)

```json
// POST /api/v1/scan → 201
{
  "success": true,
  "data": {
    "scan_id": "uuid", "detected_item": "Botol Plastik",
    "category_id": 1, "category_name": "Plastik",
    "confidence_score": 0.92, "points_awarded": 10, "points_balance": 110
  }
}
```

---

# BAB 4 — ANALISIS KEAMANAN

Bagian ini memetakan implementasi terhadap **OWASP Top 10** dan delapan aspek
keamanan yang diminta soal.

## 4.1 Authentication Security
**Ancaman:** brute-force, user enumeration, session fixation.
**Mitigasi:**
- Pesan login gagal generik ("Email atau password salah") → anti-enumeration.
- `session()->regenerate()` saat login → anti session fixation.
- Password di-hash Bcrypt (cost 12).
- Reset password: token acak hashed, kedaluwarsa, throttle.

## 4.2 Authorization Security (OWASP A01: Broken Access Control / BFLA)
**Ancaman:** user biasa mengakses fungsi admin.
**Mitigasi:** middleware `role:admin` pada seluruh route admin (web & API).
Setiap akses ditolak **dicatat** di log keamanan (`authz.denied`).

## 4.3 Input Validation (OWASP A03: Injection)
**Mitigasi:**
- `FormRequest` & `$request->validate()` di setiap endpoint.
- Eloquent ORM (prepared statements) → anti SQL Injection.
- Mass-assignment dibatasi `$fillable`.
- Validasi reward: tipe, panjang, rentang, keunikan judul.

## 4.4 Cross Site Request Forgery (CSRF)
**Mitigasi:** token CSRF pada semua form (`@csrf`); AJAX mengirim header
`X-XSRF-TOKEN` (Sanctum stateful). Route reset & login diberi throttle.

## 4.5 Cross Site Scripting (XSS) (OWASP A03)
**Mitigasi:**
- Blade `{{ }}` auto-escape.
- Seluruh data dinamis dari API di-render via `textContent` (BUKAN `innerHTML`)
  di JavaScript → menutup DOM-based XSS.

## 4.6 File Upload Security
**Ancaman:** unggah file berbahaya, path traversal.
**Mitigasi berlapis:**
1. Client: `accept` + cek MIME & ukuran (UX).
2. Server: `image|mimes:jpeg,png,jpg|max:4096` (cek header bytes).
3. Storage: `->store()` dengan nama hash acak.
4. Business: anti-fraud confidence ≥ ambang + is_recyclable.

## 4.7 Error Handling
**Mitigasi:** try-catch di seluruh controller; pesan error ke user generik
(tidak membocorkan stack trace); detail teknis masuk log via `report()`.
`APP_DEBUG=false` direkomendasikan untuk produksi.

## 4.8 Logging & Monitoring (OWASP A09: Security Logging Failures)
**Implementasi:** channel log khusus `security` (`storage/logs/security-*.log`),
terpisah dari log aplikasi. Peristiwa yang dicatat:
- `auth.login.success` / `auth.login.failed` (deteksi brute-force)
- `auth.logout`, `auth.register`
- `authz.denied` (percobaan akses tak sah)
- `admin.reward.created/updated/deleted`
- `admin.voucher.completed`
- `auth.password.reset_requested/success/failed`

**Penting:** password & kredensial **tidak pernah** dicatat.

## 4.9 Tambahan: Race Condition / TOCTOU
Redeem reward & complete voucher memakai `DB::transaction()` + `lockForUpdate()`
(pessimistic locking) untuk mencegah penukaran/penyerahan ganda.

---

# BAB 5 — PENGUJIAN

## 5.1 Metode
Pengujian otomatis menggunakan **PHPUnit** di dalam Docker terhadap database
terpisah (`visueco_test`). Setiap fitur keamanan diverifikasi dengan skenario
positif & negatif.

## 5.2 Matriks Hasil Pengujian

| Suite | Skenario | Status |
|-------|----------|--------|
| ScanTrashTest | 9 (sukses, validasi, anti-fraud, AI down, unauth) | PASS |
| RedeemRewardTest | 4 (sukses, poin kurang, stok habis, unauth) | PASS |
| ScanConfirmTest | 5 (konfirmasi, koreksi, invalid, dobel, unauth) | PASS |
| **AdminRewardCrudTest** | 10 (CRUD penuh, validasi, BFLA, guest) | PASS |
| **PasswordResetTest** | 6 (request, anti-enumeration, reset, token invalid) | PASS |
| ExampleTest | 2 | PASS |
| **TOTAL** | **36 tests / 141 assertions** | **ALL PASS** |

## 5.3 Pengujian Keamanan Spesifik

| Aspek | Pengujian | Hasil |
|-------|-----------|-------|
| Authorization (BFLA) | User biasa akses CRUD admin | 403 ✓ |
| Authentication | Guest akses endpoint terlindung | 401 ✓ |
| Input Validation | Data reward invalid | 422 ✓ |
| Anti-enumeration | Reset email tak terdaftar | Pesan netral ✓ |
| Token Security | Reset dengan token palsu | Ditolak ✓ |
| Integritas Data | Hapus reward yang punya redeem | Diblokir 422 ✓ |

## 5.4 Perintah Pengujian
```bash
docker exec visueco-app php artisan test
```

---

# BAB 6 — KESIMPULAN

## 6.1 Pencapaian

| Syarat Soal UAS | Status |
|-----------------|--------|
| Auth: Register, Login, Logout | ✅ |
| Reset Password (nilai tambah) | ✅ |
| CRUD ≥3 tabel (Create/Read/Update/Delete) | ✅ |
| Role: Admin + User | ✅ |
| Authentication Security | ✅ |
| Authorization Security | ✅ |
| Input Validation | ✅ |
| CSRF | ✅ |
| XSS | ✅ |
| File Upload Security | ✅ |
| Error Handling | ✅ |
| Logging & Monitoring | ✅ |

Seluruh syarat wajib terpenuhi, ditambah fitur opsional (reset password) dan
nilai inovasi (ML self-hosted yang belajar).

## 6.2 Kesimpulan
Visueco membuktikan bahwa aplikasi web fungsional dapat dibangun dengan
**Secure Coding** dan kesadaran **OWASP Top 10** sejak awal. Seluruh aspek
keamanan yang diminta diterapkan dan **diverifikasi melalui 36 pengujian
otomatis**. Aplikasi berjalan terisolasi dalam Docker tanpa ketergantungan
pihak ketiga, menjaga privasi data warga.

## 6.3 Keterbatasan & Pengembangan Lanjut
- Akurasi ML (~54-65%) masih dapat ditingkatkan dengan menambah data latih.
- Belum ada pagination pada daftar data besar.
- Pengiriman email reset masih ke log (`MAIL_MAILER=log`) — produksi perlu SMTP.

---

*Lampiran: tangkapan layar UI dapat ditambahkan sesuai kebutuhan. Source code
lengkap disertakan dalam berkas ZIP pengumpulan.*
