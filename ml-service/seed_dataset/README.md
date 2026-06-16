# Seed Dataset — Data Latih Awal ML Visueco

Folder ini adalah tempat kamu menaruh **foto contoh sampah** untuk melatih model
AI agar lebih akurat. Foto di sini akan disalin ke dataset internal ML lalu
dipakai melatih classifier.

## Cara Pakai

1. **Taruh foto** ke folder sesuai kategorinya:

   | Folder | Kategori | Contoh isi |
   |--------|----------|------------|
   | `1_plastik/` | Plastik | botol plastik, kresek, gelas plastik, kemasan |
   | `2_kertas/`  | Kertas  | kardus, koran, kertas HVS, buku |
   | `3_logam/`   | Logam   | kaleng, tutup botol, sendok logam |
   | `4_kaca/`    | Kaca    | botol kaca, gelas kaca, toples |
   | `5_organik/` | Organik | sisa buah, sayur, daun |

2. **Format**: JPG / JPEG / PNG. Nama file bebas.

3. **Jumlah**: minimal **10-15 foto per kategori** agar pelatihan bermakna.
   Makin banyak & beragam (sudut, pencahayaan, latar), makin akurat.

4. Setelah foto siap, jalankan ingest + train:
   ```bash
   # salin foto ke dataset internal ML
   docker exec visueco-ml curl -s -X POST http://localhost:8001/seed
   # latih model
   docker exec visueco-ml curl -s -X POST http://localhost:8001/train
   ```
   (atau lewat tombol di panel admin bila sudah tersedia)

## Catatan

- Folder ini di-mount **read-only** ke container ML — aman, foto aslimu tidak
  diubah atau dihapus.
- Proses ingest **idempotent**: menjalankan `/seed` berkali-kali tidak
  menggandakan foto yang sama (dicek via hash isi file).
- File `.gitkeep` hanya penanda folder; abaikan.
