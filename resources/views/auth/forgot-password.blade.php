@extends('layouts.auth')

@section('title', 'Lupa Password — Visueco')

@section('content')
<div class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold tracking-tight text-[#0F172A]">Lupa Password</h1>
            <p class="mt-2 text-sm text-slate-500">Masukkan email Anda untuk menerima tautan reset</p>
        </div>

        <x-clean-card padding="p-8">
            @if (session('status'))
                <div class="mb-5 rounded-lg bg-teal-50 px-4 py-3 text-sm text-[#0D9488]">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

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
                        class="w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-[#0D9488] focus:outline-none focus:ring-2 focus:ring-[#0D9488]/20"
                        placeholder="nama@email.com"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <x-action-button type="submit" variant="primary" class="w-full">
                    Kirim Tautan Reset
                </x-action-button>
            </form>
        </x-clean-card>

        <p class="mt-6 text-center text-sm text-slate-500">
            Ingat password Anda?
            <a href="{{ route('login') }}" class="font-medium text-[#0D9488] hover:text-[#0f766e]">Kembali masuk</a>
        </p>

    </div>
</div>
@endsection
