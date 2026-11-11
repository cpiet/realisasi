/**
 * SIMRAN Dashboard & Chart.js Integration
 */

window.fmtRp = function (n) {
    const val = Math.round(parseFloat(n) || 0);
    return 'Rp ' + val.toLocaleString('id-ID');
};

window.fmtM = function (n) {
    const val = (parseFloat(n) || 0) / 1e9;
    return val.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 2 }) + ' M';
};

document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi Chart.js Dashboard
    const chartCanvas = document.getElementById('realisasiChart');
    if (chartCanvas && window.chartPayload) {
        const ctx = chartCanvas.getContext('2d');
        const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';

        const getGridColor = () => isDark() ? 'rgba(255, 255, 255, 0.06)' : '#F2F2F7';
        const getTextColor = () => isDark() ? '#A1A1A6' : '#8E8E93';

        window.simranChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: window.chartPayload.labels,
                datasets: [
                    {
                        type: 'line',
                        label: 'Target Kumulatif (RPD)',
                        data: window.chartPayload.target_cumulative,
                        borderColor: '#5856D6',
                        borderWidth: 2.5,
                        borderDash: [5, 4],
                        pointRadius: 4,
                        pointBackgroundColor: '#5856D6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        fill: false,
                        tension: 0.15,
                        yAxisID: 'y',
                    },
                    ...(window.chartPayload.rpa_cumulative ? [{
                        type: 'line',
                        label: 'Rencana Penarikan (RPA Resmi)',
                        data: window.chartPayload.rpa_cumulative,
                        borderColor: '#FF9500',
                        borderWidth: 2.5,
                        pointRadius: 4,
                        pointBackgroundColor: '#FF9500',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        fill: false,
                        tension: 0.15,
                        yAxisID: 'y',
                    }] : []),
                    {
                        type: 'bar',
                        label: 'Realisasi Kumulatif',
                        data: window.chartPayload.real_cumulative,
                        backgroundColor: '#007AFF',
                        hoverBackgroundColor: '#0062CC',
                        borderRadius: 6,
                        barPercentage: 0.52,
                        yAxisID: 'y',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: getTextColor(),
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 18,
                            font: { family: 'Inter', size: 12, weight: 600 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const val = context.raw;
                                if (val === null || val === undefined) return context.dataset.label + ': Belum ada data';
                                return context.dataset.label + ': ' + window.fmtRp(val) + ' (' + window.fmtM(val) + ')';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: getGridColor() },
                        ticks: { color: getTextColor(), font: { family: 'Inter' } }
                    },
                    y: {
                        grid: { color: getGridColor() },
                        ticks: {
                            color: getTextColor(),
                            font: { family: 'Inter' },
                            callback: function (val) {
                                return (val / 1e9).toFixed(0) + ' M';
                            }
                        }
                    }
                }
            }
        });

        window.updateChartTheme = function () {
            if (!window.simranChart) return;
            const dark = isDark();
            window.simranChart.options.scales.x.grid.color = getGridColor();
            window.simranChart.options.scales.y.grid.color = getGridColor();
            window.simranChart.options.scales.x.ticks.color = getTextColor();
            window.simranChart.options.scales.y.ticks.color = getTextColor();
            window.simranChart.options.plugins.legend.labels.color = getTextColor();
            window.simranChart.update();
        };
    }

    const belanjaCanvas = document.getElementById('belanjaChart');
    if (belanjaCanvas && window.belanjaChartPayload) {
        window.simranBelanjaChart = new Chart(belanjaCanvas, {
            type: 'doughnut',
            data: {
                labels: window.belanjaChartPayload.labels,
                datasets: [{
                    data: window.belanjaChartPayload.values,
                    backgroundColor: window.belanjaChartPayload.colors,
                    borderColor: document.documentElement.getAttribute('data-theme') === 'dark' ? '#2C2C2E' : '#FFFFFF',
                    borderWidth: 4,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: context => context.label + ': ' + window.fmtRp(context.raw)
                        }
                    }
                }
            }
        });
    }

    // 2. Global Search Event Handler
    const searchInput = document.getElementById('globalSearchInput');
    const searchResults = document.getElementById('searchResultsBox');
    let searchDebounce = null;

    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();
            clearTimeout(searchDebounce);

            if (query.length < 2) {
                searchResults.style.display = 'none';
                searchResults.innerHTML = '';
                return;
            }

            searchDebounce = setTimeout(function () {
                const yearParam = window.simranCurrentYear ? '&year=' + encodeURIComponent(window.simranCurrentYear) : '';
                fetch((window.simranBaseUrl || '') + '/api/kegiatan/search?q=' + encodeURIComponent(query) + yearParam)
                    .then(res => res.json())
                    .then(res => {
                        if (res.status === 'success' && res.results.length > 0) {
                            let html = '';
                            res.results.forEach(item => {
                                html += `<div class="search-item" onclick="openActivityDetail(${item.id})">
                                    <div class="d-flex justify-content-between">
                                        <span class="code">${item.account_code || ''}</span>
                                        <span class="text-muted" style="font-size:11.5px">${window.fmtM(item.final_ceiling)}</span>
                                    </div>
                                    <div class="desc text-truncate">${item.description || item.activity_name || ''}</div>
                                </div>`;
                            });
                            searchResults.innerHTML = html;
                            searchResults.style.display = 'block';
                        } else {
                            searchResults.innerHTML = '<div class="p-3 text-muted text-center" style="font-size:12.5px">Tidak ditemukan kegiatan yang cocok.</div>';
                            searchResults.style.display = 'block';
                        }
                    })
                    .catch(() => {
                        searchResults.style.display = 'none';
                    });
            }, 250);
        });

        // Sembunyikan saat klik di luar
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }

    // 3. Activity Detail Modal Viewer
    window.openActivityDetail = function (detailId) {
        if (searchResults) searchResults.style.display = 'none';
        const modalEl = document.getElementById('activityDetailModal');
        if (!modalEl) return;

        const modal = new bootstrap.Modal(modalEl);
        document.getElementById('modalDetailTitle').textContent = 'Memuat data...';
        document.getElementById('modalDetailBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
        modal.show();

        fetch((window.simranBaseUrl || '') + '/kegiatan/detail/' + detailId)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    const d = res.data;
                    const it = d.item;
                    document.getElementById('modalDetailTitle').textContent = (it.account_code ? it.account_code + ' — ' : '') + (it.description || it.activity_name);

                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                    let monthlyRows = '';
                    let cum = 0;
                    for (let m = 1; m <= 12; m++) {
                        const val = d.monthly[m] || 0;
                        cum += val;
                        monthlyRows += `<tr>
                            <td>${months[m - 1]}</td>
                            <td class="text-end">${val > 0 ? window.fmtRp(val) : '—'}</td>
                            <td class="text-end text-muted">${cum > 0 ? window.fmtRp(cum) : '—'}</td>
                        </tr>`;
                    }

                    const html = `
                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <div class="p-3 bg-light rounded border">
                                    <div class="text-muted" style="font-size:11.5px">PAGU ANGGARAN</div>
                                    <div class="h5 mb-0 fw-bold">${window.fmtRp(d.pagu)}</div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-3 bg-light rounded border">
                                    <div class="text-muted" style="font-size:11.5px">REALISASI</div>
                                    <div class="h5 mb-0 fw-bold text-primary">${window.fmtRp(d.total_real)}</div>
                                    <small class="text-muted">${d.serap_pct}% terserap</small>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-3 bg-light rounded border">
                                    <div class="text-muted" style="font-size:11.5px">SISA ANGGARAN</div>
                                    <div class="h5 mb-0 fw-bold">${window.fmtRp(d.sisa)}</div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height:260px">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr><th>Bulan</th><th class="text-end">Realisasi (Rp)</th><th class="text-end">Kumulatif (Rp)</th></tr>
                                </thead>
                                <tbody>
                                    ${monthlyRows}
                                </tbody>
                            </table>
                        </div>
                    `;
                    document.getElementById('modalDetailBody').innerHTML = html;
                } else {
                    document.getElementById('modalDetailBody').innerHTML = '<div class="alert alert-danger">Gagal memuat data.</div>';
                }
            })
            .catch(() => {
                document.getElementById('modalDetailBody').innerHTML = '<div class="alert alert-danger">Terjadi kesalahan saat memuat data.</div>';
            });
    };
});
