/**
 * SIMRAN Realisasi Rapid Tabular Input & Auto-Save
 */

document.addEventListener('DOMContentLoaded', function () {
    const saveIndicator = document.getElementById('save-indicator');
    let saveTimeout = null;

    function markSaving() {
        if (saveIndicator) {
            saveIndicator.innerHTML = '<span class="dot" style="background:var(--warn)"></span> menyimpan…';
        }
    }

    function markSaved() {
        if (saveIndicator) {
            saveIndicator.innerHTML = '<span class="dot" style="background:var(--ok)"></span> tersimpan di database';
        }
    }

    function markError() {
        if (saveIndicator) {
            saveIndicator.innerHTML = '<span class="dot" style="background:var(--bad)"></span> gagal menyimpan';
        }
    }

    function recalculateTotals() {
        const months = 12;
        let grandTotal = 0;

        // Hitung total kolom per jenis belanja
        const colTotals = {};
        document.querySelectorAll('.real-input').forEach(input => {
            const btId = input.dataset.btId;
            const val = parseFloat(input.value) || 0;
            colTotals[btId] = (colTotals[btId] || 0) + val;
            grandTotal += val;
        });

        for (const btId in colTotals) {
            const el = document.getElementById('coltot-' + btId);
            if (el) el.textContent = window.fmtRp(colTotals[btId]);
        }

        // Hitung total baris per bulan
        for (let m = 1; m <= months; m++) {
            let rowTotal = 0;
            document.querySelectorAll(`.real-input[data-month="${m}"]`).forEach(input => {
                rowTotal += parseFloat(input.value) || 0;
            });
            const rowEl = document.getElementById('rowtot-' + m);
            if (rowEl) {
                rowEl.textContent = rowTotal > 0 ? window.fmtRp(rowTotal) : '—';
            }
        }

        const grandEl = document.getElementById('grandtot');
        if (grandEl) grandEl.textContent = window.fmtRp(grandTotal);
    }

    function saveCell(input) {
        markSaving();
        clearTimeout(saveTimeout);

        const fyId = input.dataset.fyId;
        const btId = input.dataset.btId;
        const month = input.dataset.month;
        const amount = parseFloat(input.value) || 0;

        const formData = new FormData();
        formData.append('fiscal_year_id', fyId);
        formData.append('budget_type_id', btId);
        formData.append('month', month);
        formData.append('amount', amount);
        if (window.simranCsrf && window.simranCsrf.name) {
            formData.append(window.simranCsrf.name, window.simranCsrf.value);
        }

        saveTimeout = setTimeout(() => {
            fetch((window.simranBaseUrl || '') + '/admin/realisasi/update-cell', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        markSaved();
                    } else {
                        markError();
                    }
                })
                .catch(() => {
                    markError();
                });
        }, 400);
    }

    // Pasang listener pada seluruh input realisasi
    document.querySelectorAll('.real-input').forEach(input => {
        input.addEventListener('input', function () {
            recalculateTotals();
            saveCell(this);
        });
    });

    // Inisialisasi kalkulasi saat pertama kali buka halaman
    recalculateTotals();
});
