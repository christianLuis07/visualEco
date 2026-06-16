/* ===================================================================
 * Visueco — Admin Panel Logic
 * Vanilla JS: verify voucher, complete redeem, retrain model.
 * DOM via textContent (anti-XSS).
 * Endpoints: POST /admin/voucher/verify, PATCH /admin/voucher/{id}/complete,
 *            POST /admin/model/train
 * =================================================================== */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const voucherInput  = document.getElementById('voucher-input');
        const btnVerify     = document.getElementById('btn-verify');
        const detailEmpty   = document.getElementById('detail-empty');
        const detailCard    = document.getElementById('detail-card');
        const actionArea    = document.getElementById('action-area');
        const completedBadge = document.getElementById('completed-badge');
        const btnComplete   = document.getElementById('btn-complete');
        const statusBadge   = document.getElementById('detail-status-badge');

        const alertBanner   = document.getElementById('alert-banner');
        const alertText     = alertBanner ? alertBanner.querySelector('[data-alert-text]') : null;

        let currentRedeemId = null;

        const BADGE_BASE = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ';
        const TONE = {
            pending:   'bg-amber-50 text-amber-700',
            completed: 'bg-emerald-50 text-emerald-700',
        };

        // ── Verify voucher ──
        btnVerify.addEventListener('click', verifyVoucher);
        voucherInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') verifyVoucher();
        });

        async function verifyVoucher() {
            const code = voucherInput.value.trim();
            if (!code) return;

            btnVerify.disabled = true;
            btnVerify.textContent = 'Memeriksa…';
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

        // ── Tampilkan detail voucher ──
        function showDetail(data) {
            currentRedeemId = data.id;

            document.getElementById('detail-code').textContent = data.redemption_code;
            document.getElementById('detail-user').textContent = data.user_name;
            document.getElementById('detail-reward').textContent = data.reward_title;
            document.getElementById('detail-points').textContent =
                Number(data.points_spent).toLocaleString('id-ID');
            document.getElementById('detail-date').textContent = data.created_at;

            applyStatus(data.status === 'pending' ? 'pending' : 'completed');

            detailEmpty.classList.add('hidden');
            detailCard.classList.remove('hidden');
        }

        function applyStatus(tone) {
            if (tone === 'pending') {
                statusBadge.textContent = 'Pending';
                statusBadge.className = BADGE_BASE + TONE.pending;
                actionArea.classList.remove('hidden');
                completedBadge.classList.add('hidden');
            } else {
                statusBadge.textContent = 'Completed';
                statusBadge.className = BADGE_BASE + TONE.completed;
                actionArea.classList.add('hidden');
                completedBadge.classList.remove('hidden');
            }
        }

        function hideDetail() {
            detailCard.classList.add('hidden');
            detailEmpty.classList.remove('hidden');
            currentRedeemId = null;
        }

        // ── Complete redeem ──
        btnComplete.addEventListener('click', async function () {
            if (!currentRedeemId) return;

            btnComplete.disabled = true;
            btnComplete.textContent = 'Memproses…';

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
                    applyStatus('completed');
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

        // ── Klik baris tabel = auto verify ──
        document.querySelectorAll('.voucher-row').forEach(function (row) {
            row.addEventListener('click', function () {
                voucherInput.value = this.dataset.code;
                verifyVoucher();
            });
        });

        // ── Latih Ulang Model AI ──
        const btnTrain = document.getElementById('btn-train');
        const trainAlert = document.getElementById('train-alert');
        const trainAlertText = trainAlert ? trainAlert.querySelector('[data-alert-text]') : null;

        btnTrain.addEventListener('click', async function () {
            btnTrain.disabled = true;
            btnTrain.textContent = 'Melatih…';
            showTrainAlert('Model sedang dilatih. Proses ini bisa memakan waktu beberapa saat…', 'info');

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

        // ── Helpers alert ──
        function setAlert(el, txtEl, message, type) {
            if (!el || !txtEl) return;
            txtEl.textContent = message;
            el.className = 'rounded-xl border px-4 py-3 text-sm backdrop-blur w-full mb-4';
            if (type === 'error') {
                el.classList.add('border-rose-100', 'bg-rose-50/80', 'text-rose-700');
            } else if (type === 'success') {
                el.classList.add('border-teal-100', 'bg-teal-50/80', 'text-[#0D9488]');
            } else {
                el.classList.add('border-amber-100', 'bg-amber-50/80', 'text-amber-700');
            }
        }

        function showAlert(message, type) { setAlert(alertBanner, alertText, message, type); }
        function hideAlert() { if (alertBanner) alertBanner.classList.add('hidden'); }
        function showTrainAlert(message, type) { setAlert(trainAlert, trainAlertText, message, type); }

        function getCookie(name) {
            const value = '; ' + document.cookie;
            const parts = value.split('; ' + name + '=');
            if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
            return '';
        }
    });
})();
