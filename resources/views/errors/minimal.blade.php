<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Visueco</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-[var(--cream)] text-[var(--ink)] overflow-hidden">
    <!-- Background Decor -->
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[20%] -right-[10%] w-[70vw] h-[70vw] rounded-full bg-[var(--teal-bright)]/20 blur-[120px] mix-blend-multiply opacity-70 animate-pulse"></div>
        <div class="absolute -bottom-[20%] -left-[10%] w-[60vw] h-[60vw] rounded-full bg-[var(--teal)]/20 blur-[100px] mix-blend-multiply opacity-50"></div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 flex min-h-screen flex-col items-center justify-center p-6 text-center">
        <!-- Glass Container -->
        <div id="error-container" class="relative w-full max-w-2xl overflow-hidden rounded-[2.5rem] bg-white/70 p-10 md:p-14 shadow-[0_8px_32px_rgba(13,148,136,0.08)] backdrop-blur-2xl border border-[var(--teal)]/10">
            <!-- Glare effect -->
            <div class="absolute -left-[100%] top-0 h-[200%] w-1/2 -skew-x-12 bg-gradient-to-r from-transparent via-white/80 to-transparent opacity-60 mix-blend-overlay"></div>

            @php
                $code = trim($__env->yieldContent('code'));
                $desc = 'Terjadi sebuah kesalahan yang tidak terduga pada sistem kami.';
                
                if ($code === '404') {
                    $desc = 'Sepertinya Anda tersesat. Halaman yang Anda cari mungkin telah dihapus, diubah namanya, atau tidak pernah ada.';
                } elseif ($code === '403') {
                    $desc = 'Anda tidak memiliki hak akses untuk melihat halaman ini. Silakan hubungi administrator jika ini adalah sebuah kesalahan.';
                } elseif ($code === '401') {
                    $desc = 'Sesi Anda telah habis atau Anda belum masuk. Silakan masuk kembali untuk melanjutkan.';
                } elseif ($code === '419') {
                    $desc = 'Sesi halaman ini telah kedaluwarsa karena terlalu lama tidak ada aktivitas. Silakan segarkan halaman dan coba lagi.';
                } elseif ($code === '429') {
                    $desc = 'Anda melakukan terlalu banyak permintaan dalam waktu singkat. Silakan tunggu beberapa saat.';
                } elseif ($code === '500') {
                    $desc = 'Terjadi kesalahan internal pada server kami. Tim teknis kami sedang memperbaikinya.';
                } elseif ($code === '503') {
                    $desc = 'Layanan saat ini sedang tidak tersedia karena pemeliharaan rutin. Silakan kembali lagi nanti.';
                }
            @endphp

            <div class="mb-2">
                <span class="inline-block bg-gradient-to-br from-[var(--teal)] to-[var(--teal-deep)] bg-clip-text text-transparent text-[7rem] md:text-[9rem] font-extrabold leading-none tracking-tighter drop-shadow-sm">
                    @yield('code')
                </span>
            </div>
            
            <h1 class="mb-4 text-3xl font-bold text-[var(--ink)] md:text-4xl">
                @yield('message')
            </h1>
            
            <p class="mb-10 text-base md:text-lg text-[var(--ink-soft)] max-w-md mx-auto leading-relaxed">
                {{ $desc }}
            </p>
            
            <a href="{{ url('/') }}" class="inline-flex items-center justify-center gap-3 rounded-full bg-[var(--teal)] px-8 py-4 text-sm md:text-base font-semibold text-white shadow-xl shadow-[var(--teal)]/20 transition-all hover:-translate-y-1 hover:shadow-2xl hover:shadow-[var(--teal)]/30 active:translate-y-0 group">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transition-transform group-hover:-translate-x-1">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
                Kembali ke Beranda
            </a>
        </div>
        
        <!-- Logo Bottom -->
        <div class="mt-12 opacity-80 hover:opacity-100 transition-opacity">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--teal-deep)] text-white shadow-lg">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" />
                        <path d="M12 6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6z" />
                    </svg>
                </div>
                <span class="text-xl font-bold text-[var(--ink)] tracking-tight">Visueco</span>
            </a>
        </div>
    </div>
    
    <script>
        // Smooth entrance animation
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('error-container');
            if (container) {
                container.style.opacity = '0';
                container.style.transform = 'translateY(30px) scale(0.98)';
                
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        container.style.transition = 'all 1s cubic-bezier(0.16, 1, 0.3, 1)';
                        container.style.opacity = '1';
                        container.style.transform = 'translateY(0) scale(1)';
                    }, 50);
                });
            }
        });

        // Mouse glow effect
        const cursorGlow = document.createElement('div');
        cursorGlow.className = 'pointer-events-none fixed z-50 mix-blend-multiply opacity-0 transition-opacity duration-300 rounded-full blur-[80px] bg-[var(--teal)]/30';
        cursorGlow.style.width = '300px';
        cursorGlow.style.height = '300px';
        cursorGlow.style.transform = 'translate(-50%, -50%)';
        document.body.appendChild(cursorGlow);

        let mouseX = window.innerWidth / 2;
        let mouseY = window.innerHeight / 2;
        let glowX = mouseX;
        let glowY = mouseY;

        window.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
            if (cursorGlow.style.opacity === '0') {
                cursorGlow.style.opacity = '1';
            }
        });

        function animateGlow() {
            glowX += (mouseX - glowX) * 0.1;
            glowY += (mouseY - glowY) * 0.1;
            cursorGlow.style.left = `${glowX}px`;
            cursorGlow.style.top = `${glowY}px`;
            requestAnimationFrame(animateGlow);
        }
        animateGlow();
    </script>
</body>
</html>
