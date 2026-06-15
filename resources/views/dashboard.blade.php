@extends('layouts.auth')

@section('title', 'Dashboard — Visueco')

@section('content')
<div class="min-h-screen px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-2xl">

        {{-- ═══ TOP BAR ═══ --}}
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">Halo, {{ auth()->user()->name }}</h1>
                <p class="text-sm text-slate-500">Ayo kontribusi untuk lingkungan hari ini</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-white">
                    Keluar
                </button>
            </form>
        </div>

        {{-- ═══ POIN CARD ═══ --}}
        <div class="mb-8 rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
            <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">Saldo Poin Anda</p>
            <p class="mt-1 text-4xl font-bold tracking-tight text-teal-600" id="points-display">
                {{ auth()->user()->points_balance }}
            </p>
            <p class="mt-1 text-sm text-slate-500">poin terkumpul</p>
        </div>

        {{-- ═══ SCAN CARD ═══ --}}
        <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold text-slate-900">Scan Sampah</h2>

            {{-- Alert Banner --}}
            <div id="alert-banner" class="mb-4 hidden rounded-lg px-4 py-3 text-sm"></div>

            {{-- Dropzone --}}
            <div
                id="dropzone"
                class="relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-12 transition hover:border-teal-400 hover:bg-teal-50/30"
            >
                {{-- Placeholder Icon --}}
                <div id="dropzone-placeholder" class="text-center">
                    <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                    </svg>
                    <p class="mt-3 text-sm font-medium text-slate-600">Ketuk untuk ambil foto atau pilih gambar</p>
                    <p class="mt-1 text-xs text-slate-400">JPG, JPEG, atau PNG — Maks. 4 MB</p>
                </div>

                {{-- Image Preview --}}
                <img id="image-preview" class="hidden max-h-64 rounded-lg object-contain" alt="Preview">

                {{-- Loading Skeleton --}}
                <div id="loading-skeleton" class="hidden w-full space-y-3">
                    <div class="h-40 animate-pulse rounded-lg bg-slate-200"></div>
                    <div class="h-4 w-3/4 animate-pulse rounded bg-slate-200"></div>
                    <div class="h-4 w-1/2 animate-pulse rounded bg-slate-200"></div>
                </div>

                {{-- Hidden File Input --}}
                <input
                    type="file"
                    name="image"
                    id="image-input"
                    accept="image/jpeg,image/png,image/jpg"
                    capture="environment"
                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                >
            </div>

            {{-- Submit Button --}}
            <button
                id="btn-scan"
                type="button"
                disabled
                class="mt-4 w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40"
            >
                Analisis Sampah
            </button>

            {{-- Result Card --}}
            <div id="result-card" class="mt-6 hidden rounded-xl border border-teal-100 bg-teal-50/50 p-5">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900" id="result-label"></h3>
                    <span class="rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-semibold text-teal-700" id="result-score"></span>
                </div>
                <p class="text-sm text-slate-600">Kategori: <span class="font-medium text-slate-800" id="result-category"></span></p>
                <p class="mt-1 text-sm text-teal-700 font-semibold">+<span id="result-points"></span> poin diperoleh!</p>

                <div class="mt-4 border-t border-teal-100 pt-3">
                    <p class="mb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase">Cara Penanganan</p>
                    <ul id="result-instructions" class="space-y-1.5"></ul>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const imageInput      = document.getElementById('image-input');
    const imagePreview    = document.getElementById('image-preview');
    const placeholder     = document.getElementById('dropzone-placeholder');
    const loadingSkeleton = document.getElementById('loading-skeleton');
    const btnScan         = document.getElementById('btn-scan');
    const alertBanner     = document.getElementById('alert-banner');
    const resultCard      = document.getElementById('result-card');
    const pointsDisplay   = document.getElementById('points-display');

    let selectedFile = null;

    // ─── File selection & preview ───────────────────────
    imageInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        const allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!allowed.includes(file.type)) {
            showAlert('Format file tidak didukung. Gunakan JPG atau PNG.', 'error');
            this.value = '';
            return;
        }

        if (file.size > 4 * 1024 * 1024) {
            showAlert('Ukuran file melebihi batas 4 MB.', 'error');
            this.value = '';
            return;
        }

        selectedFile = file;
        hideAlert();
        resultCard.classList.add('hidden');

        const reader = new FileReader();
        reader.onload = function (e) {
            imagePreview.src = e.target.result;
            imagePreview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(file);

        btnScan.disabled = false;
    });

    // ─── Submit via fetch ───────────────────────────────
    btnScan.addEventListener('click', async function () {
        if (!selectedFile) return;

        setLoading(true);
        hideAlert();
        resultCard.classList.add('hidden');

        const formData = new FormData();
        formData.append('image', selectedFile);

        try {
            const res = await fetch('/api/v1/scan', {
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: formData,
            });

            const json = await res.json();

            if (res.status === 201 && json.success) {
                showResult(json.data);
            } else {
                showAlert(json.message || 'Terjadi kesalahan saat memproses gambar.', 'error');
            }
        } catch (err) {
            showAlert('Tidak dapat terhubung ke server. Periksa koneksi Anda.', 'error');
        } finally {
            setLoading(false);
        }
    });

    // ─── Helpers ────────────────────────────────────────
    function setLoading(loading) {
        if (loading) {
            imagePreview.classList.add('hidden');
            placeholder.classList.add('hidden');
            loadingSkeleton.classList.remove('hidden');
            btnScan.disabled = true;
            btnScan.textContent = 'Menganalisis...';
        } else {
            loadingSkeleton.classList.add('hidden');
            imagePreview.classList.remove('hidden');
            btnScan.disabled = false;
            btnScan.textContent = 'Analisis Sampah';
        }
    }

    function showResult(data) {
        document.getElementById('result-label').textContent = data.detected_item;
        document.getElementById('result-score').textContent = (data.confidence_score * 100).toFixed(0) + '% akurasi';
        document.getElementById('result-category').textContent = data.category_name;
        document.getElementById('result-points').textContent = data.points_awarded;

        const list = document.getElementById('result-instructions');
        list.innerHTML = '';
        (data.instructions || []).forEach(function (text) {
            const li = document.createElement('li');
            li.className = 'flex items-start gap-2 text-sm text-slate-700';
            li.innerHTML =
                '<svg class="mt-0.5 h-4 w-4 shrink-0 text-teal-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />' +
                '</svg>' +
                '<span>' + escapeHtml(text) + '</span>';
            list.appendChild(li);
        });

        resultCard.classList.remove('hidden');

        pointsDisplay.textContent = data.points_balance;

        resetInput();
    }

    function showAlert(message, type) {
        alertBanner.textContent = message;
        alertBanner.className = 'mb-4 rounded-lg px-4 py-3 text-sm';
        if (type === 'error') {
            alertBanner.classList.add('bg-red-50', 'text-red-800');
        } else {
            alertBanner.classList.add('bg-teal-50', 'text-teal-800');
        }
        alertBanner.classList.remove('hidden');
    }

    function hideAlert() {
        alertBanner.classList.add('hidden');
    }

    function resetInput() {
        selectedFile = null;
        imageInput.value = '';
        btnScan.disabled = true;
    }

    function getCookie(name) {
        const value = '; ' + document.cookie;
        const parts = value.split('; ' + name + '=');
        if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
        return '';
    }

    function escapeHtml(text) {
        const el = document.createElement('span');
        el.textContent = text;
        return el.innerHTML;
    }
});
</script>
@endsection
