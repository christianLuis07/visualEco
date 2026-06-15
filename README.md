# Visueco — AI-Based Waste Audit for SDGs 12

Visueco adalah aplikasi audit sampah berbasis AI yang dirancang untuk mendukung **Sustainable Development Goals (SDGs) Butir 12: Responsible Consumption and Production**. Aplikasi ini memungkinkan warga untuk memindai sampah menggunakan kamera, mendapatkan poin reward, dan menukarkannya dengan hadiah nyata melalui pengurus RT.

---

## Prasyarat Sistem

Anda **hanya** membutuhkan satu software:

| Software | Versi Minimum | Download |
|----------|--------------|----------|
| Docker Desktop | 4.x+ | [docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop) |

> **Tidak perlu** menginstal PHP, MySQL, Composer, atau Node.js di laptop Anda. Seluruh runtime berjalan di dalam container Docker.

Pastikan Docker Desktop sudah berjalan (ikon Docker terlihat di system tray) sebelum melanjutkan.

---

## Langkah Instalasi (Quick Start)

Buka terminal (Command Prompt / PowerShell / Terminal) lalu jalankan perintah berikut **satu per satu secara berurutan**:

### 1. Masuk ke direktori proyek

```bash
cd visueco
```

### 2. Salin file environment

```bash
cp .env.example .env
```

### 3. Nyalakan seluruh container (build + start)

```bash
docker compose up -d --build
```

> Proses pertama kali memakan waktu 3-5 menit (download image PHP, Nginx, MySQL). Tunggu hingga ketiga container `visueco-app`, `visueco-web`, dan `visueco-db` berstatus **running**.

### 4. Instal dependensi PHP (Composer)

```bash
docker exec -it visueco-app composer install
```

### 5. Generate application key

```bash
docker exec -it visueco-app php artisan key:generate
```

### 6. Jalankan migrasi database + data awal

```bash
docker exec -it visueco-app php artisan migrate --seed
```

### 7. Buka aplikasi di browser

```
http://localhost:8000
```

---

## Akun Uji Coba

Aplikasi telah menyediakan dua akun siap pakai setelah proses seeding:

### Akun Warga (User Biasa)

| Field | Nilai |
|-------|-------|
| Email | `warga@visueco.test` |
| Password | `password` |
| Saldo Awal | 100 poin |

Login di: [http://localhost:8000/login](http://localhost:8000/login)

Fitur yang dapat diakses:
- Dashboard scan sampah AI (`/dashboard`)
- Katalog reward & riwayat poin (`/rewards`)

### Akun Administrator (Pengurus RT)

| Field | Nilai |
|-------|-------|
| Email | `admin@visueco.test` |
| Password | `password` |
| Role | Admin |

Login di: [http://localhost:8000/login](http://localhost:8000/login)

Fitur yang dapat diakses:
- Panel administrasi (`/admin`)
- Verifikasi kode voucher warga
- Konfirmasi penyerahan hadiah fisik

---

## Menjalankan Test Suite

```bash
docker exec -it visueco-app php artisan test
```

Hasil yang diharapkan: **15 tests, 89 assertions — ALL PASSED**.

---

## Arsitektur Container

```
┌─────────────┐     ┌───────────────┐     ┌──────────────┐
│ visueco-web │────▶│ visueco-app   │────▶│ visueco-db   │
│ Nginx:alpine│     │ PHP 8.2-FPM   │     │ MySQL 8.0    │
│ Port: 8000  │     │ Port: 9000    │     │ Port: 3306   │
└─────────────┘     └───────────────┘     └──────────────┘
       ▲                                         │
       │            visueco-network (bridge)      │
       └──────────────────────────────────────────┘
```

---

## Mematikan Aplikasi

```bash
docker compose down
```

Untuk menghapus semua data database (reset total):

```bash
docker compose down -v
```

---

## Tech Stack

- **Backend:** Laravel 12, PHP 8.2
- **Database:** MySQL 8.0 (UUID primary keys)
- **Auth:** Laravel Sanctum (stateful session)
- **Frontend:** Blade + Tailwind CSS v4 (via Vite)
- **Container:** Docker Compose (PHP-FPM + Nginx + MySQL)
- **Testing:** PHPUnit 15 tests / 89 assertions
