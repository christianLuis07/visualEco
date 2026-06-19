@extends('layouts.auth')

@section('title', 'Dashboard — Visueco')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard.css') }}">
    <style>
        /* Interactive Sidebar Hover */
        .sidebar-item {
            position: relative;
            transition: all 0.3s var(--aww-ease);
            overflow: hidden;
        }
        .sidebar-item::before {
            content: '';
            position: absolute;
            top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(13, 148, 136, 0.1), transparent);
            transition: left 0.5s var(--aww-ease);
        }
        .sidebar-item:hover::before {
            left: 100%;
        }
        .sidebar-item:hover {
            background-color: rgba(255,255,255,0.8);
            border-color: var(--teal);
            transform: translateX(4px);
        }
        .sidebar-item.active {
            background: linear-gradient(135deg, var(--teal) 0%, var(--teal-deep) 100%);
            color: #fff !important;
            border-color: transparent;
            box-shadow: 0 4px 12px -4px rgba(13, 148, 136, 0.4);
        }
        .sidebar-item.active svg {
            color: #fff !important;
        }
        
        /* Layout Structure */
        .app-layout {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 20; /* Above blobs */
        }
        .sidebar-container {
            width: 280px;
            padding: 1.5rem;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1;
            padding: 1.5rem;
            max-width: calc(100vw - 280px);
        }
        
        @media (max-width: 1024px) {
            .app-layout { flex-direction: column; }
            .sidebar-container { width: 100%; padding: 1rem; flex-direction: row; align-items: center; justify-content: space-between; }
            .main-content { max-width: 100%; padding: 1rem; }
            .sidebar-nav { display: flex; gap: 0.5rem; flex: 1; justify-content: flex-end; }
            .sidebar-item { margin-bottom: 0 !important; padding: 0.5rem 1rem !important; }
            .sidebar-item span { display: none; }
        }
    </style>
@endpush

@section('content')
<div class="app-layout">

    {{-- ═══ SIDEBAR (Glass) ═══ --}}
    <aside class="sidebar-container gs-sidebar">
        <div class="glass-card h-full w-full flex flex-col rounded-3xl p-6 shadow-xl">
            
            {{-- Logo --}}
            <div class="mb-10 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#0D9488] text-white shadow-lg">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.5 3 6 4.5 6 9a6 6 0 1 1-12 0c0-4.5 3.5-6 6-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c0-3.5 0-5.5 2.5-8"/></svg>
                </div>
                <h1 class="font-display text-2xl font-bold tracking-tight text-[var(--ink)]">Visueco</h1>
            </div>

            {{-- Navigation --}}
            <nav class="sidebar-nav flex flex-col gap-3 flex-1">
                <a href="{{ route('dashboard') }}" class="sidebar-item active flex items-center gap-3 rounded-xl border border-transparent px-4 py-3 text-sm font-semibold text-[var(--ink-soft)]">
                    <svg class="h-5 w-5 text-[var(--teal)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('rewards') }}" class="sidebar-item flex items-center gap-3 rounded-xl border border-slate-200/50 bg-white/40 px-4 py-3 text-sm font-semibold text-[var(--ink-soft)] backdrop-blur-sm">
                    <svg class="h-5 w-5 text-[var(--ink-soft)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21"/></svg>
                    <span>Reward Poin</span>
                </a>

                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-item flex items-center gap-3 rounded-xl border border-slate-200/50 bg-white/40 px-4 py-3 text-sm font-semibold text-[var(--ink-soft)] backdrop-blur-sm">
                        <svg class="h-5 w-5 text-[var(--ink-soft)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        <span>Panel Admin</span>
                    </a>
                @endif
            </nav>

            {{-- Logout Profile Area --}}
            <div class="mt-auto pt-6 border-t border-[var(--teal)]/10">
                <div class="mb-4 flex items-center gap-3 px-2">
                    <div class="h-9 w-9 rounded-full bg-[var(--teal)]/10 flex items-center justify-center text-[var(--teal-deep)] font-bold">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <p class="truncate text-sm font-bold text-[var(--ink)]">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-[var(--ink-light)]">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Warga' }}</p>
                    </div>
                </div>
                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full sidebar-item flex items-center justify-center gap-2 rounded-xl border border-red-100 bg-red-50/50 px-4 py-2.5 text-xs font-bold text-red-600 transition hover:bg-red-100 hover:text-red-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ═══ MAIN CONTENT ═══ --}}
    <main class="main-content">
        <div class="mx-auto w-full max-w-5xl gs-main-content">
            
            <div class="mb-8">
                <h2 class="font-display text-3xl font-bold tracking-tight text-[var(--ink)] gs-header">
                    Halo, <span class="font-serif italic text-[var(--teal-deep)]">{{ explode(' ', auth()->user()->name)[0] }}</span>.
                </h2>
                <p class="mt-2 text-sm text-[var(--ink-soft)] gs-header-sub">Ayo kontribusi untuk lingkungan hari ini.</p>
            </div>

            {{-- ═══ ASYMMETRIC GRID 2:3 ═══ --}}
            <div class="grid gap-6 lg:grid-cols-5">

                {{-- ── KIRI (2/5): Kontrol Kamera & Poin ── --}}
                <div class="space-y-6 lg:col-span-2">

                    {{-- Poin Card --}}
                    <div class="glass-card p-6 gs-card">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-xs font-bold uppercase tracking-widest text-[var(--ink-soft)]">Saldo Poin</p>
                            <div class="h-8 w-8 rounded-full bg-[var(--teal)]/10 flex items-center justify-center text-[var(--teal-deep)]">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>
                        <p class="text-5xl font-display font-bold tracking-tight text-[var(--ink)]" id="points-display">
                            {{ auth()->user()->points_balance }}
                        </p>
                        <p class="mt-2 text-sm font-medium text-[var(--teal)]">Terkumpul hari ini</p>
                    </div>

                    {{-- Kamera / Upload Card --}}
                    <div class="glass-card p-6 gs-card">
                        <h2 class="mb-4 text-sm font-bold text-[var(--ink)] uppercase tracking-wide">Scan AI</h2>

                        <div
                            id="dropzone"
                            class="dropzone relative flex cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-[var(--teal)]/20 bg-white/40 px-6 py-12 transition hover:border-[var(--teal)] hover:bg-[var(--teal)]/5"
                        >
                            <div id="dropzone-placeholder" class="text-center">
                                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[var(--teal)]/10 text-[var(--teal-deep)] transition-transform group-hover:scale-110">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                    </svg>
                                </div>
                                <p class="text-sm font-bold text-[var(--ink)]">Ambil Foto / Pilih Gambar</p>
                                <p class="mt-1 text-xs font-medium text-[var(--ink-light)]">Format JPG/PNG (Maks 4 MB)</p>
                            </div>

                            <img id="image-preview" class="hidden max-h-56 rounded-xl object-contain shadow-lg" alt="Preview gambar sampah">

                            <input
                                type="file"
                                name="image"
                                id="image-input"
                                accept="image/jpeg,image/png,image/jpg"
                                capture="environment"
                                class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                            >
                        </div>

                        <button id="btn-scan" disabled class="btn-primary mt-6 w-full rounded-full py-3.5 text-sm font-semibold flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            Analisis Sekarang
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/></svg>
                        </button>
                    </div>
                </div>

                {{-- ── KANAN (3/5): Display Hasil Dinamis ── --}}
                <div class="lg:col-span-3 gs-card">
                    <div class="glass-card h-full p-8 flex flex-col">

                        <x-feedback-alert id="alert-banner" class="mb-6" />

                        {{-- Idle State --}}
                        <div id="state-idle" class="flex flex-1 min-h-[24rem] flex-col items-center justify-center text-center">
                            <div class="mb-6 relative">
                                <div class="absolute inset-0 rounded-full bg-[var(--teal)]/20 blur-xl animate-pulse"></div>
                                <div class="relative flex h-20 w-20 items-center justify-center rounded-2xl bg-white/80 shadow-lg text-[var(--teal-deep)]">
                                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </div>
                            </div>
                            <h3 class="font-display text-xl font-bold text-[var(--ink)]">Menunggu Input Visual</h3>
                            <p class="mt-2 max-w-sm text-sm text-[var(--ink-soft)] leading-relaxed">Model AI lokal kami siap menganalisis jenis sampah Anda. Unggah gambar untuk memulai klasifikasi secara real-time.</p>
                        </div>

                        {{-- Loading State --}}
                        <div id="state-loading" class="hidden flex-1 space-y-6">
                            <div class="skeleton-pulse h-64 w-full rounded-2xl border border-[var(--teal)]/5"></div>
                            <div class="space-y-3">
                                <div class="skeleton-pulse h-6 w-3/4 rounded-lg"></div>
                                <div class="skeleton-pulse h-4 w-1/2 rounded-lg"></div>
                            </div>
                        </div>

                        {{-- Result State --}}
                        <div id="state-result" class="hidden flex-1 flex flex-col justify-between">
                            <div>
                                <div class="mb-6 flex items-start justify-between">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-widest text-[var(--teal)] mb-1">Hasil Scan</p>
                                        <h3 class="font-display text-3xl font-bold text-[var(--ink)]" id="result-label"></h3>
                                    </div>
                                    <x-status-badge tone="credit" id="result-score"></x-status-badge>
                                </div>

                                <div class="flex items-center gap-4 rounded-2xl bg-[var(--teal)]/5 p-5 border border-[var(--teal)]/10">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[var(--teal)] text-white shadow-md">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-[var(--ink)]">Kategori: <span id="result-category" class="text-[var(--teal-deep)]"></span></p>
                                        <p class="text-sm font-bold text-[var(--teal-bright)]">+<span id="result-points"></span> poin didapat</p>
                                    </div>
                                </div>

                                <div class="mt-8">
                                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-[var(--ink-light)]">Instruksi Penanganan</p>
                                    <ul id="result-instructions" class="space-y-2 text-sm font-medium text-[var(--ink-soft)] list-disc pl-5"></ul>
                                </div>
                            </div>

                            {{-- Confirm Block --}}
                            <div id="confirm-block" class="mt-8 pt-6 border-t border-[var(--teal)]/10">
                                <p class="mb-1 text-sm font-semibold text-[var(--ink)]">
                                    Prediksi <span id="confirm-category-name" class="text-[var(--teal-deep)] font-bold">—</span> tepat?
                                </p>
                                <p class="mb-5 text-xs text-[var(--ink-soft)]">Bantu latih AI kami dengan mengonfirmasi hasil ini.</p>

                                <div id="confirm-actions" class="flex flex-wrap gap-2">
                                    <button id="btn-confirm-yes" class="rounded-full bg-[var(--teal)]/10 px-5 py-2 text-xs font-semibold text-[var(--teal-deep)] transition hover:bg-[var(--teal)]/20">Ya, Benar</button>
                                    <button id="btn-confirm-no" class="rounded-full border border-transparent bg-[var(--sand)] px-5 py-2 text-xs font-semibold text-[var(--ink-soft)] transition hover:bg-slate-200">Koreksi</button>
                                </div>

                                <div id="correct-picker" class="mt-4 hidden bg-[var(--sand)] rounded-2xl p-4">
                                    <label for="correct-category" class="mb-2 block text-xs font-semibold text-[var(--ink)]">Pilih yang benar:</label>
                                    <div class="flex gap-2">
                                        <select id="correct-category"
                                            class="flex-1 rounded-xl border-none bg-white px-4 py-2 text-sm text-[var(--ink)] focus:outline-none focus:ring-2 focus:ring-[var(--teal)]/20 shadow-sm">
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                        <button id="btn-submit-correction" class="rounded-xl bg-[var(--teal)] px-5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[var(--teal-deep)]">Kirim</button>
                                    </div>
                                </div>

                                <p id="confirm-thanks" class="mt-4 hidden text-xs font-semibold text-[var(--teal-deep)] flex items-center gap-1.5">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    Terima kasih telah berkontribusi!
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </main>
</div>
@endsection

@push('scripts')
    <script>
        // Layout Animations with GSAP
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof gsap !== 'undefined') {
                const tl = gsap.timeline();
                
                // Sidebar slide in
                tl.fromTo('.gs-sidebar', 
                    { x: -50, opacity: 0 }, 
                    { x: 0, opacity: 1, duration: 0.8, ease: 'power3.out' }
                );
                
                // Header text reveal
                tl.fromTo('.gs-header', 
                    { y: 20, opacity: 0 }, 
                    { y: 0, opacity: 1, duration: 0.6, ease: 'power3.out' },
                    '-=0.4'
                );
                tl.fromTo('.gs-header-sub', 
                    { y: 20, opacity: 0 }, 
                    { y: 0, opacity: 1, duration: 0.6, ease: 'power3.out' },
                    '-=0.4'
                );
                
                // Cards stagger
                tl.fromTo('.gs-card', 
                    { y: 40, opacity: 0 }, 
                    { y: 0, opacity: 1, duration: 0.8, stagger: 0.1, ease: 'power3.out' },
                    '-=0.4'
                );
            }
        });
    </script>
    <script src="{{ asset('js/pages/dashboard.js') }}" defer></script>
@endpush
