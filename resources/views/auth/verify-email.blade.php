@extends('layouts.auth')

@section('title', 'Verifikasi Email — Visueco')

@section('content')
<div class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold tracking-tight text-[#0F172A]">Verifikasi Email</h1>
            <p class="mt-2 text-sm text-slate-500">Terima kasih telah mendaftar! Sebelum memulai, harap verifikasi alamat email Anda dengan mengklik tautan yang baru saja kami kirimkan ke email Anda.</p>
        </div>

        <x-clean-card padding="p-8">
            @if (session('status') == 'verification-link-sent')
                <div class="mb-5 rounded-lg bg-teal-50 px-4 py-3 text-sm text-[#0D9488]">
                    Tautan verifikasi baru telah dikirim ke alamat email yang Anda berikan saat registrasi.
                </div>
            @elseif (session('status'))
                <div class="mb-5 rounded-lg bg-teal-50 px-4 py-3 text-sm text-[#0D9488]">
                    {{ session('status') }}
                </div>
            @endif

            <p class="mb-4 text-sm text-slate-600">
                Jika Anda tidak menerima email tersebut, kami dengan senang hati akan mengirimkan ulang tautan verifikasi.
            </p>

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-action-button type="submit" variant="primary" class="w-full">
                    Kirim Ulang Email Verifikasi
                </x-action-button>
            </form>
            
            <div class="mt-4 text-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-slate-500 hover:text-slate-700 underline">
                        Keluar
                    </button>
                </form>
            </div>
        </x-clean-card>

    </div>
</div>
@endsection
