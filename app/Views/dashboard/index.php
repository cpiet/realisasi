<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Dashboard Header / Status Bar -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3 pb-3 mb-4" style="border-bottom: 1px solid var(--apple-border-light);">
  <div>
    <div class="eyebrow">EXECUTIVE OVERVIEW · T.A. <?= esc($selected_year['year']) ?></div>
    <h1 class="page-title">Dashboard Monitoring Pimpinan</h1>
  </div>
  <div class="status-dot-badge">
    <span class="dot-ping">
      <span class="dot-ping-circle"></span>
      <span class="dot-ping-solid"></span>
    </span>
    <span>Posisi data realisasi: <strong style="color: var(--apple-charcoal); font-weight: 600;"><?= esc($posisi_text) ?></strong></span>
  </div>
</div>

<!-- BEGIN: Primary Executive KPI Cards (macOS 5-Grid) -->
<section aria-label="Executive KPI Metrics" class="kpi-grid">
  <!-- Card 1: TOTAL PAGU -->
  <div class="kpi-card kpi-blue">
    <div class="d-flex justify-content-between align-items-center mb-1">
      <span class="kpi-label">TOTAL PAGU <?= esc($selected_year['year']) ?></span>
      <?php if (in_array(session()->get('role'), ['admin', 'operator'], true)): ?>
        <a href="<?= base_url('admin/pagu') ?>?year=<?= esc($selected_year['year']) ?>" class="pill pill-blue" style="text-decoration: none;" title="Kelola Pagu DIPA">
          <i class="bi bi-pencil-fill me-1" style="font-size: 10px;"></i> Edit
        </a>
      <?php endif; ?>
    </div>
    <div>
      <div class="kpi-value"><?= number_format($kpi['pagu'] / 1e9, 1, ',', '.') ?> M</div>
      <div class="kpi-sub font-mono">Rp <?= number_format($kpi['pagu'], 0, ',', '.') ?></div>
    </div>
    <div class="kpi-footer-note">
      <span class="dot"></span>
      <span>Pagu Definitif Kemenkeu</span>
    </div>
  </div>

  <!-- Card 2: REALISASI -->
  <div class="kpi-card kpi-green">
    <div class="d-flex justify-content-between align-items-center mb-1">
      <span class="kpi-label">REALISASI (<?= esc($posisi_text) ?>)</span>
      <span class="pill pill-green"><?= number_format($kpi['serap_pct'], 2, ',', '.') ?>%</span>
    </div>
    <div>
      <div class="kpi-value"><?= number_format($kpi['realisasi'] / 1e9, 1, ',', '.') ?> M</div>
      <div class="kpi-sub"><?= number_format($kpi['serap_pct'], 2, ',', '.') ?>% dari total pagu</div>
    </div>
    <div class="kpi-footer-note">
      <span class="dot"></span>
      <span>Target Kumulatif Terpenuhi</span>
    </div>
  </div>

  <!-- Card 3: SISA ANGGARAN -->
  <div class="kpi-card kpi-orange">
    <div class="d-flex justify-content-between align-items-center mb-1">
      <span class="kpi-label">SISA ANGGARAN</span>
      <span class="pill pill-orange"><?= number_format(max(0, 100 - $kpi['serap_pct']), 2, ',', '.') ?>%</span>
    </div>
    <div>
      <div class="kpi-value"><?= number_format($kpi['sisa'] / 1e9, 1, ',', '.') ?> M</div>
      <div class="kpi-sub font-mono">Rp <?= number_format($kpi['sisa'], 0, ',', '.') ?></div>
    </div>
    <div class="kpi-footer-note">
      <span class="dot"></span>
      <span>Tersedia untuk Pelaksanaan</span>
    </div>
  </div>

  <!-- Card 4: TARGET S.D POSISI -->
  <div class="kpi-card kpi-purple">
    <div class="d-flex justify-content-between align-items-center mb-1">
      <span class="kpi-label">TARGET S.D POSISI</span>
      <span class="pill pill-purple">RPD</span>
    </div>
    <div>
      <div class="kpi-value"><?= number_format($kpi['target_pct'], 2, ',', '.') ?>%</div>
      <div class="kpi-sub">Rp <?= number_format($kpi['target_rp'] / 1e9, 1, ',', '.') ?> M (RPD)</div>
    </div>
    <div class="kpi-footer-note">
      <span class="dot"></span>
      <span>Rencana Hal. III DIPA</span>
    </div>
  </div>

  <!-- Card 5: DEVIASI VS TARGET -->
  <?php 
    $isDeviasiGood = $kpi['deviasi'] >= 0;
    $deviasiClass = $isDeviasiGood ? 'kpi-green' : 'kpi-red';
  ?>
  <div class="kpi-card <?= $deviasiClass ?>">
    <div class="d-flex justify-content-between align-items-center mb-1">
      <span class="kpi-label">DEVIASI VS TARGET</span>
      <span class="pill <?= $isDeviasiGood ? 'pill-green' : 'pill-red' ?>"><?= esc($kpi['deviasi_label']) ?></span>
    </div>
    <div>
      <div class="kpi-value">
        <?= $kpi['deviasi'] >= 0 ? '+' : '−' ?><?= number_format(abs($kpi['deviasi']) / 1e9, 1, ',', '.') ?> M
      </div>
      <div class="kpi-sub"><?= $kpi['deviasi'] >= 0 ? 'di atas target RPD' : 'di bawah target RPD' ?></div>
    </div>
    <div class="kpi-footer-note">
      <span class="dot"></span>
      <span>Deviasi realisasi s.d <?= esc($posisi_text) ?></span>
    </div>
  </div>
</section>
<!-- END: Primary Executive KPI Cards -->

<!-- BEGIN: Target Penarikan Anggaran Akhir Tahun (RPA) -->
<?php if (!empty($rpa_data)): ?>
<section class="card-simran p-0 overflow-hidden mb-4">
  <!-- Section Header -->
  <div class="p-4 border-bottom d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3" style="background: var(--apple-card-subtle);">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <span class="pill pill-orange fw-bold">RPA T.A. <?= esc($selected_year['year']) ?></span>
      <div>
        <h2 class="h6 fw-bold mb-0" style="color: var(--apple-charcoal);">Target Penarikan Anggaran Akhir Tahun (September s.d. Desember)</h2>
        <div class="text-muted small mt-0.5">
          Revisi <?= esc($rpa_data['revision']['revision_no'] ?? '-') ?> · Sesuai Rencana Penarikan Anggaran DIPA Resmi
        </div>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="<?= base_url('kegiatan/uraian?year=' . esc($selected_year['year'])) ?>" class="btn-simran btn-simran-sm" title="Lihat 110 Rincian Operasional Kegiatan">
        <i class="bi bi-card-checklist text-primary"></i>
        <span>Uraian Kegiatan (110 Giat)</span>
      </a>
      <a href="<?= base_url('admin/rpa?year=' . esc($selected_year['year'])) ?>" class="btn-simran btn-simran-sm btn-simran-primary" title="Buka Lembar Kerja RPA Lengkap">
        <span>Buka Lembar Kerja RPA T.A. <?= esc($selected_year['year']) ?></span>
        <span>→</span>
      </a>
    </div>
  </div>

  <!-- 5 Milestone Mini Cards -->
  <div class="p-4 grid-milestones border-bottom" style="background: var(--apple-card);">
    <div class="row g-3">
      <!-- Q4 Total -->
      <div class="col-12 col-md-4 col-xl-3">
        <div class="card-simran-subtle h-100 d-flex flex-column justify-content-between" style="background: var(--apple-blue-tint); border-color: var(--apple-blue-border);">
          <span class="kpi-label" style="color: var(--apple-blue);">ALOKASI 4 BULAN AKHIR (Q4)</span>
          <div class="mt-2">
            <div class="h5 fw-bold font-mono tabular-nums mb-1" style="color: var(--apple-charcoal);">
              Rp <?= number_format($rpa_data['q4_total'] / 1e9, 2, ',', '.') ?> M
            </div>
            <div class="small text-muted font-mono tabular-nums">
              <strong><?= number_format($rpa_data['q4_pct'], 1, ',', '.') ?>%</strong> dari seluruh Pagu Satker
            </div>
          </div>
        </div>
      </div>

      <!-- September -->
      <div class="col-6 col-md-2 col-xl-2">
        <div class="card-simran-subtle h-100 d-flex flex-column justify-content-between">
          <span class="kpi-label">SEPTEMBER</span>
          <div class="mt-2">
            <div class="h6 fw-bold font-mono tabular-nums mb-1" style="color: var(--apple-charcoal);">
              Rp <?= number_format($rpa_data['sep'] / 1e9, 2, ',', '.') ?> M
            </div>
            <div class="small text-muted font-mono tabular-nums text-truncate">Rp <?= number_format($rpa_data['sep'], 0, ',', '.') ?></div>
          </div>
        </div>
      </div>

      <!-- Oktober (Puncak) -->
      <div class="col-6 col-md-2 col-xl-3">
        <div class="card-simran-subtle h-100 d-flex flex-column justify-content-between position-relative" style="background: var(--apple-orange-tint); border-color: var(--apple-orange-border);">
          <div class="d-flex justify-content-between align-items-center">
            <span class="kpi-label" style="color: var(--apple-orange);">OKTOBER (PUNCAK DIPA)</span>
            <span class="pill pill-orange" style="font-size: 9px; padding: 1px 6px;">★ PUNCAK</span>
          </div>
          <div class="mt-2">
            <div class="h6 fw-bold font-mono tabular-nums mb-1" style="color: var(--apple-orange);">
              Rp <?= number_format($rpa_data['okt'] / 1e9, 2, ',', '.') ?> M
            </div>
            <div class="small font-mono tabular-nums text-truncate" style="color: var(--apple-secondary);">Rp <?= number_format($rpa_data['okt'], 0, ',', '.') ?></div>
          </div>
        </div>
      </div>

      <!-- November -->
      <div class="col-6 col-md-2 col-xl-2">
        <div class="card-simran-subtle h-100 d-flex flex-column justify-content-between">
          <span class="kpi-label">NOVEMBER</span>
          <div class="mt-2">
            <div class="h6 fw-bold font-mono tabular-nums mb-1" style="color: var(--apple-charcoal);">
              Rp <?= number_format($rpa_data['nov'] / 1e9, 2, ',', '.') ?> M
            </div>
            <div class="small text-muted font-mono tabular-nums text-truncate">Rp <?= number_format($rpa_data['nov'], 0, ',', '.') ?></div>
          </div>
        </div>
      </div>

      <!-- Desember -->
      <div class="col-6 col-md-2 col-xl-2">
        <div class="card-simran-subtle h-100 d-flex flex-column justify-content-between">
          <span class="kpi-label">DESEMBER (TUTUP TA)</span>
          <div class="mt-2">
            <div class="h6 fw-bold font-mono tabular-nums mb-1" style="color: var(--apple-charcoal);">
              Rp <?= number_format($rpa_data['des'] / 1e9, 2, ',', '.') ?> M
            </div>
            <div class="small text-muted font-mono tabular-nums text-truncate">Rp <?= number_format($rpa_data['des'], 0, ',', '.') ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Top 5 Strategic Activities in Q4 Table -->
  <?php if (!empty($rpa_data['top_q4_items'])): ?>
    <div class="p-4 pb-2">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
          <span class="text-warning">★</span>
          <h3 class="eyebrow mb-0" style="color: var(--apple-charcoal); font-size: 11.5px;">5 KEGIATAN PRIORITAS DENGAN RENCANA PENARIKAN TERBESAR DI Q4 (SEP - DES)</h3>
        </div>
        <span class="pill pill-neutral">Wajib Dikawal Pimpinan</span>
      </div>
      <div class="table-responsive">
        <table class="table-simran mb-0">
          <thead>
            <tr>
              <th style="width: 110px;">Kode</th>
              <th>Nama Kegiatan / Akun</th>
              <th class="text-end" style="width: 140px;">September</th>
              <th class="text-end col-highlight" style="width: 140px;">Oktober (Puncak)</th>
              <th class="text-end" style="width: 140px;">November</th>
              <th class="text-end" style="width: 140px;">Desember</th>
              <th class="text-end fw-bold" style="width: 160px;">Total Q4</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rpa_data['top_q4_items'] as $item): ?>
              <tr>
                <td><span class="pill pill-neutral font-mono"><?= esc($item['activity_code']) ?></span></td>
                <td class="fw-semibold text-truncate" style="max-width: 380px;" title="<?= esc($item['activity_name']) ?>">
                  <?= esc($item['activity_name']) ?>
                </td>
                <td class="text-end font-mono tabular-nums"><?= (float)$item['month_9'] > 0 ? number_format($item['month_9'], 0, ',', '.') : '-' ?></td>
                <td class="text-end font-mono tabular-nums col-highlight"><?= (float)$item['month_10'] > 0 ? number_format($item['month_10'], 0, ',', '.') : '-' ?></td>
                <td class="text-end font-mono tabular-nums"><?= (float)$item['month_11'] > 0 ? number_format($item['month_11'], 0, ',', '.') : '-' ?></td>
                <td class="text-end font-mono tabular-nums"><?= (float)$item['month_12'] > 0 ? number_format($item['month_12'], 0, ',', '.') : '-' ?></td>
                <td class="text-end font-mono tabular-nums fw-bold" style="color: var(--apple-charcoal);">Rp <?= number_format($item['q4_total'], 0, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
  <div class="p-2"></div>
</section>
<?php endif; ?>
<!-- END: Target Penarikan Anggaran Akhir Tahun -->

<!-- BEGIN: Realisasi per Jenis Belanja -->
<section class="card-simran">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pb-3 mb-4 border-bottom">
    <div>
      <h2 class="h6 fw-bold mb-0" style="color: var(--apple-charcoal);">Realisasi per Jenis Belanja</h2>
      <div class="text-muted small mt-0.5">Posisi data: <?= esc($posisi_text) ?></div>
    </div>
    <?php if (in_array(session()->get('role'), ['admin', 'operator'], true)): ?>
      <a href="<?= base_url('admin/pagu') ?>?year=<?= esc($selected_year['year']) ?>" class="btn-simran btn-simran-sm" title="Atur Pagu DIPA Belanja Pegawai, Barang, Modal">
        <i class="bi bi-pencil-square text-primary"></i>
        <span>Edit Pagu DIPA</span>
      </a>
    <?php endif; ?>
  </div>

  <?php foreach ($belanja_summary as $b): 
    $pillType = $b['status'] === 'good' ? 'pill-green' : ($b['status'] === 'alert' ? 'pill-red' : 'pill-orange');
    $barColor = $b['type']['color'] ?? '#007AFF';
  ?>
    <div class="bar-row">
      <div class="bar-head">
        <div class="d-flex align-items-center gap-2">
          <strong style="color: var(--apple-charcoal);"><?= esc($b['type']['name']) ?></strong>
          <span class="pill <?= $pillType ?>"><?= esc($b['status_label']) ?></span>
        </div>
        <div class="r">
          <span style="color: var(--apple-charcoal); font-weight: 600;"><?= number_format($b['realisasi'] / 1e9, 1, ',', '.') ?> M</span> / <?= number_format($b['pagu'] / 1e9, 1, ',', '.') ?> M · <strong style="color: var(--apple-charcoal);"><?= number_format($b['serap_pct'], 2, ',', '.') ?>%</strong>
        </div>
      </div>
      <div class="track" title="Realisasi: <?= $b['serap_pct'] ?>% | Target: <?= $b['target_pct'] ?>%">
        <div class="fill" style="width: <?= min(100, max(0, $b['serap_pct'])) ?>%; background-color: <?= esc($barColor) ?>;"></div>
        <div class="tickmark" style="left: <?= min(100, max(0, $b['target_pct'])) ?>%;" title="Target RPD: <?= $b['target_pct'] ?>%"></div>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="d-flex align-items-center gap-2 mt-3 pt-2 text-muted" style="font-size: 11.5px;">
    <span style="display: inline-block; width: 10px; height: 10px; background: var(--apple-charcoal); opacity: 0.6; border-radius: 2px;"></span>
    <span>Garis vertikal menunjukkan target RPD minimal s.d posisi bulan berjalan (<?= number_format($kpi['target_pct'], 2, ',', '.') ?>%)</span>
  </div>
</section>
<!-- END: Realisasi per Jenis Belanja -->

<!-- BEGIN: Visual Chart Section (Tren Bulanan & Komposisi Pagu) -->
<section class="dashboard-chart-grid">
  <!-- Left: Tren Bulanan (Realisasi Kumulatif vs RPD) -->
  <div class="card-simran chart-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <div class="eyebrow">TREN BULANAN</div>
        <h2 class="h6 fw-bold mb-0">Realisasi Kumulatif vs RPD</h2>
      </div>
      <span class="pill pill-neutral font-mono fw-bold"><?= esc($selected_year['year']) ?></span>
    </div>
    <div class="chart-canvas-wrap">
      <canvas id="realisasiChart"></canvas>
    </div>
  </div>

  <!-- Right: Komposisi Pagu (Donut Chart) -->
  <div class="card-simran chart-card">
    <div class="eyebrow">KOMPOSISI PAGU</div>
    <h2 class="h6 fw-bold mb-0">Distribusi Jenis Belanja</h2>
    <div class="donut-wrap">
      <canvas id="belanjaChart"></canvas>
      <div class="donut-center">
        <strong><?= number_format($kpi['pagu'] / 1e9, 1, ',', '.') ?></strong>
        <span>Miliar</span>
      </div>
    </div>
    <div class="chart-legend-custom">
      <?php foreach ($belanja_summary as $b): ?>
        <div>
          <div class="d-flex align-items-center">
            <span class="legend-dot" style="background: <?= esc($b['type']['color'] ?? '#007AFF') ?>"></span>
            <span><?= esc($b['type']['name']) ?></span>
          </div>
          <b><?= number_format($b['pagu'] / 1e9, 1, ',', '.') ?> M</b>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<!-- END: Visual Chart Section -->

<!-- BEGIN: Rekapitulasi per Triwulan -->
<section class="card-simran">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="h6 fw-bold mb-0">Rekapitulasi Target &amp; Realisasi per Triwulan</h2>
      <div class="text-muted small mt-0.5">Berdasarkan RPD Halaman III DIPA</div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table-simran">
      <thead>
        <tr>
          <th>Jenis Belanja</th>
          <th>TW I (Target)</th>
          <th>TW II (Target)</th>
          <th>TW III (Target)</th>
          <th>TW IV (Target)</th>
          <th class="text-end">Realisasi Kumulatif</th>
          <th class="text-center">Capaian (%)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($quarter_recap as $qr): ?>
          <tr>
            <td><strong style="color: var(--apple-charcoal);"><?= esc($qr['type']['name']) ?></strong></td>
            <?php for ($q = 0; $q < 4; $q++): ?>
              <td>
                <div class="font-mono tabular-nums"><?= number_format($qr['q_targets'][$q]['rp'] / 1e9, 1, ',', '.') ?> M</div>
                <small class="text-muted font-mono"><?= $qr['q_targets'][$q]['pct'] ?>%</small>
              </td>
            <?php endfor; ?>
            <td class="text-end font-mono tabular-nums fw-bold" style="color: var(--apple-charcoal);">
              Rp <?= number_format($qr['realisasi'], 0, ',', '.') ?>
            </td>
            <td class="text-center">
              <span class="pill <?= $qr['serap_pct'] >= 70 ? 'pill-green' : ($qr['serap_pct'] >= 40 ? 'pill-orange' : 'pill-red') ?>">
                <?= number_format($qr['serap_pct'], 2, ',', '.') ?>%
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<!-- END: Rekapitulasi per Triwulan -->

<!-- BEGIN: Nilai IKPA 7-Indikator Scorecard -->
<section class="card-simran">
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 pb-3 mb-3 border-bottom">
    <div>
      <h2 class="h6 fw-bold mb-0">Nilai IKPA — Komposit 7 Indikator</h2>
      <div class="text-muted small mt-0.5">Indikator Kinerja Pelaksanaan Anggaran Kementerian Keuangan<?= $ikpa_raw ? ' · Periode ' . esc(\App\Models\IkpaModel::monthName((int)$ikpa_raw['period_month'])) . ' ' . esc($selected_year['year']) : '' ?></div>
    </div>
    <?php if ($ikpa_score): ?>
      <div class="d-flex align-items-center gap-2">
        <span class="text-muted small">Skor Akhir:</span>
        <span class="pill pill-orange fw-bold" style="font-size: 13px; padding: 4px 12px;">
          <?= esc($ikpa_score['score']) ?> (<?= esc($ikpa_score['category']) ?>)
        </span>
      </div>
    <?php endif; ?>
  </div>

  <div class="ikpa-grid">
    <?php 
      $indicatorsDef = \App\Models\IkpaModel::$indicators;
      foreach ($indicatorsDef as $k => $ind): 
        $val = $ikpa_raw[$k] ?? null;
    ?>
      <div class="ikpa-cell">
        <div class="cap"><b><?= esc($ind['label']) ?></b> · <?= $ind['weight'] ?>%</div>
        <div class="val">
          <?= ($val !== null && $val !== '') ? number_format((float)$val, 2, ',', '.') : '<span class="text-muted" style="font-size:14px">—</span>' ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center pt-2 gap-2">
    <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 12px;">
      <span class="fw-semibold" style="color: var(--apple-charcoal);">Dispensasi SPM:</span>
      <span class="font-mono fw-bold" style="color: var(--apple-red);"><?= isset($ikpa_raw['dispensasi']) && $ikpa_raw['dispensasi'] > 0 ? '−' . number_format((float)$ikpa_raw['dispensasi'], 2, ',', '.') : '0' ?></span>
      <span class="text-muted">(faktor pengurang)</span>
    </div>
    <?php if (in_array(session()->get('role'), ['admin', 'operator'], true)): ?>
      <a href="<?= base_url('admin/ikpa') ?>?year=<?= $selected_year['year'] ?>" class="btn-simran btn-simran-sm">
        <i class="bi bi-arrow-repeat text-primary"></i>
        <span>Perbarui Nilai IKPA</span>
      </a>
    <?php else: ?>
      <span class="text-muted" style="font-size: 11.5px;">Data indikator bersumber dari OMSPAN / Monev SAKTI Kemenkeu.</span>
    <?php endif; ?>
  </div>
</section>
<!-- END: Nilai IKPA 7-Indikator Scorecard -->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  window.chartPayload = <?= json_encode($chart_data) ?>;
  window.belanjaChartPayload = <?= json_encode([
    'labels' => array_map(fn($item) => $item['type']['name'], $belanja_summary),
    'values' => array_map(fn($item) => $item['pagu'], $belanja_summary),
    'colors' => array_map(fn($item) => $item['type']['color'] ?? '#007AFF', $belanja_summary),
  ]) ?>;
</script>
<?= $this->endSection() ?>
