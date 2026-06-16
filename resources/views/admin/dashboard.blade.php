@extends('layouts.auth')

@section('title', 'Admin Panel — Visueco')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/admin.css') }}">
@endpush

@section('content')
@php
    $activeVersion  = $modelStats['active_version'] ?? null;
    $pendingSamples = $modelStats['pending_samples'] ?? 0;
    $totalSamples   = $modelStats['total_samples'] ?? 0;
    $correctPred    = $modelStats['correct_predictions'] ?? 0;
    $accuracyPct    = $totalSamples > 0 ? round($correctPred / $totalSamples * 100) : null;
@endphp
<div class="min-h-screen px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl">

        {{-- ═══ TOP BAR ═══ --}}
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-[#0F172A]">Panel Administrasi</h1>
                <p class="text-sm text-slate-500">Verifikasi voucher dan pantau aktivitas warga</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-white">
                    Keluar
                </button>
            </form>
        </div>

        {{-- ═══ STATS CARD ═══ --}}
        <div class="mb-8 grid gap-4 sm:grid-cols-2">
            <x-clean-card>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total Sampah Terselamatkan</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-[#0D9488]">{{ number_format($totalScans) }}</p>
                <p class="mt-1 text-sm text-slate-500">scan berhasil diproses</p>
            </x-clean-card>
            <x-clean-card>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Voucher Menunggu Verifikasi</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-amber-600">{{ $pendingRedeems->count() }}</p>
                <p class="mt-1 text-sm text-slate-500">voucher pending</p>
            </x-clean-card>
        </div>

        {{-- ═══ KARTU STATUS MODEL AI ═══ --}}
        <x-clean-card class="mb-8">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-[#0F172A]">Status Model AI</h2>
                    <p class="text-xs text-slate-400">Model belajar dari konfirmasi warga</p>
                </div>
                <x-action-button id="btn-train" variant="primary" class="px-4 py-2 text-xs">Latih Ulang Model</x-action-button>
            </div>

            <x-feedback-alert id="train-alert" class="mb-4" />

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-400">Versi Model</p>
                    <p class="mt-0.5 text-lg font-bold text-slate-800" id="stat-version">{{ $activeVersion ? 'v' . $activeVersion->version : 'Belum dilatih' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-400">Akurasi Latih</p>
                    <p class="mt-0.5 text-lg font-bold text-slate-800" id="stat-accuracy">{{ $activeVersion && $activeVersion->accuracy !== null ? round($activeVersion->accuracy * 100) . '%' : '—' }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-400">Total Data Latih</p>
                    <p class="mt-0.5 text-lg font-bold text-slate-800">{{ number_format($totalSamples) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-400">Belum Dilatih</p>
                    <p class="mt-0.5 text-lg font-bold text-[#0D9488]" id="stat-pending">{{ number_format($pendingSamples) }}</p>
                </div>
            </div>

            @if($accuracyPct !== null)
                <p class="mt-3 text-xs text-slate-500">
                    Akurasi prediksi awal model terhadap konfirmasi warga:
                    <span class="font-semibold text-slate-700">{{ $accuracyPct }}%</span>
                    ({{ $correctPred }}/{{ $totalSamples }} tebakan benar)
                </p>
            @endif
        </x-clean-card>

        {{-- ═══ MAIN LAYOUT: ASIMETRIS 2:3 ═══ --}}
        <div class="grid gap-6 lg:grid-cols-5">

            {{-- KIRI (2/5): Form Verifikasi --}}
            <div class="lg:col-span-2">
                <x-clean-card>
                    <h2 class="mb-4 text-sm font-semibold text-[#0F172A]">Verifikasi Kode Voucher</h2>
                    <p class="mb-4 text-xs text-slate-400">Masukkan kode yang ditunjukkan warga</p>

                    <x-feedback-alert id="alert-banner" class="mb-4" />

                    <div class="flex gap-2">
                        <input
                            type="text"
                            id="voucher-input"
                            placeholder="VSEC-XXXXXXXX"
                            class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 font-mono text-sm uppercase tracking-wider text-slate-900 placeholder-slate-300 transition focus:border-[#0D9488] focus:outline-none focus:ring-2 focus:ring-[#0D9488]/20"
                            maxlength="13"
                            autocomplete="off"
                        >
                        <x-action-button id="btn-verify" variant="primary">Periksa</x-action-button>
                    </div>
                </x-clean-card>
            </div>

            {{-- KANAN (3/5): Detail Voucher --}}
            <div class="lg:col-span-3">
                {{-- Empty State --}}
                <div id="detail-empty" class="flex h-full items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-10">
                    <div class="text-center">
                        <svg class="mx-auto h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <p class="mt-3 text-sm text-slate-400">Masukkan kode voucher untuk melihat detail</p>
                    </div>
                </div>

                {{-- Detail Card --}}
                <x-clean-card id="detail-card" class="hidden">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-[#0F172A]">Detail Voucher</h2>
                        <x-status-badge tone="pending" id="detail-status-badge"></x-status-badge>
                    </div>

                    <div class="mb-5 rounded-xl bg-slate-50 px-4 py-3 text-center">
                        <p class="font-mono text-xl font-bold tracking-widest text-slate-900" id="detail-code"></p>
                    </div>

                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500">Nama Warga</dt>
                            <dd class="text-sm font-medium text-slate-800" id="detail-user"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500">Hadiah</dt>
                            <dd class="text-sm font-medium text-slate-800" id="detail-reward"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500">Poin Digunakan</dt>
                            <dd class="text-sm font-medium text-[#0D9488]" id="detail-points"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500">Tanggal Tukar</dt>
                            <dd class="text-sm text-slate-600" id="detail-date"></dd>
                        </div>
                    </dl>

                    <div id="action-area" class="mt-6 border-t border-slate-100 pt-5">
                        <x-action-button id="btn-complete" variant="primary" class="w-full">Konfirmasi Penyerahan Hadiah</x-action-button>
                    </div>

                    <div id="completed-badge" class="mt-6 hidden border-t border-slate-100 pt-5">
                        <div class="flex items-center justify-center gap-2 rounded-xl bg-emerald-50 px-4 py-3">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span class="text-sm font-semibold text-emerald-800">Hadiah sudah diserahkan</span>
                        </div>
                    </div>
                </x-clean-card>
            </div>

        </div>

        {{-- ═══ PENDING VOUCHERS TABLE ═══ --}}
        <section class="mt-8">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Daftar Voucher Pending</h2>

            <x-clean-card padding="p-0" class="overflow-hidden">
                @if($pendingRedeems->isEmpty())
                    <div class="p-10 text-center">
                        <p class="text-sm text-slate-400">Tidak ada voucher yang menunggu verifikasi.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50/50">
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Kode</th>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Warga</th>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Hadiah</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-400">Status</th>
                                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($pendingRedeems as $redeem)
                                    <tr class="voucher-row cursor-pointer transition hover:bg-slate-50/50" data-code="{{ $redeem->redemption_code }}">
                                        <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs font-semibold tracking-wider text-slate-800">{{ $redeem->redemption_code }}</td>
                                        <td class="px-5 py-3.5 text-sm text-slate-700">{{ $redeem->user->name }}</td>
                                        <td class="px-5 py-3.5 text-sm text-slate-700">{{ $redeem->reward->title }}</td>
                                        <td class="px-5 py-3.5 text-center">
                                            <x-status-badge tone="pending">Pending</x-status-badge>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3.5 text-xs text-slate-500">{{ $redeem->created_at->format('d M Y, H:i') }}</td>
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
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/admin.js') }}" defer></script>
@endpush
