@extends('layouts.auth')

@section('title', 'Reward & Riwayat Poin — Visueco')

@section('content')
<div class="min-h-screen px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl">

        {{-- ═══ TOP BAR ═══ --}}
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">Tukar Reward</h1>
                <p class="text-sm text-slate-500">Gunakan poin Anda untuk hadiah nyata</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-white">
                    Dashboard
                </a>
                <div class="rounded-lg bg-teal-50 px-4 py-2">
                    <span class="text-xs text-slate-500">Saldo:</span>
                    <span class="ml-1 font-bold text-teal-600" id="points-display">{{ auth()->user()->points_balance }}</span>
                    <span class="text-xs text-slate-500">poin</span>
                </div>
            </div>
        </div>

        {{-- ═══ REWARD CATALOG ═══ --}}
        <section class="mb-10">
            <h2 class="mb-4 text-sm font-semibold tracking-wide text-slate-400 uppercase">Katalog Reward</h2>

            @if($rewards->isEmpty())
                <div class="rounded-xl border border-slate-100 bg-white p-10 text-center shadow-sm">
                    <p class="text-sm text-slate-400">Belum ada reward yang tersedia saat ini.</p>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($rewards as $reward)
                        <div class="flex flex-col justify-between rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">{{ $reward->title }}</h3>
                                <p class="mt-1.5 text-xs leading-relaxed text-slate-500">{{ $reward->description }}</p>
                            </div>
                            <div class="mt-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="text-lg font-bold text-teal-600">{{ number_format($reward->points_required) }} <span class="text-xs font-normal text-slate-400">poin</span></span>
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $reward->stock > 0 ? 'bg-teal-50 text-teal-700' : 'bg-slate-100 text-slate-400' }}">
                                        Stok: {{ $reward->stock }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    class="btn-redeem w-full rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40"
                                    data-id="{{ $reward->id }}"
                                    data-title="{{ $reward->title }}"
                                    data-points="{{ $reward->points_required }}"
                                    {{ $reward->stock <= 0 ? 'disabled' : '' }}
                                >
                                    {{ $reward->stock > 0 ? 'Tukarkan Poin' : 'Stok Habis' }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ═══ POINT LEDGER ═══ --}}
        <section>
            <h2 class="mb-4 text-sm font-semibold tracking-wide text-slate-400 uppercase">Riwayat Mutasi Poin</h2>

            <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
                @if($ledgers->isEmpty())
                    <div class="p-10 text-center">
                        <p class="text-sm text-slate-400">Belum ada riwayat transaksi poin.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50/50">
                                    <th class="px-5 py-3 text-xs font-semibold tracking-wide text-slate-400 uppercase">Tanggal</th>
                                    <th class="px-5 py-3 text-xs font-semibold tracking-wide text-slate-400 uppercase">Keterangan</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold tracking-wide text-slate-400 uppercase">Tipe</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold tracking-wide text-slate-400 uppercase">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($ledgers as $ledger)
                                    <tr class="transition hover:bg-slate-50/50">
                                        <td class="whitespace-nowrap px-5 py-3.5 text-xs text-slate-500">
                                            {{ $ledger->created_at->format('d M Y, H:i') }}
                                        </td>
                                        <td class="px-5 py-3.5 text-sm text-slate-700">
                                            {{ $ledger->description }}
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            @if($ledger->type === 'credit')
                                                <span class="inline-flex rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-700">Masuk</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Keluar</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3.5 text-right text-sm font-semibold {{ $ledger->type === 'credit' ? 'text-teal-600' : 'text-amber-600' }}">
                                            {{ $ledger->type === 'credit' ? '+' : '-' }}{{ number_format($ledger->amount) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

    </div>
</div>

{{-- ═══ CONFIRM MODAL ═══ --}}
<div id="modal-confirm" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-4 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
        <h3 class="text-sm font-semibold text-slate-900">Konfirmasi Penukaran</h3>
        <p class="mt-2 text-sm text-slate-500">
            Anda akan menukarkan <span class="font-semibold text-teal-600" id="confirm-points"></span> poin untuk:
        </p>
        <p class="mt-1 font-semibold text-slate-800" id="confirm-title"></p>
        <div class="mt-5 flex gap-3">
            <button id="confirm-cancel" type="button" class="flex-1 rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                Batal
            </button>
            <button id="confirm-proceed" type="button" class="flex-1 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-700">
                Ya, Tukarkan
            </button>
        </div>
    </div>
</div>

{{-- ═══ SUCCESS MODAL ═══ --}}
<div id="modal-success" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-4 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-xl bg-white p-6 text-center shadow-xl">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-teal-100">
            <svg class="h-6 w-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <h3 class="mt-3 text-sm font-semibold text-slate-900">Penukaran Berhasil!</h3>
        <p class="mt-1 text-xs text-slate-500">Tunjukkan kode ini kepada pengurus RT</p>
        <div class="mt-4 rounded-lg bg-slate-50 px-4 py-3">
            <p class="font-mono text-xl font-bold tracking-widest text-slate-900" id="success-code"></p>
        </div>
        <p class="mt-3 text-xs text-slate-500">Reward: <span class="font-medium text-slate-700" id="success-title"></span></p>
        <button id="success-close" type="button" class="mt-5 w-full rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
            Tutup
        </button>
    </div>
</div>

{{-- ═══ ERROR ALERT (fixed top) ═══ --}}
<div id="alert-error" class="fixed left-1/2 top-4 z-50 hidden w-full max-w-md -translate-x-1/2 rounded-lg bg-red-50 px-5 py-3 text-sm text-red-800 shadow-lg">
    <span id="alert-error-text"></span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const pointsDisplay   = document.getElementById('points-display');
    const modalConfirm    = document.getElementById('modal-confirm');
    const modalSuccess    = document.getElementById('modal-success');
    const alertError      = document.getElementById('alert-error');
    const alertErrorText  = document.getElementById('alert-error-text');

    let pendingRewardId = null;

    // ─── Open confirm modal ────────────────────────────
    document.querySelectorAll('.btn-redeem').forEach(function (btn) {
        btn.addEventListener('click', function () {
            pendingRewardId = this.dataset.id;
            document.getElementById('confirm-title').textContent = this.dataset.title;
            document.getElementById('confirm-points').textContent = Number(this.dataset.points).toLocaleString('id-ID');
            modalConfirm.classList.remove('hidden');
            modalConfirm.classList.add('flex');
        });
    });

    // ─── Cancel confirm ────────────────────────────────
    document.getElementById('confirm-cancel').addEventListener('click', closeConfirm);
    modalConfirm.addEventListener('click', function (e) {
        if (e.target === modalConfirm) closeConfirm();
    });

    function closeConfirm() {
        modalConfirm.classList.add('hidden');
        modalConfirm.classList.remove('flex');
        pendingRewardId = null;
    }

    // ─── Proceed redeem via fetch ──────────────────────
    document.getElementById('confirm-proceed').addEventListener('click', async function () {
        if (!pendingRewardId) return;

        const btnProceed = this;
        btnProceed.disabled = true;
        btnProceed.textContent = 'Memproses...';

        try {
            const res = await fetch('/api/v1/redeem', {
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ reward_id: Number(pendingRewardId) }),
            });

            const json = await res.json();

            closeConfirm();

            if (res.status === 201 && json.success) {
                showSuccess(json.data);
            } else {
                showError(json.message || 'Penukaran gagal.');
            }
        } catch (err) {
            closeConfirm();
            showError('Tidak dapat terhubung ke server. Periksa koneksi Anda.');
        } finally {
            btnProceed.disabled = false;
            btnProceed.textContent = 'Ya, Tukarkan';
        }
    });

    // ─── Success modal ─────────────────────────────────
    function showSuccess(data) {
        document.getElementById('success-code').textContent = data.redemption_code;
        document.getElementById('success-title').textContent = data.reward_title;
        pointsDisplay.textContent = data.points_balance;
        modalSuccess.classList.remove('hidden');
        modalSuccess.classList.add('flex');
    }

    document.getElementById('success-close').addEventListener('click', function () {
        modalSuccess.classList.add('hidden');
        modalSuccess.classList.remove('flex');
        location.reload();
    });

    // ─── Error alert ───────────────────────────────────
    function showError(message) {
        alertErrorText.textContent = message;
        alertError.classList.remove('hidden');
        setTimeout(function () {
            alertError.classList.add('hidden');
        }, 5000);
    }

    // ─── Helpers ───────────────────────────────────────
    function getCookie(name) {
        const value = '; ' + document.cookie;
        const parts = value.split('; ' + name + '=');
        if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
        return '';
    }
});
</script>
@endsection
