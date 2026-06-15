@extends('layouts.auth')

@section('title', 'Dashboard — Visueco')

@section('content')
<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-lg text-center">
        <div class="rounded-xl border border-slate-100 bg-white p-10 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">Selamat Datang, {{ auth()->user()->name }}!</h1>
            <p class="mt-2 text-sm text-slate-500">Role: {{ auth()->user()->role }} &middot; Saldo: {{ auth()->user()->points_balance }} poin</p>

            <form method="POST" action="{{ route('logout') }}" class="mt-8">
                @csrf
                <button
                    type="submit"
                    class="rounded-lg border border-slate-200 px-6 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Keluar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
