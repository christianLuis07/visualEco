<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visueco — High-End AI Waste Audit</title>
    <meta name="description" content="Visueco mengubah cara warga memilah sampah. Pindai dengan kamera, AI mengenali jenisnya.">

    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
    
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif

    <!-- GSAP & ScrollTrigger -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="{{ asset('js/welcome.js') }}" defer></script>
</head>
<body class="antialiased selection:bg-[var(--teal)] selection:text-white relative bg-[var(--cream)] overflow-x-hidden">
    
    {{-- ════════ BACKGROUND BLOBS (Fixed & Animated via GSAP) ════════ --}}
    <div class="fixed inset-0 pointer-events-none z-0">
        <div class="blob-gsap bg-emerald-200/50 w-[40vw] h-[40vw] rounded-full blur-[100px] absolute top-[-10%] left-[-10%]"></div>
        <div class="blob-gsap bg-teal-200/40 w-[30vw] h-[30vw] rounded-full blur-[80px] absolute top-[40%] right-[-5%]"></div>
        <div class="blob-gsap bg-cyan-200/40 w-[50vw] h-[50vw] rounded-full blur-[120px] absolute bottom-[-15%] left-[15%]"></div>
    </div>

    {{-- ════════ NAV (Floating Pill) ════════ --}}
    <header class="fixed top-0 left-0 right-0 z-50 px-4 pointer-events-none">
        <div class="pointer-events-auto mt-6 mx-auto w-max gs-nav-pill opacity-0 translate-y-[-20px]">
            <nav class="nav-pill flex items-center gap-8 rounded-full px-6 py-3">
                <a href="#top" class="flex items-center gap-2 group">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--teal-deep)] text-white shadow-md transition-transform duration-700 group-hover:rotate-[360deg]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.5 3 6 4.5 6 9a6 6 0 1 1-12 0c0-4.5 3.5-6 6-9Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21c0-3.5 0-5.5 2.5-8"/>
                        </svg>
                    </span>
                    <span class="font-display text-lg font-semibold tracking-tight text-[var(--ink)]">Visueco</span>
                </a>

                <div class="hidden items-center gap-8 md:flex">
                    <a href="#fitur" class="text-sm font-medium text-[var(--ink-soft)] hover:text-[var(--teal)] transition-colors duration-300">Platform</a>
                    <a href="#cara" class="text-sm font-medium text-[var(--ink-soft)] hover:text-[var(--teal)] transition-colors duration-300">Metodologi</a>
                </div>

                <div class="flex items-center gap-3 border-l border-[var(--line)] pl-5">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn-primary rounded-full px-6 py-2.5 text-sm font-semibold flex items-center gap-2">
                            Dashboard
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25"/></svg>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="hidden text-sm font-medium text-[var(--ink-soft)] hover:text-[var(--teal)] transition-colors duration-300 sm:block">Log in</a>
                        <a href="{{ route('register') }}" class="btn-primary rounded-full px-6 py-2.5 text-sm font-medium flex items-center gap-2">
                            Mulai Sekarang
                        </a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    {{-- ════════ SCROLL PINNED CONTAINER ════════ --}}
    <main id="gsap-scroll-container" class="relative z-10 w-full">
        
        {{-- SECTION 1: HERO (Pinned & Fades out) --}}
        <section id="top" class="gs-panel h-[100vh] w-full flex items-center justify-center px-4 relative">
            <div class="grid w-full max-w-7xl items-center gap-16 lg:grid-cols-12 mx-auto">
                <div class="lg:col-span-6 gs-hero-text">
                    <div class="inline-flex items-center gap-2 rounded-full border border-[var(--teal)]/20 bg-[var(--teal)]/5 px-4 py-1.5 text-xs font-semibold text-[var(--teal-deep)] mb-8">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[var(--teal-bright)] opacity-50"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-[var(--teal)]"></span>
                        </span>
                        SDGs 12 · Self-Hosted AI
                    </div>

                    <h1 class="font-display text-5xl font-semibold leading-[1.05] tracking-tight text-[var(--ink)] sm:text-6xl lg:text-7xl overflow-hidden">
                        <div class="gs-line">Sampah jadi <span class="font-serif italic text-[var(--teal-deep)]">poin</span>,</div>
                        <div class="gs-line">kebiasaan jadi</div>
                        <div class="gs-line"><span class="font-serif italic text-[var(--teal-deep)]">gerakan.</span></div>
                    </h1>
                    
                    <p class="mt-8 max-w-lg text-lg leading-relaxed text-[var(--ink-soft)] gs-fade">
                        Infrastruktur kecerdasan buatan untuk masa depan manajemen sampah komunal. Pindai dengan kamera, kenali jenisnya, dan kumpulkan poin secara instan.
                    </p>

                    <div class="mt-12 flex flex-wrap items-center gap-4 gs-fade">
                        <a href="{{ route('register') }}" class="btn-primary rounded-full px-8 py-4 text-base font-semibold flex items-center gap-3 group">
                            <span>Mulai Memindai</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-4 w-4 transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25"/></svg>
                        </a>
                        <a href="#cara" class="btn-outline rounded-full px-8 py-4 text-base font-medium flex items-center gap-3 backdrop-blur-sm">
                            Cara Kerja
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-6 relative gs-hero-img">
                    @php
                        $heroImage = \File::glob(public_path('images/hero_app_scan*.jpg'));
                        $heroImagePath = count($heroImage) > 0 ? 'images/' . basename($heroImage[0]) : '';
                    @endphp
                    <div class="editorial-img relative w-full aspect-[4/5] lg:aspect-square origin-center">
                        <img src="{{ asset($heroImagePath) }}" alt="Scanning App" class="absolute inset-0 h-full w-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                    </div>
                    
                    <div class="gs-hero-badge absolute -bottom-6 -left-6 rounded-2xl border border-white/40 bg-white/70 p-6 backdrop-blur-xl shadow-2xl">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[var(--teal-deep)] text-white shadow-lg">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            </div>
                            <div>
                                <p class="font-display text-sm font-bold text-[var(--ink)]">Akurasi 99%</p>
                                <p class="text-xs text-[var(--ink-soft)]">Real-time AI Scan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- BRAND TICKER (Scrolls horizontally with page scroll) --}}
        <section class="gs-ticker-section border-y border-[var(--teal)]/10 py-8 overflow-hidden bg-[var(--teal)]/5 backdrop-blur-md relative z-10">
            <div class="gs-ticker-track flex w-max items-center gap-24 text-xl font-bold uppercase tracking-[0.2em] text-[var(--teal-deep)]/70">
                @php $chips = ['Botol Plastik', 'Kertas Karton', 'Kaleng Logam', 'Pecahan Kaca', 'Sampah Organik', 'Akurasi 99%', 'AI Lokal']; @endphp
                @foreach(array_merge($chips, $chips, $chips, $chips) as $chip)
                    <span class="flex items-center gap-6 whitespace-nowrap">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.5 3 6 4.5 6 9a6 6 0 1 1-12 0c0-4.5 3.5-6 6-9Z"/></svg>
                        {{ $chip }}
                    </span>
                @endforeach
            </div>
        </section>

        {{-- SECTION 2: FEATURES (Parallax Reveal) --}}
        <section id="fitur" class="gs-panel min-h-[100vh] w-full flex items-center py-32 px-4 relative">
            <div class="mx-auto max-w-7xl w-full">
                <div class="max-w-2xl mb-16 gs-feat-title">
                    <span class="text-[var(--teal-deep)] font-semibold tracking-wider text-sm uppercase mb-4 block">Infrastruktur</span>
                    <h2 class="font-display text-4xl font-semibold tracking-tight text-[var(--ink)] sm:text-5xl overflow-hidden">
                        <div class="gs-line">Segala yang dibutuhkan</div>
                        <div class="gs-line">untuk memilah dengan <span class="font-serif italic text-[var(--teal-deep)]">sempurna</span>.</div>
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 gs-feat-cards">
                    {{-- Big Image Panel --}}
                    <div class="bento-card md:col-span-8 p-0 h-[450px] gs-card">
                        @php
                            $featImage = \File::glob(public_path('images/features_macro_*.jpg'));
                            $featImagePath = count($featImage) > 0 ? 'images/' . basename($featImage[0]) : '';
                        @endphp
                        <div class="gs-parallax-img absolute inset-0 w-full h-[120%] -top-[10%]">
                            <img src="{{ asset($featImagePath) }}" alt="Macro Waste" class="w-full h-full object-cover">
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-b from-black/0 to-black/80"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-10 gs-card-content">
                            <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 backdrop-blur-md mb-6 border border-white/20">
                                <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 13.5 4.5 12m0 0L3 13.5M4.5 12v6.75M16.5 6 18 4.5M18 4.5 19.5 6M18 4.5v6.75"/></svg>
                            </div>
                            <h3 class="font-display text-3xl font-semibold text-white mb-3">Klasifikasi Instan.</h3>
                            <p class="text-white/90 max-w-md text-base leading-relaxed">Kamera mendeteksi jenis sampah dalam hitungan milidetik. Tanpa mengetik, cukup arahkan kamera dan biarkan AI kami bekerja.</p>
                        </div>
                    </div>

                    {{-- Stat Panel --}}
                    <div class="bento-card md:col-span-4 p-10 flex flex-col justify-between bg-gradient-teal gs-card">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-white/10 backdrop-blur-md border border-white/10">
                            <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </div>
                        <div class="mt-12 gs-card-content">
                            <h3 class="font-display text-3xl font-semibold text-white mb-3">Poin Real-Time.</h3>
                            <p class="text-white/80 text-base leading-relaxed">Setiap pindaian sah langsung dikonversi menjadi poin yang dicatat pada buku besar.</p>
                        </div>
                    </div>

                    {{-- Text Panel --}}
                    <div class="bento-card md:col-span-5 p-10 flex flex-col justify-between gs-card">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-[var(--sand)] text-[var(--teal-deep)] mb-12">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </div>
                        <div class="gs-card-content">
                            <h3 class="font-display text-2xl font-semibold text-[var(--ink)] mb-3">Self-Hosted ML.</h3>
                            <p class="text-[var(--ink-soft)] text-base leading-relaxed">Tak ada data yang dikirim ke API pihak ketiga. Mesin prediksi berjalan aman di infrastruktur lokal.</p>
                        </div>
                    </div>

                    {{-- Text Panel 2 --}}
                    <div class="bento-card md:col-span-7 p-10 flex flex-col justify-between gs-card">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-[var(--sand)] text-[var(--teal-deep)] mb-12">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21"/></svg>
                        </div>
                        <div class="gs-card-content">
                            <h3 class="font-display text-2xl font-semibold text-[var(--ink)] mb-3">Tukar Hadiah Fisik.</h3>
                            <p class="text-[var(--ink-soft)] text-base leading-relaxed max-w-lg">Akumulasi poinmu dapat ditukar menjadi voucher belanja, sembako, atau merchandise yang dikelola langsung oleh pengurus lingkungan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- SECTION 3: HOW IT WORKS (Sticky Scrollytelling) --}}
        <section id="cara" class="gs-panel relative w-full px-4 py-32 bg-white">
            <div class="mx-auto max-w-7xl">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-20">
                    <div class="gs-cara-left lg:sticky lg:top-32 lg:h-max">
                        <span class="text-[var(--teal-deep)] font-semibold tracking-wider text-sm uppercase mb-4 block">Metodologi</span>
                        <h2 class="font-display text-4xl font-semibold tracking-tight text-[var(--ink)] sm:text-5xl mb-12 overflow-hidden">
                            <div class="gs-line">Empat langkah,</div>
                            <div class="gs-line"><span class="font-serif italic text-[var(--teal-deep)]">selesai.</span></div>
                        </h2>
                        
                        <div class="gs-cara-image mt-8 editorial-img relative w-full aspect-square overflow-hidden rounded-[2rem] shadow-2xl">
                            @php
                                $gridImage = \File::glob(public_path('images/how_it_works*.jpg'));
                                $gridImagePath = count($gridImage) > 0 ? 'images/' . basename($gridImage[0]) : '';
                            @endphp
                            <div class="gs-parallax-img absolute inset-0 w-full h-[120%] -top-[10%]">
                                <img src="{{ asset($gridImagePath) }}" alt="Grid" class="w-full h-full object-cover">
                            </div>
                        </div>
                    </div>

                    <div class="gs-cara-right flex flex-col gap-12 lg:pt-32 pb-32">
                        @php $steps = [
                            ['01', 'Pindai', 'Arahkan kamera smartphone ke objek sampah. Sistem akan menggunakan model Visi Komputer terbaru.'],
                            ['02', 'Analisis', 'Model AI lokal mengenali jenis dan kategorinya dalam hitungan milidetik secara edge-computing.'],
                            ['03', 'Poin Masuk', 'Saldo tercatat otomatis pada akun pengguna, divalidasi oleh sistem lokal tanpa delay jaringan.'],
                            ['04', 'Hadiah', 'Tukarkan poin yang telah dikumpulkan di pusat pengelolaan terdekat untuk mendapatkan voucher bernilai.']
                        ]; @endphp
                        @foreach($steps as $i => $s)
                            <div class="gs-step flex flex-col gap-4 items-start group min-h-[30vh] justify-center">
                                <span class="font-display text-6xl font-light text-[var(--teal)]/20 transition-colors duration-500 gs-step-num">{{ $s[0] }}</span>
                                <div class="pt-4 border-t border-[var(--line)] w-full">
                                    <h3 class="font-display text-3xl font-semibold text-[var(--ink)] transition-colors duration-300">{{ $s[1] }}</h3>
                                    <p class="text-xl text-[var(--ink-soft)] mt-4 leading-relaxed">{{ $s[2] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA & FOOTER --}}
        <section class="gs-footer-wrap relative w-full bg-[var(--sand)] pt-32 pb-16 px-4 z-20">
            <div class="mx-auto max-w-5xl text-center mb-32 gs-cta">
                <div class="bento-card p-16 sm:p-24 bg-white/60 backdrop-blur-xl border border-white/50 shadow-2xl">
                    <h2 class="font-display text-4xl font-semibold tracking-tight text-[var(--ink)] sm:text-5xl overflow-hidden">
                        <div class="gs-line">Jadilah bagian dari</div>
                        <div class="gs-line"><span class="font-serif italic text-[var(--teal-deep)]">solusi hari ini.</span></div>
                    </h2>
                    <p class="mx-auto mt-6 max-w-md text-lg text-[var(--ink-soft)] leading-relaxed gs-fade">
                        Tanpa biaya. Hanya gerakan tulus menjaga bumi dengan dukungan teknologi tingkat lanjut.
                    </p>
                    <div class="mt-12 flex justify-center gs-fade">
                        <a href="{{ route('register') }}" class="btn-primary rounded-full px-10 py-5 text-lg font-semibold flex items-center gap-3 hover:scale-105 transition-transform duration-300">
                            Bergabung Sekarang
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <footer class="mx-auto max-w-7xl pt-16 border-t border-[var(--line)]">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-base text-[var(--ink-soft)]">
                    <div>
                        <span class="font-display text-2xl font-semibold text-[var(--ink)] mb-4 flex items-center gap-2">
                            <svg class="h-6 w-6 text-[var(--teal-deep)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.5 3 6 4.5 6 9a6 6 0 1 1-12 0c0-4.5 3.5-6 6-9Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21c0-3.5 0-5.5 2.5-8"/>
                            </svg>
                            Visueco
                        </span>
                        <p class="max-w-xs leading-relaxed">Infrastruktur AI untuk masa depan manajemen sampah komunal yang berkelanjutan.</p>
                    </div>
                    
                    <div class="flex flex-col gap-4">
                        <strong class="font-display text-sm font-bold text-[var(--ink)] tracking-wider uppercase mb-2">Platform</strong>
                        <a href="#fitur" class="hover:text-[var(--teal)] transition-colors">Fitur Utama</a>
                        <a href="#cara" class="hover:text-[var(--teal)] transition-colors">Metodologi</a>
                    </div>
                    
                    <div class="flex flex-col gap-4">
                        <strong class="font-display text-sm font-bold text-[var(--ink)] tracking-wider uppercase mb-2">Sistem</strong>
                        <a href="{{ route('login') }}" class="hover:text-[var(--teal)] transition-colors">Portal Masuk</a>
                        <a href="{{ route('register') }}" class="hover:text-[var(--teal)] transition-colors">Registrasi Warga</a>
                    </div>
                </div>
                
                <div class="mt-20 flex flex-col md:flex-row justify-between items-center gap-4 text-xs font-semibold uppercase tracking-widest text-[var(--ink-light)]">
                    <p>&copy; {{ date('Y') }} Visueco Project.</p>
                    <p>SDG 12 · Self-Hosted Local AI</p>
                </div>
            </footer>
        </section>
    </main>
</body>
</html>
