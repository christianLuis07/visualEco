<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visueco — Audit Sampah Berbasis AI untuk Bumi yang Lebih Bersih</title>
    <meta name="description" content="Visueco mengubah cara warga memilah sampah: pindai dengan kamera, AI mengenali jenisnya, kumpulkan poin, tukar hadiah. Machine Learning yang kami latih sendiri, mendukung SDGs 12.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:300,400,500,600,400i,500i|plus-jakarta-sans:400,500,600,700" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif

    <style>
        :root {
            --ink: #14211d;
            --ink-soft: #4b5d57;
            --teal: #0d9488;
            --teal-deep: #0f5e57;
            --teal-bright: #14b8a6;
            --cream: #f6f8f5;
            --sand: #eef3ec;
            --line: #e3ebe5;
        }

        * { -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; }

        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            background: var(--cream);
            color: var(--ink);
            overflow-x: hidden;
        }

        .font-display {
            font-family: 'Fraunces', Georgia, serif;
            font-optical-sizing: auto;
        }

        .italic-serif { font-style: italic; font-weight: 400; }

        /* ── Atmospheric background ── */
        .mesh {
            position: fixed;
            inset: 0;
            z-index: -2;
            background:
                radial-gradient(50rem 50rem at 12% -8%, rgba(20, 184, 166, 0.16), transparent 60%),
                radial-gradient(40rem 40rem at 92% 8%, rgba(16, 185, 129, 0.12), transparent 55%),
                radial-gradient(45rem 45rem at 70% 100%, rgba(13, 148, 136, 0.10), transparent 60%),
                var(--cream);
        }
        .grain {
            position: fixed;
            inset: 0;
            z-index: -1;
            opacity: 0.035;
            pointer-events: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        .blob {
            position: absolute;
            border-radius: 47% 53% 60% 40% / 42% 47% 53% 58%;
            filter: blur(2px);
            opacity: 0.5;
            animation: drift 18s ease-in-out infinite;
        }
        @keyframes drift {
            0%, 100% { transform: translate(0, 0) rotate(0deg) scale(1); }
            50% { transform: translate(14px, -22px) rotate(8deg) scale(1.05); }
        }

        /* ── Entrance choreography ── */
        .reveal {
            opacity: 0;
            transform: translateY(22px);
            transition: opacity 0.9s cubic-bezier(.2,.7,.2,1), transform 0.9s cubic-bezier(.2,.7,.2,1);
        }
        .reveal.in { opacity: 1; transform: none; }

        .rise {
            opacity: 0;
            transform: translateY(28px);
            animation: rise 1s cubic-bezier(.2,.7,.2,1) forwards;
        }
        @keyframes rise { to { opacity: 1; transform: none; } }

        .underline-draw {
            background-image: linear-gradient(var(--teal), var(--teal));
            background-size: 0% 2px;
            background-position: 0 100%;
            background-repeat: no-repeat;
            transition: background-size 0.4s ease;
        }
        .group-link:hover .underline-draw { background-size: 100% 2px; }

        .lift { transition: transform 0.5s cubic-bezier(.2,.7,.2,1), box-shadow 0.5s ease, border-color 0.5s ease; }
        .lift:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 48px -28px rgba(15, 94, 87, 0.45);
            border-color: rgba(13, 148, 136, 0.4);
        }

        .btn-primary {
            position: relative;
            overflow: hidden;
            background: var(--teal-deep);
            transition: transform 0.4s cubic-bezier(.2,.7,.2,1), box-shadow 0.4s ease;
            box-shadow: 0 12px 28px -14px rgba(15, 94, 87, 0.7);
        }
        .btn-primary::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, transparent 30%, rgba(255,255,255,0.18) 50%, transparent 70%);
            transform: translateX(-120%);
            transition: transform 0.7s ease;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 18px 34px -16px rgba(15, 94, 87, 0.8); }
        .btn-primary:hover::after { transform: translateX(120%); }

        .marquee-track { animation: marquee 28s linear infinite; }
        @keyframes marquee { to { transform: translateX(-50%); } }

        .ring-soft { box-shadow: 0 1px 0 rgba(20,33,29,0.02), 0 18px 40px -30px rgba(15,94,87,0.35); }

        .dot-grid {
            background-image: radial-gradient(rgba(13,148,136,0.22) 1px, transparent 1px);
            background-size: 18px 18px;
        }

        @media (prefers-reduced-motion: reduce) {
            .reveal, .rise, .blob, .marquee-track { animation: none !important; transition: none !important; opacity: 1 !important; transform: none !important; }
        }
    </style>
</head>
<body>
    <div class="mesh"></div>
    <div class="grain"></div>

    {{-- ════════ NAV ════════ --}}
    <header class="sticky top-0 z-50">
        <div class="mx-auto max-w-6xl px-5 sm:px-8">
            <nav class="mt-4 flex items-center justify-between rounded-2xl border border-[var(--line)] bg-white/70 px-4 py-3 backdrop-blur-xl ring-soft">
                <a href="#top" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[var(--teal-deep)] text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.5 3 6 4.5 6 9a6 6 0 1 1-12 0c0-4.5 3.5-6 6-9Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21c0-3.5 0-5.5 2.5-8"/>
                        </svg>
                    </span>
                    <span class="font-display text-xl font-semibold tracking-tight text-[var(--ink)]">Visueco</span>
                </a>

                <div class="hidden items-center gap-8 md:flex">
                    <a href="#fitur" class="group-link text-sm text-[var(--ink-soft)] hover:text-[var(--ink)]"><span class="underline-draw">Fitur</span></a>
                    <a href="#cara" class="group-link text-sm text-[var(--ink-soft)] hover:text-[var(--ink)]"><span class="underline-draw">Cara Kerja</span></a>
                    <a href="#teknologi" class="group-link text-sm text-[var(--ink-soft)] hover:text-[var(--ink)]"><span class="underline-draw">Teknologi</span></a>
                    <a href="#misi" class="group-link text-sm text-[var(--ink-soft)] hover:text-[var(--ink)]"><span class="underline-draw">Misi</span></a>
                </div>

                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn-primary rounded-xl px-5 py-2.5 text-sm font-semibold text-white">Buka Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="hidden rounded-xl px-4 py-2.5 text-sm font-medium text-[var(--ink)] transition hover:bg-[var(--sand)] sm:block">Masuk</a>
                        <a href="{{ route('register') }}" class="btn-primary rounded-xl px-5 py-2.5 text-sm font-semibold text-white">Mulai Gratis</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    {{-- ════════ HERO ════════ --}}
    <section id="top" class="relative mx-auto max-w-6xl px-5 pt-16 pb-10 sm:px-8 sm:pt-24">
        <div class="blob -left-24 top-10 h-72 w-72 bg-[var(--teal-bright)]"></div>
        <div class="blob right-0 top-40 h-56 w-56 bg-emerald-300" style="animation-delay:-6s"></div>

        <div class="grid items-center gap-12 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <div class="rise inline-flex items-center gap-2 rounded-full border border-[var(--line)] bg-white/70 px-3.5 py-1.5 text-xs font-medium text-[var(--teal-deep)] backdrop-blur" style="animation-delay:.05s">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[var(--teal-bright)] opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-[var(--teal)]"></span>
                    </span>
                    Mendukung SDGs 12 · Konsumsi & Produksi Bertanggung Jawab
                </div>

                <h1 class="rise mt-6 font-display text-5xl font-semibold leading-[1.05] tracking-tight text-[var(--ink)] sm:text-6xl lg:text-7xl" style="animation-delay:.12s">
                    Sampah jadi<br>
                    <span class="italic-serif text-[var(--teal-deep)]">poin</span>, kebiasaan<br>
                    jadi <span class="italic-serif text-[var(--teal-deep)]">gerakan.</span>
                </h1>

                <p class="rise mt-6 max-w-xl text-lg leading-relaxed text-[var(--ink-soft)]" style="animation-delay:.2s">
                    Pindai sampahmu dengan kamera. Kecerdasan buatan Visueco mengenali jenisnya,
                    memberi poin, dan menuntunmu mendaur ulang dengan benar — semua dalam hitungan detik.
                </p>

                <div class="rise mt-9 flex flex-wrap items-center gap-3" style="animation-delay:.28s">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn-primary rounded-xl px-7 py-3.5 text-sm font-semibold text-white">Buka Dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="btn-primary rounded-xl px-7 py-3.5 text-sm font-semibold text-white">Mulai Memindai</a>
                        <a href="#cara" class="group-link inline-flex items-center gap-2 rounded-xl border border-[var(--line)] bg-white/60 px-7 py-3.5 text-sm font-semibold text-[var(--ink)] backdrop-blur transition hover:border-[var(--teal)]/40">
                            Lihat cara kerjanya
                            <svg class="h-4 w-4 transition group-link-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                    @endauth
                </div>

                <div class="rise mt-10 flex items-center gap-6 text-sm text-[var(--ink-soft)]" style="animation-delay:.36s">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-[var(--teal)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        Tanpa pihak ketiga
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-[var(--teal)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        AI yang belajar sendiri
                    </div>
                </div>
            </div>

            {{-- Hero visual: scan result card mockup --}}
            <div class="rise lg:col-span-5" style="animation-delay:.24s">
                <div class="relative">
                    <div class="absolute -inset-4 -z-10 rounded-[2rem] dot-grid opacity-60"></div>

                    <div class="rounded-[1.75rem] border border-[var(--line)] bg-white/80 p-5 shadow-[0_40px_80px_-40px_rgba(15,94,87,0.5)] backdrop-blur-xl">
                        <div class="flex items-center justify-between px-1">
                            <div class="flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-rose-300"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                            </div>
                            <span class="text-[11px] font-medium tracking-wide text-slate-400">visueco · scan</span>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-2xl border border-[var(--line)] bg-gradient-to-br from-teal-50 to-emerald-50">
                            <div class="flex h-44 items-center justify-center">
                                <svg class="h-20 w-20 text-teal-400/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1.75M7.5 6.75h9l-.7 12.2a2 2 0 0 1-2 1.8H10.2a2 2 0 0 1-2-1.8L7.5 6.75Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 10.5v6M13.5 10.5v6"/>
                                </svg>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-teal-100 bg-teal-50/60 p-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-[var(--ink)]">Botol Plastik PET</h3>
                                <span class="rounded-full bg-teal-100 px-2.5 py-0.5 text-[11px] font-semibold text-teal-700">92% akurat</span>
                            </div>
                            <p class="mt-1 text-xs text-[var(--ink-soft)]">Kategori: <span class="font-medium text-[var(--ink)]">Plastik</span></p>
                            <p class="mt-2 font-display text-2xl font-semibold text-[var(--teal-deep)]">+10 <span class="text-sm font-medium text-[var(--ink-soft)]">poin</span></p>

                            <div class="mt-3 space-y-1.5 border-t border-teal-100 pt-3">
                                @foreach(['Kosongkan isi botol', 'Lepaskan label & tutup', 'Remas untuk hemat ruang'] as $i => $step)
                                    <div class="flex items-center gap-2 text-xs text-[var(--ink-soft)]" style="animation: rise .6s {{ 0.5 + $i * 0.12 }}s both;">
                                        <svg class="h-3.5 w-3.5 shrink-0 text-teal-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75"/></svg>
                                        {{ $step }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- floating points badge --}}
                    <div class="absolute -right-4 -top-4 rotate-3 rounded-2xl border border-[var(--line)] bg-white px-4 py-3 shadow-xl">
                        <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">Saldo</p>
                        <p class="font-display text-xl font-semibold text-[var(--teal-deep)]">1.240</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════ MARQUEE STRIP ════════ --}}
    <section class="relative overflow-hidden border-y border-[var(--line)] bg-white/40 py-5 backdrop-blur">
        <div class="marquee-track flex w-max items-center gap-12 whitespace-nowrap text-sm font-medium text-[var(--ink-soft)]">
            @php $chips = ['Plastik', 'Kertas', 'Logam', 'Kaca', 'Organik', 'Daur Ulang', 'Kompos', 'Bebas Pihak Ketiga', 'Self-Hosted ML', 'SDGs 12']; @endphp
            @foreach(array_merge($chips, $chips) as $chip)
                <span class="flex items-center gap-3">
                    <svg class="h-4 w-4 text-[var(--teal)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.5 3 6 4.5 6 9a6 6 0 1 1-12 0c0-4.5 3.5-6 6-9Z"/></svg>
                    {{ $chip }}
                </span>
            @endforeach
        </div>
    </section>

    {{-- ════════ STATS ════════ --}}
    <section class="mx-auto max-w-6xl px-5 py-20 sm:px-8">
        <div class="grid gap-6 sm:grid-cols-3">
            @php $stats = [
                ['5', 'Kategori sampah', 'Plastik, kertas, logam, kaca, organik — dikenali otomatis.'],
                ['< 1 dtk', 'Waktu analisis', 'Dari foto ke hasil klasifikasi, secara langsung di perangkatmu.'],
                ['0', 'Layanan pihak ketiga', 'Seluruh AI berjalan di server kami sendiri. Datamu tetap di sini.'],
            ]; @endphp
            @foreach($stats as $i => $stat)
                <div class="reveal rounded-2xl border border-[var(--line)] bg-white/70 p-7 backdrop-blur lift" style="transition-delay:{{ $i * 0.1 }}s">
                    <p class="font-display text-4xl font-semibold text-[var(--teal-deep)]">{{ $stat[0] }}</p>
                    <p class="mt-2 text-sm font-semibold text-[var(--ink)]">{{ $stat[1] }}</p>
                    <p class="mt-1.5 text-sm leading-relaxed text-[var(--ink-soft)]">{{ $stat[2] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ════════ FITUR ════════ --}}
    <section id="fitur" class="mx-auto max-w-6xl px-5 py-16 sm:px-8">
        <div class="reveal max-w-2xl">
            <p class="text-sm font-semibold uppercase tracking-widest text-[var(--teal)]">Fitur</p>
            <h2 class="mt-3 font-display text-4xl font-semibold leading-tight tracking-tight text-[var(--ink)] sm:text-5xl">
                Semua yang dibutuhkan untuk <span class="italic-serif text-[var(--teal-deep)]">memilah dengan benar.</span>
            </h2>
        </div>

        <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @php $features = [
                ['M6 13.5 4.5 12m0 0L3 13.5M4.5 12v6.75M16.5 6 18 4.5M18 4.5 19.5 6M18 4.5v6.75', 'Pindai dengan Kamera', 'Arahkan kamera ke sampah. Tak perlu mengetik apa pun — cukup satu jepretan.'],
                ['M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z', 'AI yang Belajar', 'Setiap koreksi warga membuat model makin pintar mengenali sampah lokal.'],
                ['M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'Poin Real-Time', 'Saldo bertambah seketika setiap scan berhasil. Transparan, tercatat rapi.'],
                ['M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21', 'Tukar Hadiah Nyata', 'Voucher sembako, tiket wisata, merchandise — tukar poinmu di katalog reward.'],
                ['M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'Verifikasi Voucher', 'Pengurus RT memindai kode penukaran dengan aman, anti-penyalahgunaan.'],
                ['M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z', 'Riwayat Transparan', 'Buku besar poin tiap warga — setiap masuk & keluar tercatat jelas.'],
            ]; @endphp
            @foreach($features as $i => $f)
                <div class="reveal group flex flex-col rounded-2xl border border-[var(--line)] bg-white/70 p-6 backdrop-blur lift" style="transition-delay:{{ ($i % 3) * 0.08 }}s">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[var(--sand)] text-[var(--teal-deep)] transition group-hover:bg-[var(--teal-deep)] group-hover:text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $f[0] }}"/></svg>
                    </span>
                    <h3 class="mt-4 text-base font-semibold text-[var(--ink)]">{{ $f[1] }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-[var(--ink-soft)]">{{ $f[2] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ════════ CARA KERJA ════════ --}}
    <section id="cara" class="relative mx-auto max-w-6xl px-5 py-20 sm:px-8">
        <div class="blob right-10 top-20 h-64 w-64 bg-teal-200" style="animation-delay:-9s"></div>

        <div class="reveal max-w-2xl">
            <p class="text-sm font-semibold uppercase tracking-widest text-[var(--teal)]">Cara Kerja</p>
            <h2 class="mt-3 font-display text-4xl font-semibold leading-tight tracking-tight text-[var(--ink)] sm:text-5xl">
                Empat langkah, <span class="italic-serif text-[var(--teal-deep)]">selesai.</span>
            </h2>
        </div>

        <div class="mt-14 grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-4">
            @php $steps = [
                ['01', 'Pindai', 'Foto sampah yang ingin kamu daur ulang lewat dashboard.'],
                ['02', 'AI Mengenali', 'Model kami mengklasifikasi jenis sampah & tingkat keyakinannya.'],
                ['03', 'Dapat Poin', 'Sampah sah memberi poin yang langsung masuk ke saldomu.'],
                ['04', 'Tukar Hadiah', 'Kumpulkan poin, tukarkan dengan reward nyata dari pengurus.'],
            ]; @endphp
            @foreach($steps as $i => $s)
                <div class="reveal relative" style="transition-delay:{{ $i * 0.1 }}s">
                    <span class="font-display text-6xl font-light text-[var(--teal)]/25">{{ $s[0] }}</span>
                    <h3 class="mt-2 text-lg font-semibold text-[var(--ink)]">{{ $s[1] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-[var(--ink-soft)]">{{ $s[2] }}</p>
                    @if(! $loop->last)
                        <svg class="absolute -right-3 top-6 hidden h-5 w-5 text-[var(--teal)]/40 lg:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 6l6 6-6 6M5 12h14"/></svg>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- ════════ TEKNOLOGI ════════ --}}
    <section id="teknologi" class="mx-auto max-w-6xl px-5 py-20 sm:px-8">
        <div class="overflow-hidden rounded-[2rem] border border-[var(--line)] bg-gradient-to-br from-[#0f5e57] to-[#0b3d39] text-white">
            <div class="grid gap-10 p-9 sm:p-14 lg:grid-cols-2">
                <div class="reveal">
                    <p class="text-sm font-semibold uppercase tracking-widest text-teal-300">Di Balik Layar</p>
                    <h2 class="mt-3 font-display text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">
                        Machine Learning yang kami latih <span class="italic-serif text-teal-200">sendiri.</span>
                    </h2>
                    <p class="mt-5 max-w-md leading-relaxed text-teal-50/80">
                        Tidak ada API berbayar, tidak ada data yang dikirim ke luar. Model klasifikasi
                        sampah berjalan penuh di server kami — dan semakin akurat setiap kali warga
                        mengonfirmasi hasilnya.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-2.5">
                        @foreach(['MobileNetV2', 'TensorFlow', 'FastAPI', 'Laravel 12', 'MySQL', 'Docker'] as $tech)
                            <span class="rounded-full border border-white/15 bg-white/5 px-3.5 py-1.5 text-xs font-medium text-teal-50 backdrop-blur">{{ $tech }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="reveal grid gap-4 sm:grid-cols-2" style="transition-delay:.12s">
                    @php $tech = [
                        ['Self-Hosted', 'Seluruh inferensi & pelatihan berjalan di kontainer milik sendiri.'],
                        ['Transfer Learning', 'MobileNetV2 sebagai fondasi, kepala classifier dilatih ulang dari data lokal.'],
                        ['Privasi Terjaga', 'Gambar tak pernah meninggalkan infrastruktur Visueco.'],
                        ['Anti-Fraud', 'Validasi keyakinan & penguncian transaksi mencegah manipulasi poin.'],
                    ]; @endphp
                    @foreach($tech as $t)
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur transition hover:bg-white/10">
                            <h3 class="text-sm font-semibold text-white">{{ $t[0] }}</h3>
                            <p class="mt-1.5 text-xs leading-relaxed text-teal-50/70">{{ $t[1] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ════════ MISI / SDGs ════════ --}}
    <section id="misi" class="relative mx-auto max-w-6xl px-5 py-20 sm:px-8">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <div class="reveal">
                <p class="text-sm font-semibold uppercase tracking-widest text-[var(--teal)]">Misi Kami</p>
                <h2 class="mt-3 font-display text-4xl font-semibold leading-tight tracking-tight text-[var(--ink)] sm:text-5xl">
                    Setiap botol yang dipilah, <span class="italic-serif text-[var(--teal-deep)]">satu langkah</span> menuju bumi yang lebih sehat.
                </h2>
                <p class="mt-6 max-w-lg leading-relaxed text-[var(--ink-soft)]">
                    Visueco lahir untuk mendukung <strong class="font-semibold text-[var(--ink)]">Sustainable
                    Development Goals butir 12</strong> — Konsumsi dan Produksi yang Bertanggung Jawab.
                    Kami percaya perubahan besar dimulai dari kebiasaan kecil yang dihargai dan dirayakan
                    di tingkat lingkungan, RT demi RT.
                </p>

                <div class="mt-8 space-y-3">
                    @foreach(['Mendorong warga memilah sampah sejak dari rumah', 'Memberi insentif nyata atas perilaku ramah lingkungan', 'Membangun data audit sampah yang transparan & terbuka'] as $point)
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-100 text-teal-700">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <p class="text-sm leading-relaxed text-[var(--ink-soft)]">{{ $point }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="reveal relative" style="transition-delay:.12s">
                <div class="absolute -inset-3 -z-10 rounded-[2rem] dot-grid opacity-50"></div>
                <div class="rounded-[2rem] border border-[var(--line)] bg-white/70 p-9 backdrop-blur ring-soft">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[var(--teal-deep)] text-white">
                        <span class="font-display text-2xl font-semibold">12</span>
                    </span>
                    <h3 class="mt-5 font-display text-2xl font-semibold text-[var(--ink)]">Responsible Consumption &amp; Production</h3>
                    <p class="mt-3 leading-relaxed text-[var(--ink-soft)]">
                        Tujuan ke-12 dari 17 Tujuan Pembangunan Berkelanjutan PBB. Visueco menerjemahkannya
                        menjadi aksi sehari-hari yang menyenangkan dan terukur.
                    </p>
                    <div class="mt-6 grid grid-cols-3 gap-3 border-t border-[var(--line)] pt-6 text-center">
                        <div>
                            <p class="font-display text-2xl font-semibold text-[var(--teal-deep)]">68jt</p>
                            <p class="mt-1 text-[11px] leading-tight text-[var(--ink-soft)]">ton sampah/tahun di Indonesia</p>
                        </div>
                        <div>
                            <p class="font-display text-2xl font-semibold text-[var(--teal-deep)]">7%</p>
                            <p class="mt-1 text-[11px] leading-tight text-[var(--ink-soft)]">yang berhasil didaur ulang</p>
                        </div>
                        <div>
                            <p class="font-display text-2xl font-semibold text-[var(--teal-deep)]">∞</p>
                            <p class="mt-1 text-[11px] leading-tight text-[var(--ink-soft)]">potensi bila kita bergerak bersama</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════ CTA ════════ --}}
    <section class="mx-auto max-w-6xl px-5 pb-24 sm:px-8">
        <div class="reveal relative overflow-hidden rounded-[2rem] border border-[var(--line)] bg-white/70 px-8 py-16 text-center backdrop-blur sm:px-16">
            <div class="blob -left-10 -top-10 h-48 w-48 bg-emerald-200"></div>
            <div class="blob -right-10 bottom-0 h-48 w-48 bg-teal-200" style="animation-delay:-7s"></div>

            <h2 class="relative font-display text-4xl font-semibold leading-tight tracking-tight text-[var(--ink)] sm:text-5xl">
                Siap mengubah sampah<br>jadi <span class="italic-serif text-[var(--teal-deep)]">kebaikan?</span>
            </h2>
            <p class="relative mx-auto mt-5 max-w-md leading-relaxed text-[var(--ink-soft)]">
                Bergabunglah dengan gerakan memilah sampah yang menyenangkan. Gratis, dan dimulai hari ini.
            </p>
            <div class="relative mt-9 flex flex-wrap justify-center gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn-primary rounded-xl px-8 py-3.5 text-sm font-semibold text-white">Buka Dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="btn-primary rounded-xl px-8 py-3.5 text-sm font-semibold text-white">Daftar Sekarang</a>
                    <a href="{{ route('login') }}" class="rounded-xl border border-[var(--line)] bg-white/60 px-8 py-3.5 text-sm font-semibold text-[var(--ink)] backdrop-blur transition hover:border-[var(--teal)]/40">Saya sudah punya akun</a>
                @endauth
            </div>
        </div>
    </section>

    {{-- ════════ FOOTER ════════ --}}
    <footer class="border-t border-[var(--line)] bg-white/40 backdrop-blur">
        <div class="mx-auto max-w-6xl px-5 py-12 sm:px-8">
            <div class="flex flex-col items-start justify-between gap-8 sm:flex-row sm:items-center">
                <div>
                    <a href="#top" class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[var(--teal-deep)] text-white">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.5 3 6 4.5 6 9a6 6 0 1 1-12 0c0-4.5 3.5-6 6-9Z"/></svg>
                        </span>
                        <span class="font-display text-xl font-semibold tracking-tight text-[var(--ink)]">Visueco</span>
                    </a>
                    <p class="mt-3 max-w-xs text-sm leading-relaxed text-[var(--ink-soft)]">
                        Audit sampah berbasis AI untuk konsumsi & produksi yang bertanggung jawab.
                    </p>
                </div>

                <div class="flex flex-wrap gap-x-10 gap-y-3 text-sm">
                    <a href="#fitur" class="text-[var(--ink-soft)] transition hover:text-[var(--teal-deep)]">Fitur</a>
                    <a href="#cara" class="text-[var(--ink-soft)] transition hover:text-[var(--teal-deep)]">Cara Kerja</a>
                    <a href="#teknologi" class="text-[var(--ink-soft)] transition hover:text-[var(--teal-deep)]">Teknologi</a>
                    <a href="#misi" class="text-[var(--ink-soft)] transition hover:text-[var(--teal-deep)]">Misi</a>
                </div>
            </div>

            <div class="mt-10 flex flex-col items-start justify-between gap-3 border-t border-[var(--line)] pt-6 text-xs text-[var(--ink-soft)] sm:flex-row sm:items-center">
                <p>© {{ date('Y') }} Visueco. Dibuat untuk mendukung SDGs 12.</p>
                <p>Machine Learning self-hosted · Tanpa pihak ketiga</p>
            </div>
        </div>
    </footer>

    <script>
        // Scroll-reveal choreography
        const io = new IntersectionObserver((entries) => {
            entries.forEach((e) => {
                if (e.isIntersecting) {
                    e.target.classList.add('in');
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

        document.querySelectorAll('.reveal').forEach((el) => io.observe(el));
    </script>
</body>
</html>
