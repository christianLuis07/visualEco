@extends('layouts.auth')

@section('title', 'Reward & Riwayat Poin — Visueco')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/rewards.css') }}">
@endpush

@section('content')
<div class="min-h-screen px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl">

        {{-- ═══ TOP BAR ═══ --}}
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-[#0F172A]">Tukar Reward</h1>
                <p class="text-sm text-slate-500">Gunakan poin Anda untuk hadiah nyata</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-white">
                    Dashboard
                </a>
                <div class="rounded-xl bg-teal-50 px-4 py-2">
                    <span class="text-xs text-slate-500">Saldo:</span>
                    <span class="ml-1 font-bold text-[#0D9488]" id="points-display">{{ auth()->user()->points_balance }}</span>
                    <span class="text-xs text-slate-500">poin</span>
                </div>
            </div>
        </div>

        {{-- ═══ REWARD CATALOG ═══ --}}
        <section class="mb-10">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Katalog Reward</h2>

            @if($rewards->isEmpty())
                <x-clean-card padding="p-10" class="text-center">
                    <p class="text-sm text-slate-400">Belum ada reward yang tersedia saat ini.</p>
                </x-clean-card>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($rewards as $reward)
                        <x-clean-card class="flex flex-col justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-[#0F172A]">{{ $reward->title }}</h3>
                                <p class="mt-1.5 text-xs leading-relaxed text-slate-500">{{ $reward->description }}</p>
                            </div>
                            <div class="mt-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="text-lg font-bold text-[#0D9488]">{{ number_format($reward->points_required) }} <span class="text-xs font-normal text-slate-400">poin</span></span>
                                    <x-status-badge :tone="$reward->stock > 0 ? 'credit' : 'neutral'">Stok: {{ $reward->stock }}</x-status-badge>
                                </div>
                                <x-action-button
                                    variant="primary"
                                    class="btn-redeem w-full"
                                    data-id="{{ $reward->id }}"
                                    data-title="{{ $reward->title }}"
                                    data-points="{{ $reward->points_required }}"
                                    :disabled="$reward->stock <= 0"
                                >
                                    {{ $reward->stock > 0 ? 'Tukarkan Poin' : 'Stok Habis' }}
                                </x-action-button>
                            </div>
                        </x-clean-card>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ═══ POINT LEDGER ═══ --}}
        <section>
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Riwayat Mutasi Poin</h2>

            <x-clean-card padding="p-0" class="overflow-hidden">
                @if($ledgers->isEmpty())
                    <div class="p-10 text-center">
                        <p class="text-sm text-slate-400">Belum ada riwayat transaksi poin.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50/50">
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal</th>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Keterangan</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-400">Tipe</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-400">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($ledgers as $ledger)
                                    <tr class="transition hover:bg-slate-50/50">
                                        <td class="whitespace-nowrap px-5 py-3.5 text-xs text-slate-500">{{ $ledger->created_at->format('d M Y, H:i') }}</td>
                                        <td class="px-5 py-3.5 text-sm text-slate-700">{{ $ledger->description }}</td>
                                        <td class="px-5 py-3.5 text-center">
                                            <x-status-badge :tone="$ledger->type">{{ $ledger->type === 'credit' ? 'Masuk' : 'Keluar' }}</x-status-badge>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3.5 text-right text-sm font-semibold {{ $ledger->type === 'credit' ? 'text-[#0D9488]' : 'text-rose-600' }}">
                                            {{ $ledger->type === 'credit' ? '+' : '-' }}{{ number_format($ledger->amount) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-clean-card>
        </section>

    </div>
</div>

{{-- ═══ CONFIRM MODAL ═══ --}}
<div id="modal-confirm" class="modal-overlay fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-4 backdrop-blur-sm">
    <div class="modal-panel w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
        <h3 class="text-sm font-semibold text-[#0F172A]">Konfirmasi Penukaran</h3>
        <p class="mt-2 text-sm text-slate-500">
            Anda akan menukarkan <span class="font-semibold text-[#0D9488]" id="confirm-points"></span> poin untuk:
        </p>
        <p class="mt-1 font-semibold text-slate-800" id="confirm-title"></p>
        <div class="mt-5 flex gap-3">
            <x-action-button id="confirm-cancel" variant="ghost" class="flex-1">Batal</x-action-button>
            <x-action-button id="confirm-proceed" variant="primary" class="flex-1">Ya, Tukarkan</x-action-button>
        </div>
    </div>
</div>

{{-- ═══ SUCCESS MODAL ═══ --}}
<div id="modal-success" class="modal-overlay fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-4 backdrop-blur-sm">
    <div class="modal-panel w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-teal-100">
            <svg class="h-6 w-6 text-[#0D9488]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <h3 class="mt-3 text-sm font-semibold text-[#0F172A]">Penukaran Berhasil!</h3>
        <p class="mt-1 text-xs text-slate-500">Tunjukkan kode ini kepada pengurus RT</p>
        <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3">
            <p class="font-mono text-xl font-bold tracking-widest text-slate-900" id="success-code"></p>
        </div>
        <p class="mt-3 text-xs text-slate-500">Reward: <span class="font-medium text-slate-700" id="success-title"></span></p>
        <x-action-button id="success-close" variant="ghost" class="mt-5 w-full">Tutup</x-action-button>
    </div>
</div>

{{-- ═══ ERROR ALERT (floating) ═══ --}}
<x-feedback-alert id="alert-error" floating class="border-rose-100 bg-rose-50/90 text-rose-700" />
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/rewards.js') }}" defer></script>
@endpush
