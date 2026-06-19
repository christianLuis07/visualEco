<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Visueco')</title>

    <!-- Font & Styles matching Welcome page -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,500;1,9..144,400;1,9..144,500&display=swap" rel="stylesheet">
    
    <!-- GSAP for Cursor & Reveals -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --ink: #111827;
            --ink-soft: #4b5563;
            --cream: #ffffff;
            --teal: #0d9488;
            --teal-deep: #0f766e;
            --teal-bright: #14b8a6;
            --aww-ease: cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            background-color: var(--cream);
            color: var(--ink);
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: -0.02em; }
        .font-serif { font-family: 'Fraunces', serif; }

        /* Animated Blobs (Organic floating) */
        .blob-bg {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.3;
            pointer-events: none;
            z-index: 0;
            will-change: transform;
        }

        /* Cursor Glow */
        #cursor-glow {
            position: fixed;
            top: 0; left: 0;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(20, 184, 166, 0.15) 0%, rgba(20, 184, 166, 0) 70%);
            pointer-events: none;
            z-index: 10;
            transform: translate(-50%, -50%);
            mix-blend-mode: multiply;
        }

        /* Glass Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(13, 148, 136, 0.15);
            box-shadow: 0 32px 64px -16px rgba(13, 148, 136, 0.1);
            border-radius: 1.5rem;
            position: relative;
            z-index: 20;
        }

        /* Forms */
        .input-glass {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(13, 148, 136, 0.2);
            transition: all 0.3s var(--aww-ease);
        }
        .input-glass:focus {
            border-color: var(--teal);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
            outline: none;
            background: #fff;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--teal) 0%, var(--teal-deep) 100%);
            color: #fff;
            box-shadow: 0 8px 20px -6px rgba(13, 148, 136, 0.4);
            transition: transform 0.4s var(--aww-ease), box-shadow 0.4s var(--aww-ease);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px -8px rgba(13, 148, 136, 0.6);
        }

        /* Back to home */
        .back-link {
            transition: all 0.3s var(--aww-ease);
        }
        .back-link:hover {
            transform: translateX(-4px);
            color: var(--teal-deep);
        }
    </style>

    @stack('styles')
</head>
<body class="relative min-h-screen">

    {{-- Cursor Tracker --}}
    <div id="cursor-glow"></div>

    {{-- Background Orbs --}}
    <div class="blob-bg bg-emerald-200/60 w-[400px] h-[400px] top-[-100px] right-[-100px]" id="blob1"></div>
    <div class="blob-bg bg-teal-200/50 w-[500px] h-[500px] bottom-[-150px] left-[-150px]" id="blob2"></div>

    {{-- Back to Home (Hanya untuk tamu/halaman auth) --}}
    @guest
    <div class="fixed top-8 left-8 z-50 gs-reveal">
        <a href="{{ url('/') }}" class="back-link flex items-center gap-2 text-sm font-semibold text-[var(--ink-soft)] bg-white/50 backdrop-blur px-4 py-2 rounded-full border border-[var(--teal)]/10 shadow-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            Kembali
        </a>
    </div>
    @endguest

    @yield('content')

    {{-- Logic for Animations --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Cursor Glow Tracking
            const cursorGlow = document.getElementById('cursor-glow');
            
            // GSAP quickTo for ultra-smooth cursor tracking
            let xTo = gsap.quickTo(cursorGlow, "x", {duration: 0.4, ease: "power3"}),
                yTo = gsap.quickTo(cursorGlow, "y", {duration: 0.4, ease: "power3"});

            window.addEventListener("mousemove", e => {
                xTo(e.clientX);
                yTo(e.clientY);
            });

            // Ambient background blob animation
            gsap.to("#blob1", {
                x: 'random(-50, 50)',
                y: 'random(-50, 50)',
                scale: 'random(0.9, 1.1)',
                duration: 'random(8, 12)',
                ease: 'sine.inOut',
                repeat: -1,
                yoyo: true
            });
            gsap.to("#blob2", {
                x: 'random(-80, 80)',
                y: 'random(-80, 80)',
                scale: 'random(0.8, 1.2)',
                duration: 'random(10, 15)',
                ease: 'sine.inOut',
                repeat: -1,
                yoyo: true
            });

            // Load Animation Sequence
            const tl = gsap.timeline();
            tl.fromTo('.gs-reveal', 
                { y: 30, opacity: 0 }, 
                { y: 0, opacity: 1, duration: 1, stagger: 0.1, ease: 'power3.out' }
            );

            // SweetAlert Hooks
            @if(session('success'))
                Swal.fire({
                    title: 'Berhasil!',
                    text: "{{ session('success') }}",
                    icon: 'success',
                    confirmButtonColor: '#0D9488',
                    confirmButtonText: 'Oke'
                });
            @endif

            @if(session('status') && session('status') != 'verification-link-sent')
                Swal.fire({
                    title: 'Informasi',
                    text: "{{ session('status') }}",
                    icon: 'success',
                    confirmButtonColor: '#0D9488',
                    confirmButtonText: 'Oke'
                });
            @endif

            @if(session('error'))
                Swal.fire({
                    title: 'Gagal!',
                    text: "{{ session('error') }}",
                    icon: 'error',
                    confirmButtonColor: '#0D9488',
                    confirmButtonText: 'Tutup'
                });
            @endif
        });
    </script>
    
    @stack('scripts')
</body>
</html>
