/* ===================================================================
 * Visueco — Dashboard Page Logic
 * Vanilla JS: AJAX fetch, state machine UI, DOM via textContent (anti-XSS).
 * Endpoint: POST /api/v1/scan  &  POST /api/v1/scan/confirm
 * =================================================================== */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // ── Element refs ──
        const imageInput      = document.getElementById('image-input');
        const imagePreview    = document.getElementById('image-preview');
        const placeholder     = document.getElementById('dropzone-placeholder');
        const btnScan         = document.getElementById('btn-scan');
        const pointsDisplay   = document.getElementById('points-display');

        const stateIdle       = document.getElementById('state-idle');
        const stateLoading    = document.getElementById('state-loading');
        const stateResult     = document.getElementById('state-result');

        const alertBanner     = document.getElementById('alert-banner');
        const alertText       = alertBanner.querySelector('[data-alert-text]');

        // Konfirmasi belajar
        const confirmBlock    = document.getElementById('confirm-block');
        const confirmActions  = document.getElementById('confirm-actions');
        const correctPicker   = document.getElementById('correct-picker');
        const confirmThanks   = document.getElementById('confirm-thanks');
        const btnConfirmYes   = document.getElementById('btn-confirm-yes');
        const btnConfirmNo    = document.getElementById('btn-confirm-no');
        const btnSubmitCorr   = document.getElementById('btn-submit-correction');
        const correctCategory = document.getElementById('correct-category');

        let selectedFile = null;
        let currentScanId = null;
        let currentCategoryId = null;

        // ── File selection & preview ──
        imageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const allowed = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!allowed.includes(file.type)) {
                showAlert('Format file tidak didukung. Gunakan JPG atau PNG.', 'error');
                this.value = '';
                return;
            }
            if (file.size > 4 * 1024 * 1024) {
                showAlert('Ukuran file melebihi batas 4 MB.', 'error');
                this.value = '';
                return;
            }

            selectedFile = file;
            hideAlert();

            const reader = new FileReader();
            reader.onload = function (e) {
                imagePreview.src = e.target.result;
                imagePreview.classList.remove('hidden');
                placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);

            btnScan.disabled = false;
        });

        // ── Submit scan via fetch ──
        btnScan.addEventListener('click', async function () {
            if (!selectedFile) return;

            setLoading(true);
            hideAlert();

            const formData = new FormData();
            formData.append('image', selectedFile);

            try {
                const res = await fetch('/api/v1/scan', {
                    method: 'POST',
                    headers: {
                        'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: formData,
                });

                const json = await res.json();

                if (res.status === 201 && json.success) {
                    showResult(json.data);
                } else {
                    showState('idle');
                    showAlert(json.message || 'Terjadi kesalahan saat memproses gambar.', 'error');
                }
            } catch (err) {
                showState('idle');
                showAlert('Tidak dapat terhubung ke server. Periksa koneksi Anda.', 'error');
            } finally {
                btnScan.disabled = false;
                btnScan.textContent = 'Analisis Sampah';
            }
        });

        // ── State machine ──
        function showState(name) {
            stateIdle.classList.toggle('hidden', name !== 'idle');
            stateLoading.classList.toggle('hidden', name !== 'loading');
            stateResult.classList.toggle('hidden', name !== 'result');
        }

        function setLoading(loading) {
            if (loading) {
                showState('loading');
                btnScan.disabled = true;
                btnScan.textContent = 'Menganalisis…';
            }
        }

        // ── Render result (textContent only — anti XSS) ──
        function showResult(data) {
            document.getElementById('result-label').textContent = data.detected_item;
            document.getElementById('result-score').textContent =
                Math.round((data.confidence_score || 0) * 100) + '% akurasi';
            document.getElementById('result-category').textContent = data.category_name;
            document.getElementById('result-points').textContent = data.points_awarded;

            const list = document.getElementById('result-instructions');
            list.textContent = '';
            (data.instructions || []).forEach(function (text) {
                const li = document.createElement('li');
                li.className = 'flex items-start gap-2 text-sm text-slate-700';

                const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                icon.setAttribute('class', 'mt-0.5 h-4 w-4 shrink-0 text-[#0D9488]');
                icon.setAttribute('fill', 'none');
                icon.setAttribute('viewBox', '0 0 24 24');
                icon.setAttribute('stroke-width', '2');
                icon.setAttribute('stroke', 'currentColor');
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('stroke-linecap', 'round');
                path.setAttribute('stroke-linejoin', 'round');
                path.setAttribute('d', 'M9 12.75 11.25 15 15 9.75');
                icon.appendChild(path);

                const span = document.createElement('span');
                span.textContent = text;

                li.appendChild(icon);
                li.appendChild(span);
                list.appendChild(li);
            });

            // Konfirmasi belajar
            currentScanId = data.scan_id;
            currentCategoryId = data.category_id || null;
            document.getElementById('confirm-category-name').textContent = data.category_name;
            if (currentCategoryId) {
                correctCategory.value = String(currentCategoryId);
            }
            confirmActions.classList.remove('hidden');
            correctPicker.classList.add('hidden');
            confirmThanks.classList.add('hidden');

            if (data.points_balance !== undefined) {
                pointsDisplay.textContent = data.points_balance;
            }

            showState('result');
            resetInput();
        }

        // ── Konfirmasi belajar ──
        btnConfirmYes.addEventListener('click', function () {
            const catId = currentCategoryId || parseInt(correctCategory.value, 10);
            if (!catId) {
                confirmThanks.textContent = 'Kategori tidak terbaca. Coba pilih lewat "Bukan, koreksi".';
                confirmThanks.classList.remove('hidden');
                return;
            }
            sendConfirmation(catId);
        });

        btnConfirmNo.addEventListener('click', function () {
            correctPicker.classList.remove('hidden');
            confirmActions.classList.add('hidden');
        });

        btnSubmitCorr.addEventListener('click', function () {
            const chosen = parseInt(correctCategory.value, 10);
            if (!chosen) return;
            sendConfirmation(chosen);
        });

        async function sendConfirmation(categoryId) {
            if (!currentScanId) return;

            const buttons = [btnConfirmYes, btnConfirmNo, btnSubmitCorr];
            buttons.forEach(function (b) { if (b) b.disabled = true; });

            try {
                const res = await fetch('/api/v1/scan/confirm', {
                    method: 'POST',
                    headers: {
                        'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        scan_id: currentScanId,
                        correct_category_id: categoryId,
                    }),
                });

                const json = await res.json();

                if (res.status === 201 && json.success) {
                    confirmActions.classList.add('hidden');
                    correctPicker.classList.add('hidden');
                    confirmThanks.textContent = json.message;
                    confirmThanks.classList.remove('hidden');
                    currentScanId = null;
                } else {
                    confirmThanks.textContent = json.message || 'Gagal menyimpan konfirmasi.';
                    confirmThanks.classList.remove('hidden');
                }
            } catch (err) {
                confirmThanks.textContent = 'Tidak dapat terhubung ke server.';
                confirmThanks.classList.remove('hidden');
            } finally {
                buttons.forEach(function (b) { if (b) b.disabled = false; });
            }
        }

        // ── Helpers ──
        function showAlert(message, type) {
            alertText.textContent = message;
            alertBanner.classList.remove('hidden', 'border-rose-100', 'bg-rose-50/80', 'text-rose-700', 'border-teal-100', 'bg-teal-50/80', 'text-[#0D9488]');
            if (type === 'error') {
                alertBanner.classList.add('border-rose-100', 'bg-rose-50/80', 'text-rose-700');
            } else {
                alertBanner.classList.add('border-teal-100', 'bg-teal-50/80', 'text-[#0D9488]');
            }
        }

        function hideAlert() {
            alertBanner.classList.add('hidden');
        }

        function resetInput() {
            selectedFile = null;
            imageInput.value = '';
            imagePreview.classList.add('hidden');
            placeholder.classList.remove('hidden');
            btnScan.disabled = true;
        }

        function getCookie(name) {
            const value = '; ' + document.cookie;
            const parts = value.split('; ' + name + '=');
            if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
            return '';
        }
    });
})();
