@extends('layouts.auth')

@section('title', 'Daftar — Visueco')

@section('content')
<div class="flex min-h-screen items-center justify-center px-4 py-12 relative z-20">
    <div class="w-full max-w-md">

        {{-- Header --}}
        <div class="mb-8 text-center gs-reveal">
            <h1 class="font-display text-4xl font-bold tracking-tight text-[var(--ink)]">
                Mulai <span class="font-serif italic text-[var(--teal-deep)]">memindai.</span>
            </h1>
            <p class="mt-3 text-base text-[var(--ink-soft)]">Buat akun untuk mencatat poin sampahmu</p>
        </div>

        {{-- Glass Card --}}
        <div class="glass-card p-8 gs-reveal">
            <form method="POST" action="{{ route('register') }}">
                @csrf

                {{-- Nama --}}
                <div class="mb-5 gs-reveal">
                    <label for="name" class="mb-2 block text-sm font-semibold text-[var(--ink)]">Nama Lengkap</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="name"
                        class="input-glass w-full rounded-xl px-4 py-3.5 text-sm text-[var(--ink)] placeholder-[var(--ink-light)]"
                        placeholder="Cth: Budi Santoso"
                    >
                    @error('name')
                        <p class="mt-2 text-xs font-semibold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="mb-5 gs-reveal">
                    <label for="email" class="mb-2 block text-sm font-semibold text-[var(--ink)]">Alamat Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        class="input-glass w-full rounded-xl px-4 py-3.5 text-sm text-[var(--ink)] placeholder-[var(--ink-light)]"
                        placeholder="nama@email.com"
                    >
                    @error('email')
                        <p class="mt-2 text-xs font-semibold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-5 gs-reveal">
                    <label for="password" class="mb-2 block text-sm font-semibold text-[var(--ink)]">Kata Sandi</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="input-glass w-full rounded-xl px-4 py-3.5 text-sm text-[var(--ink)] placeholder-[var(--ink-light)]"
                        placeholder="Minimal 8 karakter"
                    >
                    @error('password')
                        <p class="mt-2 text-xs font-semibold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Konfirmasi Password --}}
                <div class="mb-8 gs-reveal">
                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-[var(--ink)]">Konfirmasi Sandi</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="input-glass w-full rounded-xl px-4 py-3.5 text-sm text-[var(--ink)] placeholder-[var(--ink-light)]"
                        placeholder="Ulangi kata sandi"
                    >
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="btn-primary gs-reveal w-full rounded-full px-4 py-3.5 text-base font-semibold transition focus:outline-none focus:ring-2 focus:ring-[var(--teal)]/50 focus:ring-offset-2 flex justify-center items-center gap-2"
                >
                    Registrasi Akun
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </button>
            </form>
        </div>

        {{-- Footer Link --}}
        <p class="mt-8 text-center text-sm text-[var(--ink-soft)] gs-reveal">
            Sudah terdaftar?
            <a href="{{ route('login') }}" class="font-bold text-[var(--teal-deep)] hover:text-[var(--teal)] transition-colors">Akses portal</a>
        </p>

    </div>
</div>
@endsection
