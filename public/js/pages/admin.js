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

        // ── Latih Model AI (train & seed+train berbagi logika) ──
        const btnTrain = document.getElementById('btn-train');
        const btnSeedTrain = document.getElementById('btn-seed-train');
        const trainAlert = document.getElementById('train-alert');
        const trainAlertText = trainAlert ? trainAlert.querySelector('[data-alert-text]') : null;

        async function runTraining(url, btn, label, startMsg) {
            const buttons = [btnTrain, btnSeedTrain];
            const original = btn.textContent;
            buttons.forEach(function (b) { if (b) b.disabled = true; });
            btn.textContent = label;
            showTrainAlert(startMsg, 'info');

            try {
                const res = await fetch(url, {
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
                    const d = json.data;
                    document.getElementById('stat-version').textContent = 'v' + d.version;
                    document.getElementById('stat-accuracy').textContent =
                        d.accuracy !== null ? Math.round(d.accuracy * 100) + '%' : '—';
                    document.getElementById('stat-pending').textContent = '0';
                    showTrainAlert(json.message + ' Versi v' + d.version +
                        ' — akurasi ' + Math.round((d.accuracy || 0) * 100) + '%, ' +
                        d.sample_count + ' sampel.', 'success');
                } else {
                    showTrainAlert(json.message || 'Gagal melatih model.', 'error');
                }
            } catch (err) {
                showTrainAlert('Tidak dapat terhubung ke server AI.', 'error');
            } finally {
                buttons.forEach(function (b) { if (b) b.disabled = false; });
                btn.textContent = original;
            }
        }

        btnTrain.addEventListener('click', function () {
            runTraining('/admin/model/train', btnTrain, 'Melatih…',
                'Model sedang dilatih. Proses ini bisa memakan waktu beberapa saat…');
        });

        if (btnSeedTrain) {
            btnSeedTrain.addEventListener('click', function () {
                runTraining('/admin/model/seed-train', btnSeedTrain, 'Memproses…',
                    'Mengimpor foto dari folder seed lalu melatih model…');
            });
        }

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

        // ─── CRUD Reward ───────────────────────────────────
        const rewardModal   = document.getElementById('modal-reward');
        const rewardForm    = document.getElementById('reward-form');
        const rewardDelete  = document.getElementById('modal-reward-delete');
        const rewardTbody   = document.getElementById('reward-tbody');
        const rewardAlert   = document.getElementById('reward-alert');
        const rewardAlertTx = rewardAlert ? rewardAlert.querySelector('[data-alert-text]') : null;

        const fId    = document.getElementById('reward-id');
        const fTitle = document.getElementById('reward-input-title');
        const fDesc  = document.getElementById('reward-input-description');
        const fPts   = document.getElementById('reward-input-points');
        const fStock = document.getElementById('reward-input-stock');
        const modalTitle = document.getElementById('modal-reward-title');

        let deleteId = null;

        function openModal(m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        function closeModal(m) { m.classList.add('hidden'); m.classList.remove('flex'); }

        function clearErrors() {
            document.querySelectorAll('.reward-err').forEach(function (el) {
                el.classList.add('hidden');
                el.textContent = '';
            });
        }

        function showRewardAlert(message, type) {
            if (!rewardAlert || !rewardAlertTx) return;
            rewardAlertTx.textContent = message;
            rewardAlert.className = 'rounded-xl border px-4 py-3 text-sm backdrop-blur w-full mb-4';
            if (type === 'error') {
                rewardAlert.classList.add('border-rose-100', 'bg-rose-50/80', 'text-rose-700');
            } else {
                rewardAlert.classList.add('border-teal-100', 'bg-teal-50/80', 'text-[#0D9488]');
            }
        }

        // Buka modal CREATE
        const btnCreate = document.getElementById('btn-reward-create');
        if (btnCreate) {
            btnCreate.addEventListener('click', function () {
                clearErrors();
                fId.value = ''; fTitle.value = ''; fDesc.value = ''; fPts.value = ''; fStock.value = '';
                modalTitle.textContent = 'Tambah Reward';
                openModal(rewardModal);
            });
        }

        // Buka modal EDIT (delegasi)
        if (rewardTbody) {
            rewardTbody.addEventListener('click', function (e) {
                const editBtn = e.target.closest('.btn-reward-edit');
                const delBtn = e.target.closest('.btn-reward-delete');

                if (editBtn) {
                    clearErrors();
                    fId.value    = editBtn.dataset.id;
                    fTitle.value = editBtn.dataset.title;
                    fDesc.value  = editBtn.dataset.description;
                    fPts.value   = editBtn.dataset.points;
                    fStock.value = editBtn.dataset.stock;
                    modalTitle.textContent = 'Edit Reward';
                    openModal(rewardModal);
                } else if (delBtn) {
                    deleteId = delBtn.dataset.id;
                    document.getElementById('reward-delete-title').textContent = delBtn.dataset.title;
                    openModal(rewardDelete);
                }
            });
        }

        document.getElementById('reward-cancel')?.addEventListener('click', function () { closeModal(rewardModal); });
        document.getElementById('reward-delete-cancel')?.addEventListener('click', function () { closeModal(rewardDelete); deleteId = null; });

        // SUBMIT create/update
        if (rewardForm) {
            rewardForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                clearErrors();

                const id = fId.value;
                const payload = {
                    title:           fTitle.value,
                    description:     fDesc.value,
                    points_required: fPts.value === '' ? null : Number(fPts.value),
                    stock:           fStock.value === '' ? null : Number(fStock.value),
                };
                const url = id ? '/api/v1/admin/rewards/' + id : '/api/v1/admin/rewards';
                const method = id ? 'PUT' : 'POST';

                const submitBtn = document.getElementById('reward-submit');
                submitBtn.disabled = true;

                try {
                    const res = await fetch(url, {
                        method: method,
                        headers: {
                            'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload),
                    });
                    const json = await res.json();

                    if (res.ok && json.success) {
                        closeModal(rewardModal);
                        showRewardAlert(json.message || 'Berhasil disimpan.', 'success');
                        upsertRow(json.data, !id);
                    } else if (res.status === 422 && json.errors) {
                        Object.keys(json.errors).forEach(function (field) {
                            const el = document.querySelector('.reward-err[data-for="' + field + '"]');
                            if (el) { el.textContent = json.errors[field][0]; el.classList.remove('hidden'); }
                        });
                    } else {
                        showRewardAlert(json.message || 'Gagal menyimpan reward.', 'error');
                    }
                } catch (err) {
                    showRewardAlert('Tidak dapat terhubung ke server.', 'error');
                } finally {
                    submitBtn.disabled = false;
                }
            });
        }

        // CONFIRM delete
        document.getElementById('reward-delete-confirm')?.addEventListener('click', async function () {
            if (!deleteId) return;
            this.disabled = true;
            try {
                const res = await fetch('/api/v1/admin/rewards/' + deleteId, {
                    method: 'DELETE',
                    headers: {
                        'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const json = await res.json();

                if (res.ok && json.success) {
                    const row = rewardTbody.querySelector('tr[data-reward-id="' + deleteId + '"]');
                    if (row) row.remove();
                    showRewardAlert(json.message || 'Reward dihapus.', 'success');
                } else {
                    showRewardAlert(json.message || 'Gagal menghapus reward.', 'error');
                }
            } catch (err) {
                showRewardAlert('Tidak dapat terhubung ke server.', 'error');
            } finally {
                this.disabled = false;
                closeModal(rewardDelete);
                deleteId = null;
            }
        });

        // Tambah/perbarui baris tabel (textContent — anti XSS)
        function upsertRow(data, isNew) {
            const emptyRow = document.getElementById('reward-empty-row');
            if (emptyRow) emptyRow.remove();

            let row = rewardTbody.querySelector('tr[data-reward-id="' + data.id + '"]');
            if (!row) {
                row = document.createElement('tr');
                row.setAttribute('data-reward-id', data.id);
                rewardTbody.prepend(row);
            }
            row.textContent = '';

            const tdTitle = document.createElement('td');
            tdTitle.className = 'px-5 py-3.5 text-sm font-medium text-slate-800';
            tdTitle.textContent = data.title;

            const tdPts = document.createElement('td');
            tdPts.className = 'px-5 py-3.5 text-sm text-[#0D9488] font-semibold';
            tdPts.textContent = Number(data.points_required).toLocaleString('id-ID');

            const tdStock = document.createElement('td');
            tdStock.className = 'px-5 py-3.5 text-sm text-slate-600';
            tdStock.textContent = data.stock;

            const tdAct = document.createElement('td');
            tdAct.className = 'px-5 py-3.5 text-right';

            const editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.className = 'btn-reward-edit rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50';
            editBtn.textContent = 'Edit';
            editBtn.dataset.id = data.id;
            editBtn.dataset.title = data.title;
            editBtn.dataset.description = data.description;
            editBtn.dataset.points = data.points_required;
            editBtn.dataset.stock = data.stock;

            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'btn-reward-delete rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50';
            delBtn.textContent = 'Hapus';
            delBtn.dataset.id = data.id;
            delBtn.dataset.title = data.title;

            tdAct.appendChild(editBtn);
            tdAct.appendChild(document.createTextNode(' '));
            tdAct.appendChild(delBtn);

            row.appendChild(tdTitle);
            row.appendChild(tdPts);
            row.appendChild(tdStock);
            row.appendChild(tdAct);
        }
    });
})();
