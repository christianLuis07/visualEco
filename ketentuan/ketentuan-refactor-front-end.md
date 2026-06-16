Bertindaklah sebagai Senior UI/UX Engineer dan Front-End Architect Laravel Expert. Proyek kita bernama "Visueco" (Aplikasi Web & Web API berbasis Laravel Docker untuk audit sampah SDGs 12). 

Kita akan melakukan REFACTORING BESAR-BESARAN pada layer frontend karena tampilan sebelumnya terlalu membosankan, kaku, dan terlihat seperti template AI generik yang pasaran. Kita akan merombak total interaksi pada halaman `dashboard.blade.php`, `rewards.blade.php`, dan `admin/dashboard.blade.php`.

Anda WAJIB mematuhi aturan arsitektur frontend dan keamanan berikut tanpa pengecualian:

---

### ATURAN 1: SEPARATION OF CONCERNS (ISOLASI KODE)
1. DILARANG KERAS menulis tag <script> atau memasukkan kode JavaScript apa pun di dalam file Blade.
2. DILARANG KERAS menulis tag <style> atau custom CSS internal di dalam file Blade. Utilitas Tailwind CSS ditulis langsung di class HTML, namun jika ada custom CSS (seperti animasi linier atau custom grid), wajib dipisah.
3. Manajemen File Spesifik: Setiap satu halaman Blade hanya boleh mengikat satu file JavaScript eksternal khusus dan satu file CSS eksternal khusus yang dipanggil melalui tag <script src="..."> atau Vite asset bundler di bagian atas/bawah template.
   - Contoh struktur:
     * resources/views/dashboard.blade.php ◄ Murni HTML & Tailwind Class
     * public/js/pages/dashboard.js ◄ Berisi seluruh logika Vanilla JS AJAX Fetch, State Machine, dan DOM Manipulation textContent
     * public/css/pages/dashboard.css ◄ Berisi custom styling/animasi spesifik halaman tersebut

---

### ATURAN 2: REUSABLE BLADE COMPONENTS (MINDSET ARCHITECTURE LIKE REACT)
Jangan menulis elemen kartu atau modal berulang-ulang secara hardcoded. Pecah UI menjadi komponen-komponen kecil yang reusable di dalam folder `resources/views/components/` memanfaatkan fitur komponen Laravel (Anonymous Components atau Blade Components).
Buat komponen reusable berikut:
1. `<x-clean-card>` : Komponen kartu minimalis dengan soft shadow ambient, padding presisi, dan border tipis sewarna latar untuk membungkus metrik poin atau form.
2. `<x-action-button>` : Komponen tombol premium warna Teal (#0D9488) dengan efek transisi transparan halus saat hover dan state disabled otomatis saat memproses AJAX.
3. `<x-status-badge>` : Komponen badge status transparan (untuk pending/completed/credit/debit) dengan pemisahan warna pastel medis yang elegan.
4. `<x-feedback-alert>` : Komponen banner notifikasi/error transparan melayang untuk menangkap error validasi atau server down.

---

### ATURAN 3: LUXURY CLEAN UI & ANTI-TEMPLATE AESTHETIC
- Canvas Utama: bg-[#F8FAFC] (Slate abu super lembut), text-[#0F172A] (Slate gelap), dan aksen utama text-[#0D9488] (Teal Medis).
- Layout Asimetris: Gunakan pembagian grid dengan rasio 2:3 (lg:col-span-2 untuk form kontrol/kamera dan lg:col-span-3 untuk dynamic display/hasil) untuk menciptakan visual yang bernapas dan tidak monoton.
- State Machine Loading Senyap: Saat proses AJAX berjalan, ganti spinner putar tradisional dengan animasi horizontal pulse redup (fading skeleton) pada kontainer target.
- Keamanan DOM-Based XSS: Di dalam file JavaScript terpisah nanti, pastikan semua data dinamis dari response API di-render ke elemen HTML menggunakan properti `textContent` (BUKAN innerHTML) untuk memblokir celah XSS.

---

### TUGAS EKSEKUSI REFACTOR:
Hasilkan struktur kode lengkap, rapi, dan modular untuk halaman `dashboard.blade.php` beserta file pasangannya:
1. `resources/views/dashboard.blade.php` (Gunakan komponen <x-clean-card>, <x-action-button>, dll. Tanpa script/style internal).
2. `public/js/pages/dashboard.js` (Seluruh logika AJAX Fetch ke endpoint /api/v1/scan dengan penanganan token CSRF X-XSRF-TOKEN dan state machine UI).
3. `public/css/pages/dashboard.css` (Custom CSS untuk micro-interactions atau animasi skeleton pulse).

Tuliskan juga dokumen audit final di `ringkasan-perubahan/refactor-front-end.md` beserta rekomendasi pesan commit berstandar Conventional Commits di paling akhir respon Anda. Jangan potong kode, berikan hasil refaktor yang elegan dan siap demo!