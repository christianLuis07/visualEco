# model_store — Dataset Latih & Model ML (Folder Host)

Folder ini berisi **seluruh data latih dan model AI** Visueco. Berbeda dari
sebelumnya (named volume Docker yang tak terlihat), kini semuanya **transparan**
di File Explorer — mudah diaudit & di-backup.

## Isi

| Path | Keterangan |
|------|------------|
| `dataset/1/` … `dataset/5/` | Foto latih per kategori (1=Plastik … 5=Organik) |
| `head.keras` | Bobot model classifier terlatih |
| `meta.json` | Metadata versi & akurasi model aktif |

## Dari Mana Foto di `dataset/` Datang?

Dua jalur, keduanya bermuara di sini:

1. **Seed manual** — foto yang kamu taruh di `../seed_dataset/<kategori>/` lalu
   diimpor lewat tombol admin "Impor Seed + Latih".
2. **Konfirmasi warga** — saat warga scan & menekan "Ya, benar"/"Bukan, koreksi",
   foto otomatis tersimpan ke `dataset/<kategori_benar>/`.

## Penting

- Folder ini **di-ignore git** (lihat `.gitignore`) — foto & model adalah data
  runtime, tidak ikut commit. Hanya struktur folder (`.gitkeep`) yang dilacak.
- **Backup**: cukup salin folder ini. Untuk reset total, hapus isi `dataset/`,
  `head.keras`, `meta.json` lalu restart container ML.
- Nama file foto = hash isi → tidak ada duplikat.
