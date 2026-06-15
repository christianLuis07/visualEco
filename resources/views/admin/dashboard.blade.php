@extends('layouts.auth')

@section('title', 'Admin Panel — Visueco')

@section('content')
<div class="min-h-screen px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl">

        {{-- ═══ TOP BAR ═══ --}}
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">Panel Administrasi</h1>
                <p class="text-sm text-slate-500">Verifikasi voucher dan pantau aktivitas warga</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-white">
                    Keluar
                </button>
            </form>
        </div>

        {{-- ═══ STATS CARD ═══ --}}
        <div class="mb-8 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
                <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">Total Sampah Terselamatkan</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-teal-600">{{ number_format($totalScans) }}</p>
                <p class="mt-1 text-sm text-slate-500">scan berhasil diproses</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
                <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">Voucher Menunggu Verifikasi</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-amber-600">{{ $pendingRedeems->count() }}</p>
                <p class="mt-1 text-sm text-slate-500">voucher pending</p>
            </div>
        </div>

        {{-- ═══ KARTU STATUS MODEL AI ═══ --}}
        @php
            $mlInfo = $modelStats['ml_info'] ?? null;
            $activeVersion = $modelStats['active_version'] ?? null;
            $pendingSamples = $modelStats['pending_samples'] ?? 0;
            $totalSamples = $modelStats['total_samples'] ?? 0;
            $correctPred = $modelStats['correct_predictions'] ?? 0;
            $accuracyPct = $totalSamples > 0 ? round($correctPred / $totalSamples * 100) : null;
        @endphp
        <div class="mb-8 rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Status Model AI</h2>
                    <p class="text-xs text-slate-400">Model belajar dari konfirmasi warga</p>
                </div>
                <button id="btn-train" type="button"
                    class="rounded-lg bg-teal-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:ring-offset-2 disabled:opacity-40">
                    Latih Ulang Model
                </button>
            </div>

            <div id="train-alert" class="mb-4 hidden rounded-lg px-4 py-3 text-sm"></div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-lg bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-400">Versi Model</p>
                    <p class="mt-0.5 text-lg font-bold text-slate-800" id="stat-version">
                        {{ $activeVersion ? 'v' . $activeVersion->version : 'Belum dilatih' }}
                    </p>
                </div>
                <div class="rounded-lg bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-400">Akurasi Latih</p>
                    <p class="mt-0.5 text-lg font-bold text-slate-800" id="stat-accuracy">
                        {{ $activeVersion && $activeVersion->accuracy !== null ? round($activeVersion->accuracy * 100) . '%' : '—' }}
                    </p>
                </div>
                <div class="rounded-lg bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-400">Total Data Latih</p>
                    <p class="mt-0.5 text-lg font-bold text-slate-800">{{ number_format($totalSamples) }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-400">Belum Dilatih</p>
                    <p class="mt-0.5 text-lg font-bold text-teal-600" id="stat-pending">{{ number_format($pendingSamples) }}</p>
                </div>
            </div>

            @if($accuracyPct !== null)
                <p class="mt-3 text-xs text-slate-500">
                    Akurasi prediksi awal model terhadap konfirmasi warga:
                    <span class="font-semibold text-slate-700">{{ $accuracyPct }}%</span>
                    ({{ $correctPred }}/{{ $totalSamples }} tebakan benar)
                </p>
            @endif
        </div>

        {{-- ═══ MAIN LAYOUT: 2 COLUMN ═══ --}}
        <div class="grid gap-6 lg:grid-cols-5">

            {{-- LEFT: Voucher Verification Form --}}
            <div class="lg:col-span-2">
                <div class="rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-slate-900">Verifikasi Kode Voucher</h2>
                    <p class="mb-4 text-xs text-slate-400">Masukkan kode yang ditunjukkan warga</p>

                    {{-- Alert --}}
                    <div id="alert-banner" class="mb-4 hidden rounded-lg px-4 py-3 text-sm"></div>

                    <div class="flex gap-2">
                        <input
                            type="text"
                            id="voucher-input"
                            placeholder="VSEC-XXXXXXXX"
                            class="flex-1 rounded-lg border border-slate-200 px-4 py-2.5 font-mono text-sm tracking-wider text-slate-900 placeholder-slate-300 transition focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20 uppercase"
                            maxlength="13"
                            autocomplete="off"
                        >
                        <button
                            id="btn-verify"
                            type="button"
                            class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:ring-offset-2"
                        >
                            Periksa
                        </button>
                    </div>
                </div>
            </div>

            {{-- RIGHT: Voucher Detail Card --}}
            <div class="lg:col-span-3">
                {{-- Empty State --}}
                <div id="detail-empty" class="flex h-full items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-10">
                    <div class="text-center">
                        <svg class="mx-auto h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <p class="mt-3 text-sm text-slate-400">Masukkan kode voucher untuk melihat detail</p>
                    </div>
                </div>

                {{-- Detail Card (hidden) --}}
                <div id="detail-card" class="hidden rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900">Detail Voucher</h2>
                        <span id="detail-status-badge" class="rounded-full px-3 py-1 text-xs font-semibold"></span>
                    </div>

                    <div class="mb-5 rounded-lg bg-slate-50 px-4 py-3 text-center">
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
                            <dd class="text-sm font-medium text-teal-600" id="detail-points"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500">Tanggal Tukar</dt>
                            <dd class="text-sm text-slate-600" id="detail-date"></dd>
                        </div>
                    </dl>

                    {{-- Action Button --}}
                    <div id="action-area" class="mt-6 border-t border-slate-100 pt-5">
                        <button
                            id="btn-complete"
                            type="button"
                            class="w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:ring-offset-2"
                        >
                            Konfirmasi Penyerahan Hadiah
                        </button>
                    </div>

                    {{-- Completed State --}}
                    <div id="completed-badge" class="mt-6 hidden border-t border-slate-100 pt-5">
                        <div class="flex items-center justify-center gap-2 rounded-lg bg-green-50 px-4 py-3">
                            <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span class="text-sm font-semibold text-green-800">Hadiah sudah diserahkan</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ═══ PENDING VOUCHERS TABLE ═══ --}}
        <section class="mt-8">
            <h2 class="mb-4 text-sm font-semibold tracking-wide text-slate-400 uppercase">Daftar Voucher Pending</h2>

            <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
                @if($pendingRedeems->isEmpty())
                    <div class="p-10 text-center">
                        <p class="text-sm text-slate-400">Tidak ada voucher yang menunggu verifikasi.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50/50">
                                    <th class="px-5 py-3 text-xs font-semibold tracking-wide text-slate-400 uppercase">Kode</th>
                                    <th class="px-5 py-3 text-xs font-semibold tracking-wide text-slate-400 uppercase">Warga</th>
                                    <th class="px-5 py-3 text-xs font-semibold tracking-wide text-slate-400 uppercase">Hadiah</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold tracking-wide text-slate-400 uppercase">Status</th>
                                    <th class="px-5 py-3 text-xs font-semibold tracking-wide text-slate-400 uppercase">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($pendingRedeems as $redeem)
                                    <tr class="cursor-pointer transition hover:bg-slate-50/50" data-code="{{ $redeem->redemption_code }}">
                                        <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs font-semibold tracking-wider text-slate-800">{{ $redeem->redemption_code }}</td>
                                        <td class="px-5 py-3.5 text-sm text-slate-700">{{ $redeem->user->name }}</td>
                                        <td class="px-5 py-3.5 text-sm text-slate-700">{{ $redeem->reward->title }}</td>
                                        <td class="px-5 py-3.5 text-center">
                                            <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Pending</span>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3.5 text-xs text-slate-500">{{ $redeem->created_at->format('d M Y, H:i') }}</td>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const voucherInput  = document.getElementById('voucher-input');
    const btnVerify     = document.getElementById('btn-verify');
    const alertBanner   = document.getElementById('alert-banner');
    const detailEmpty   = document.getElementById('detail-empty');
    const detailCard    = document.getElementById('detail-card');
    const actionArea    = document.getElementById('action-area');
    const completedBadge = document.getElementById('completed-badge');
    const btnComplete   = document.getElementById('btn-complete');

    let currentRedeemId = null;

    // ─── Verify voucher ────────────────────────────────
    btnVerify.addEventListener('click', verifyVoucher);
    voucherInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') verifyVoucher();
    });

    async function verifyVoucher() {
        const code = voucherInput.value.trim();
        if (!code) return;

        btnVerify.disabled = true;
        btnVerify.textContent = 'Memeriksa...';
        hideAlert();

        try {
            const res = await fetch('/admin/voucher/verify', {
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ redemption_code: code }),
            });

            const json = await res.json();

            if (res.ok && json.success) {
                showDetail(json.data);
            } else {
                hideDetail();
                showAlert(json.message || 'Kode voucher tidak valid.', 'error');
            }
        } catch (err) {
            hideDetail();
            showAlert('Tidak dapat terhubung ke server.', 'error');
        } finally {
            btnVerify.disabled = false;
            btnVerify.textContent = 'Periksa';
        }
    }

    // ─── Show voucher detail ───────────────────────────
    function showDetail(data) {
        currentRedeemId = data.id;

        document.getElementById('detail-code').textContent = data.redemption_code;
        document.getElementById('detail-user').textContent = data.user_name;
        document.getElementById('detail-reward').textContent = data.reward_title;
        document.getElementById('detail-points').textContent = Number(data.points_spent).toLocaleString('id-ID');
        document.getElementById('detail-date').textContent = data.created_at;

        const badge = document.getElementById('detail-status-badge');

        if (data.status === 'pending') {
            badge.textContent = 'Pending';
            badge.className = 'rounded-full px-3 py-1 text-xs font-semibold bg-amber-50 text-amber-700';
            actionArea.classList.remove('hidden');
            completedBadge.classList.add('hidden');
        } else {
            badge.textContent = 'Completed';
            badge.className = 'rounded-full px-3 py-1 text-xs font-semibold bg-green-100 text-green-800';
            actionArea.classList.add('hidden');
            completedBadge.classList.remove('hidden');
        }

        detailEmpty.classList.add('hidden');
        detailCard.classList.remove('hidden');
    }

    function hideDetail() {
        detailCard.classList.add('hidden');
        detailEmpty.classList.remove('hidden');
        currentRedeemId = null;
    }

    // ─── Complete redeem ───────────────────────────────
    btnComplete.addEventListener('click', async function () {
        if (!currentRedeemId) return;

        btnComplete.disabled = true;
        btnComplete.textContent = 'Memproses...';

        try {
            const res = await fetch('/admin/voucher/' + currentRedeemId + '/complete', {
                method: 'PATCH',
                headers: {
                    'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
            });

            const json = await res.json();

            if (res.ok && json.success) {
                const badge = document.getElementById('detail-status-badge');
                badge.textContent = 'Completed';
                badge.className = 'rounded-full px-3 py-1 text-xs font-semibold bg-green-100 text-green-800';
                actionArea.classList.add('hidden');
                completedBadge.classList.remove('hidden');
                showAlert('Hadiah berhasil diserahkan!', 'success');
            } else {
                showAlert(json.message || 'Gagal mengubah status voucher.', 'error');
            }
        } catch (err) {
            showAlert('Tidak dapat terhubung ke server.', 'error');
        } finally {
            btnComplete.disabled = false;
            btnComplete.textContent = 'Konfirmasi Penyerahan Hadiah';
        }
    });

    // ─── Table row click to auto-verify ────────────────
    document.querySelectorAll('tr[data-code]').forEach(function (row) {
        row.addEventListener('click', function () {
            voucherInput.value = this.dataset.code;
            verifyVoucher();
        });
    });

    // ─── Helpers ───────────────────────────────────────
    function showAlert(message, type) {
        alertBanner.textContent = message;
        alertBanner.className = 'mb-4 rounded-lg px-4 py-3 text-sm';
        if (type === 'error') {
            alertBanner.classList.add('bg-red-50', 'text-red-800');
        } else {
            alertBanner.classList.add('bg-teal-50', 'text-teal-800');
        }
        alertBanner.classList.remove('hidden');
    }

    function hideAlert() {
        alertBanner.classList.add('hidden');
    }

    function getCookie(name) {
        const value = '; ' + document.cookie;
        const parts = value.split('; ' + name + '=');
        if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
        return '';
    }

    // ─── Latih Ulang Model AI ──────────────────────────
    const btnTrain = document.getElementById('btn-train');
    const trainAlert = document.getElementById('train-alert');

    btnTrain.addEventListener('click', async function () {
        btnTrain.disabled = true;
        btnTrain.textContent = 'Melatih...';
        showTrainAlert('Model sedang dilatih. Proses ini bisa memakan waktu beberapa saat...', 'info');

        try {
            const res = await fetch('/admin/model/train', {
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
            });

            const json = await res.json();

            if (res.ok && json.success) {
                document.getElementById('stat-version').textContent = 'v' + json.data.version;
                document.getElementById('stat-accuracy').textContent =
                    json.data.accuracy !== null ? Math.round(json.data.accuracy * 100) + '%' : '—';
                document.getElementById('stat-pending').textContent = '0';
                showTrainAlert(
                    'Model berhasil dilatih! Versi v' + json.data.version +
                    ' (akurasi ' + Math.round(json.data.accuracy * 100) + '%, ' +
                    json.data.sample_count + ' sampel).', 'success');
            } else {
                showTrainAlert(json.message || 'Gagal melatih model.', 'error');
            }
        } catch (err) {
            showTrainAlert('Tidak dapat terhubung ke server AI.', 'error');
        } finally {
            btnTrain.disabled = false;
            btnTrain.textContent = 'Latih Ulang Model';
        }
    });

    function showTrainAlert(message, type) {
        trainAlert.textContent = message;
        trainAlert.className = 'mb-4 rounded-lg px-4 py-3 text-sm';
        if (type === 'error') {
            trainAlert.classList.add('bg-red-50', 'text-red-800');
        } else if (type === 'success') {
            trainAlert.classList.add('bg-teal-50', 'text-teal-800');
        } else {
            trainAlert.classList.add('bg-amber-50', 'text-amber-800');
        }
        trainAlert.classList.remove('hidden');
    }
});
</script>
@endsection
