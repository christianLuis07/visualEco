/* ===================================================================
 * Visueco — Rewards Page Logic
 * Vanilla JS: redeem AJAX, confirm/success modal, textContent (anti-XSS).
 * Endpoint: POST /api/v1/redeem
 * =================================================================== */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const pointsDisplay = document.getElementById('points-display');
        const modalConfirm  = document.getElementById('modal-confirm');
        const modalSuccess  = document.getElementById('modal-success');
        const alertError    = document.getElementById('alert-error');
        const alertErrorTxt = alertError ? alertError.querySelector('[data-alert-text]') : null;

        let pendingRewardId = null;
        let hideTimer = null;

        // ── Buka modal konfirmasi ──
        document.querySelectorAll('.btn-redeem').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (this.disabled) return;
                pendingRewardId = this.dataset.id;
                document.getElementById('confirm-title').textContent = this.dataset.title;
                document.getElementById('confirm-points').textContent =
                    Number(this.dataset.points).toLocaleString('id-ID');
                openModal(modalConfirm);
            });
        });

        // ── Batal ──
        document.getElementById('confirm-cancel').addEventListener('click', closeConfirm);
        modalConfirm.addEventListener('click', function (e) {
            if (e.target === modalConfirm) closeConfirm();
        });

        function closeConfirm() {
            closeModal(modalConfirm);
            pendingRewardId = null;
        }

        // ── Proses redeem ──
        document.getElementById('confirm-proceed').addEventListener('click', async function () {
            if (!pendingRewardId) return;

            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Memproses…';

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
                btn.disabled = false;
                btn.textContent = 'Ya, Tukarkan';
            }
        });

        // ── Modal sukses ──
        function showSuccess(data) {
            document.getElementById('success-code').textContent = data.redemption_code;
            document.getElementById('success-title').textContent = data.reward_title;
            pointsDisplay.textContent = data.points_balance;
            openModal(modalSuccess);
        }

        document.getElementById('success-close').addEventListener('click', function () {
            closeModal(modalSuccess);
            location.reload();
        });

        // ── Error alert ──
        function showError(message) {
            if (!alertErrorTxt) return;
            alertErrorTxt.textContent = message;
            alertError.classList.remove('hidden');
            clearTimeout(hideTimer);
            hideTimer = setTimeout(function () {
                alertError.classList.add('hidden');
            }, 5000);
        }

        // ── Helpers ──
        function openModal(m) {
            m.classList.remove('hidden');
            m.classList.add('flex');
        }
        function closeModal(m) {
            m.classList.add('hidden');
            m.classList.remove('flex');
        }
        function getCookie(name) {
            const value = '; ' + document.cookie;
            const parts = value.split('; ' + name + '=');
            if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
            return '';
        }
    });
})();
