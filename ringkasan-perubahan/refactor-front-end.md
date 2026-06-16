# Refactor Front-End — Isolasi Kode + Komponen Reusable (3 Halaman)

**Tanggal Perubahan:** 16 Juni 2026

---

## Ringkasan

Refactoring besar layer frontend untuk **`dashboard.blade.php`,
`rewards.blade.php`, dan `admin/dashboard.blade.php`** sesuai ketentuan
`ketentuan/ketentuan-refactor-front-end.md`: isolasi total script/style,
komponen Blade reusable, dan estetika Luxury Clean UI dengan layout asimetris 2:3.

> Ketiga halaman kini **0 inline `<script>` / `<style>`**, memuat JS & CSS
> eksternal per-halaman, dan menggunakan 4 komponen Blade bersama.

---

## Cakupan Per Halaman

| Halaman | JS Eksternal | CSS Eksternal | Terverifikasi |
|---------|-------------|---------------|---------------|
| `dashboard.blade.php` | `public/js/pages/dashboard.js` | `public/css/pages/dashboard.css` | scan+confirm 201, 0 inline ✓ |
| `rewards.blade.php` | `public/js/pages/rewards.js` | `public/css/pages/rewards.css` | redeem modal, 0 inline, 4 tombol ✓ |
| `admin/dashboard.blade.php` | `public/js/pages/admin.js` | `public/css/pages/admin.css` | verify+complete+train, 0 inline ✓ |

### Bug Blade yang ditemukan & diperbaiki saat refactor

`@disabled(...)` directive **tidak boleh** dipakai sebagai atribut di dalam tag
komponen `<x-action-button>` — menyebabkan syntax error Blade ("unexpected
endif") sehingga `/rewards` gagal render & fallback ke welcome. Diganti dengan
bound attribute `:disabled="$reward->stock <= 0"` yang merupakan cara benar.

---

## Daftar File yang Dibuat / Diubah

| Status | Path | Keterangan |
|--------|------|------------|
| **Diubah** | `resources/views/layouts/auth.blade.php` | Tambah `@stack('styles')` & `@stack('scripts')`, canvas `#F8FAFC` |
| **Baru** | `resources/views/components/clean-card.blade.php` | Kartu ambient shadow |
| **Baru** | `resources/views/components/action-button.blade.php` | Tombol Teal premium (primary/ghost/soft) |
| **Baru** | `resources/views/components/status-badge.blade.php` | Badge pastel medis (pending/completed/credit/debit) |
| **Baru** | `resources/views/components/feedback-alert.blade.php` | Banner notifikasi transparan (inline/floating) |
| **Diubah** | `resources/views/dashboard.blade.php` | HTML+Tailwind murni + komponen, tanpa script/style internal |
| **Baru** | `public/js/pages/dashboard.js` | AJAX scan+confirm, state machine, DOM via textContent |
| **Baru** | `public/css/pages/dashboard.css` | Skeleton pulse senyap + micro-interactions |
| **Diubah** | `resources/views/rewards.blade.php` | HTML murni + komponen, modal redeem, `:disabled` fix |
| **Baru** | `public/js/pages/rewards.js` | AJAX redeem, modal confirm/success, textContent |
| **Baru** | `public/css/pages/rewards.css` | Animasi masuk modal |
| **Diubah** | `resources/views/admin/dashboard.blade.php` | HTML murni + komponen, layout asimetris 2:3 |
| **Baru** | `public/js/pages/admin.js` | AJAX verify+complete+train, badge dinamis, textContent |
| **Baru** | `public/css/pages/admin.css` | Animasi masuk kartu detail |
| **Baru** | `ringkasan-perubahan/refactor-front-end.md` | Dokumen ini |

---

## Audit Kepatuhan Aturan

### ATURAN 1 — Separation of Concerns ✅

| Cek | Hasil |
|-----|-------|
| Inline `<script>` di blade | **0** (terverifikasi via grep) |
| Inline `<style>` di blade | **0** (terverifikasi via grep) |
| JS eksternal khusus halaman | `public/js/pages/dashboard.js` (via `@stack('scripts')`) |
| CSS eksternal khusus halaman | `public/css/pages/dashboard.css` (via `@stack('styles')`) |
| Asset reachable | dashboard.js HTTP 200, dashboard.css HTTP 200 |

### ATURAN 2 — Reusable Blade Components ✅

Empat anonymous component dibuat & dipakai di dashboard:
- `<x-clean-card>` — membungkus kartu poin, kamera, dan display hasil
- `<x-action-button variant="primary|ghost|soft">` — semua tombol aksi
- `<x-status-badge tone="credit|pending|...">` — badge skor hasil
- `<x-feedback-alert>` — banner error/notifikasi

> Komponen ini juga siap dipakai ulang di `rewards.blade.php` &
> `admin/dashboard.blade.php` pada tahap berikutnya.

### ATURAN 3 — Luxury Clean UI & Anti-Template ✅

- **Canvas**: `bg-[#F8FAFC]`, teks `#0F172A`, aksen `#0D9488`.
- **Layout asimetris 2:3**: kiri `lg:col-span-2` (poin + kamera),
  kanan `lg:col-span-3` (display hasil dinamis) — terverifikasi.
- **State machine senyap**: spinner tradisional diganti **skeleton pulse**
  (shimmer linier) — 5 elemen skeleton pada state loading.
- **Anti-XSS**: seluruh data API dirender via `textContent` / `createElement`
  + `setAttribute` (SVG), **tanpa `innerHTML`** sama sekali.

---

## Arsitektur State Machine (dashboard.js)

```
IDLE ──pilih gambar──▶ (preview) ──klik Analisis──▶ LOADING (skeleton)
                                                        │
                                          ┌─────────────┴─────────────┐
                                          ▼                           ▼
                                       RESULT                       IDLE
                                  (hasil + konfirmasi)         (+ alert error)
                                          │
                                  ┌───────┴────────┐
                                  ▼                ▼
                             "Ya, benar"     "Bukan, koreksi"
                                  └──── POST /scan/confirm ────┘
```

Tiga state div (`#state-idle`, `#state-loading`, `#state-result`) ditoggle
oleh `showState()` — hanya satu terlihat pada satu waktu.

---

## Bug yang Sekaligus Diperbaiki

Selama refactor, bug tombol **"Ya, benar" yang diam** ikut tuntas:
- **Backend** `ScanController` kini menyertakan `category_id` di respons 201
  (sebelumnya hanya `category_name`, sehingga `currentCategoryId` selalu null).
- **Frontend** `btnConfirmYes` diberi fallback ke nilai dropdown + pesan bila
  kategori tak terbaca — tidak lagi "diam tanpa respons".

Teks "Apakah kategori **X** ini sudah benar?" memang **sudah dinamis**
(`confirm-category-name` diisi `data.category_name`).

---

## Verifikasi (Diuji Nyata)

```
Render dashboard  : state-idle ✓ state-loading ✓ skeleton-pulse ×5 ✓
Isolasi           : inline <script>=0  inline <style>=0  ✓
Asset eksternal   : dashboard.js 200  dashboard.css 200  ✓
Grid asimetris    : lg:col-span-2 ✓
Alur fungsi       : scan 201 (category_id=1) → confirm "Ya benar" 201 ✓
Test suite        : 15 passed (89 assertions) — tanpa regresi ✓
```

---

## Catatan Operasional

Karena optimasi performa (OPcache `validate_timestamps=0` + view cache),
setelah edit blade/asset perlu:

```bash
docker exec visueco-app php artisan view:clear
docker exec visueco-app npm run build
docker restart visueco-app
docker exec visueco-app php artisan view:cache
```

---

## Pesan Commit

```
refactor(ui): isolate dashboard, rewards & admin into components + external assets

- Extract all page JS/CSS to public/js/pages & public/css/pages (dashboard,
  rewards, admin) — AJAX, state machines, textContent-only rendering
- Add reusable Blade components: clean-card, action-button, status-badge,
  feedback-alert; apply across all three pages
- Rebuild dashboard/rewards/admin as pure HTML+Tailwind with 2:3 asymmetric
  grids and silent skeleton-pulse loading
- Add @stack('styles'/'scripts') to auth layout for per-page assets
- Fix Blade @disabled-in-component error on rewards (use :disabled bound attr)
- Include category_id in scan response so "Ya, benar" confirm works
- No inline <script>/<style> on any page; 15 tests pass
```
