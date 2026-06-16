@extends('layouts.auth')

@section('title', 'Dashboard — Visueco')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard.css') }}">
@endpush

@section('content')
<div class="min-h-screen px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-5xl">

        {{-- ═══ TOP BAR ═══ --}}
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-[#0F172A]">Halo, {{ auth()->user()->name }}</h1>
                <p class="text-sm text-slate-500">Ayo kontribusi untuk lingkungan hari ini</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('rewards') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-white">
                    Reward
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-white">
                        Keluar
                    </button>
                </form>
            </div>
        </div>

        {{-- ═══ ASYMMETRIC GRID 2:3 ═══ --}}
        <div class="grid gap-6 lg:grid-cols-5">

            {{-- ── KIRI (2/5): Kontrol Kamera & Poin ── --}}
            <div class="space-y-6 lg:col-span-2">

                {{-- Poin Card --}}
                <x-clean-card>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Saldo Poin Anda</p>
                    <p class="mt-1 text-4xl font-bold tracking-tight text-[#0D9488]" id="points-display">
                        {{ auth()->user()->points_balance }}
                    </p>
                    <p class="mt-1 text-sm text-slate-500">poin terkumpul</p>
                </x-clean-card>

                {{-- Kamera / Upload Card --}}
                <x-clean-card>
                    <h2 class="mb-4 text-sm font-semibold text-[#0F172A]">Scan Sampah</h2>

                    <div
                        id="dropzone"
                        class="dropzone relative flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-12 transition hover:border-[#0D9488]/50 hover:bg-teal-50/30"
                    >
                        <div id="dropzone-placeholder" class="text-center">
                            <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                            </svg>
                            <p class="mt-3 text-sm font-medium text-slate-600">Ketuk untuk ambil foto atau pilih gambar</p>
                            <p class="mt-1 text-xs text-slate-400">JPG, JPEG, atau PNG — Maks. 4 MB</p>
                        </div>

                        <img id="image-preview" class="hidden max-h-56 rounded-xl object-contain" alt="Preview gambar sampah">

                        <input
                            type="file"
                            name="image"
                            id="image-input"
                            accept="image/jpeg,image/png,image/jpg"
                            capture="environment"
                            class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                        >
                    </div>

                    <x-action-button id="btn-scan" variant="primary" disabled class="mt-4 w-full">
                        Analisis Sampah
                    </x-action-button>
                </x-clean-card>
            </div>

            {{-- ── KANAN (3/5): Display Hasil Dinamis ── --}}
            <div class="lg:col-span-3">
                <x-clean-card class="h-full">

                    {{-- Alert inline --}}
                    <x-feedback-alert id="alert-banner" class="mb-4" />

                    {{-- Idle State --}}
                    <div id="state-idle" class="flex h-full min-h-[20rem] flex-col items-center justify-center text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 text-[#0D9488]">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-sm font-semibold text-[#0F172A]">Hasil analisis muncul di sini</h3>
                        <p class="mt-1 max-w-xs text-sm text-slate-500">Pilih gambar sampah lalu tekan “Analisis Sampah” untuk mulai.</p>
                    </div>

                    {{-- Loading State (skeleton pulse senyap) --}}
                    <div id="state-loading" class="hidden space-y-4">
                        <div class="skeleton-pulse h-48 w-full rounded-2xl"></div>
                        <div class="skeleton-pulse h-5 w-2/3 rounded-lg"></div>
                        <div class="skeleton-pulse h-4 w-1/2 rounded-lg"></div>
                        <div class="space-y-2 pt-2">
                            <div class="skeleton-pulse h-3 w-full rounded"></div>
                            <div class="skeleton-pulse h-3 w-5/6 rounded"></div>
                        </div>
                    </div>

                    {{-- Result State --}}
                    <div id="state-result" class="hidden">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Terdeteksi</p>
                                <h3 class="text-lg font-semibold text-[#0F172A]" id="result-label"></h3>
                            </div>
                            <x-status-badge tone="credit" id="result-score"></x-status-badge>
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl bg-teal-50/60 p-4">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-[#0D9488] shadow-sm">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-slate-600">Kategori: <span class="font-semibold text-[#0F172A]" id="result-category"></span></p>
                                <p class="text-sm font-semibold text-[#0D9488]">+<span id="result-points"></span> poin diperoleh!</p>
                            </div>
                        </div>

                        <div class="mt-5">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Cara Penanganan</p>
                            <ul id="result-instructions" class="space-y-1.5"></ul>
                        </div>

                        {{-- Blok Konfirmasi: bantu AI belajar --}}
                        <div id="confirm-block" class="mt-5 border-t border-slate-100 pt-4">
                            <p class="mb-1 text-xs font-semibold text-slate-700">
                                Apakah kategori <span id="confirm-category-name" class="text-[#0D9488]">—</span> ini sudah benar?
                            </p>
                            <p class="mb-3 text-xs text-slate-400">Bantu AI belajar mengenali sampah dengan lebih akurat.</p>

                            <div id="confirm-actions" class="flex flex-wrap gap-2">
                                <x-action-button id="btn-confirm-yes" variant="soft" class="px-4 py-2 text-xs">Ya, benar</x-action-button>
                                <x-action-button id="btn-confirm-no" variant="ghost" class="px-4 py-2 text-xs">Bukan, koreksi</x-action-button>
                            </div>

                            <div id="correct-picker" class="mt-3 hidden">
                                <label for="correct-category" class="mb-1 block text-xs font-medium text-slate-500">Pilih kategori yang benar:</label>
                                <div class="flex gap-2">
                                    <select id="correct-category"
                                        class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-[#0D9488] focus:outline-none focus:ring-2 focus:ring-[#0D9488]/20">
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-action-button id="btn-submit-correction" variant="primary" class="px-4 py-2 text-xs">Kirim</x-action-button>
                                </div>
                            </div>

                            <p id="confirm-thanks" class="mt-3 hidden text-xs font-medium text-[#0D9488]"></p>
                        </div>
                    </div>

                </x-clean-card>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/dashboard.js') }}" defer></script>
@endpush
