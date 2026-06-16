@extends('layouts.auth')

@section('title', 'Reset Password — Visueco')

@section('content')
<div class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold tracking-tight text-[#0F172A]">Atur Password Baru</h1>
            <p class="mt-2 text-sm text-slate-500">Masukkan password baru untuk akun Anda</p>
        </div>

        <x-clean-card padding="p-8">
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-5">
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email', $email) }}"
                        autocomplete="email"
                        required
                        readonly
                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-600"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password Baru</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-[#0D9488] focus:outline-none focus:ring-2 focus:ring-[#0D9488]/20"
                        placeholder="Minimal 8 karakter"
                    >
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Konfirmasi Password</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-[#0D9488] focus:outline-none focus:ring-2 focus:ring-[#0D9488]/20"
                        placeholder="Ulangi password baru"
                    >
                </div>

                <x-action-button type="submit" variant="primary" class="w-full">
                    Simpan Password Baru
                </x-action-button>
            </form>
        </x-clean-card>

    </div>
</div>
@endsection
