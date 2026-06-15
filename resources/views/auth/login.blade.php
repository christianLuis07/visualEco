@extends('layouts.auth')

@section('title', 'Masuk — Visueco')

@section('content')
<div class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        {{-- Header --}}
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Visueco</h1>
            <p class="mt-2 text-sm text-slate-500">Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        {{-- Card --}}
        <div class="rounded-xl border border-slate-100 bg-white p-8 shadow-sm">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div class="mb-5">
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        autofocus
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
                        autocomplete="current-password"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                        placeholder="Masukkan password"
                    >
                </div>

                {{-- Remember --}}
                <div class="mb-6 flex items-center">
                    <input
                        id="remember"
                        name="remember"
                        type="checkbox"
                        {{ old('remember') ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                    >
                    <label for="remember" class="ml-2 text-sm text-slate-600">Ingat saya</label>
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:ring-offset-2"
                >
                    Masuk
                </button>
            </form>
        </div>

        {{-- Footer Link --}}
        <p class="mt-6 text-center text-sm text-slate-500">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-medium text-teal-600 hover:text-teal-700">Daftar sekarang</a>
        </p>

    </div>
</div>
@endsection
