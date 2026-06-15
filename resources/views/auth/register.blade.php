@extends('layouts.auth')

@section('title', 'Daftar — Visueco')

@section('content')
<div class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        {{-- Header --}}
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Visueco</h1>
            <p class="mt-2 text-sm text-slate-500">Buat akun baru untuk mulai berkontribusi</p>
        </div>

        {{-- Card --}}
        <div class="rounded-xl border border-slate-100 bg-white p-8 shadow-sm">
            <form method="POST" action="{{ route('register') }}">
                @csrf

                {{-- Nama --}}
                <div class="mb-5">
                    <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama Lengkap</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="name"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                        placeholder="Nama lengkap Anda"
                    >
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="mb-5">
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                        placeholder="nama@email.com"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-5">
                    <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                        placeholder="Minimal 8 karakter"
                    >
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Konfirmasi Password --}}
                <div class="mb-6">
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Konfirmasi Password</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                        placeholder="Ulangi password"
                    >
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:ring-offset-2"
                >
                    Daftar
                </button>
            </form>
        </div>

        {{-- Footer Link --}}
        <p class="mt-6 text-center text-sm text-slate-500">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-medium text-teal-600 hover:text-teal-700">Masuk di sini</a>
        </p>

    </div>
</div>
@endsection
