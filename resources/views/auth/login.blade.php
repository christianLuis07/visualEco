@extends('layouts.auth')

@section('title', 'Masuk — Visueco')

@section('content')
<div class="flex min-h-screen items-center justify-center px-4 py-12 relative z-20">
    <div class="w-full max-w-md">

        {{-- Header --}}
        <div class="mb-8 text-center gs-reveal">
            <h1 class="font-display text-4xl font-bold tracking-tight text-[var(--ink)]">
                Selamat <span class="font-serif italic text-[var(--teal-deep)]">datang.</span>
            </h1>
            <p class="mt-3 text-base text-[var(--ink-soft)]">Masuk ke portal Anda untuk melihat poin</p>
        </div>

        {{-- Glass Card --}}
        <div class="glass-card p-8 gs-reveal">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div class="mb-5 gs-reveal">
                    <label for="email" class="mb-2 block text-sm font-semibold text-[var(--ink)]">Alamat Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        autofocus
                        class="input-glass w-full rounded-xl px-4 py-3.5 text-sm text-[var(--ink)] placeholder-[var(--ink-light)]"
                        placeholder="nama@email.com"
                    >
                    @error('email')
                        <p class="mt-2 text-xs font-semibold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-6 gs-reveal">
                    <div class="mb-2 flex items-center justify-between">
                        <label for="password" class="block text-sm font-semibold text-[var(--ink)]">Kata Sandi</label>
                        <a href="{{ route('password.request') }}" class="text-xs font-bold text-[var(--teal-deep)] hover:text-[var(--teal-bright)] transition-colors">Lupa sandi?</a>
                    </div>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="input-glass w-full rounded-xl px-4 py-3.5 text-sm text-[var(--ink)] placeholder-[var(--ink-light)]"
                        placeholder="••••••••"
                    >
                </div>

                {{-- Remember --}}
                <div class="mb-8 flex items-center gs-reveal">
                    <input
                        id="remember"
                        name="remember"
                        type="checkbox"
                        {{ old('remember') ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-[var(--teal)]/30 text-[var(--teal-deep)] focus:ring-[var(--teal)]"
                    >
                    <label for="remember" class="ml-3 text-sm font-medium text-[var(--ink-soft)]">Ingat Sesi Ini</label>
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="btn-primary gs-reveal w-full rounded-full px-4 py-3.5 text-base font-semibold transition focus:outline-none focus:ring-2 focus:ring-[var(--teal)]/50 focus:ring-offset-2 flex justify-center items-center gap-2"
                >
                    Akses Portal
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </button>
            </form>
        </div>

        {{-- Footer Link --}}
        <p class="mt-8 text-center text-sm text-[var(--ink-soft)] gs-reveal">
            Warga baru?
            <a href="{{ route('register') }}" class="font-bold text-[var(--teal-deep)] hover:text-[var(--teal)] transition-colors">Daftar sekarang</a>
        </p>

    </div>
</div>
@endsection
