<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
  $sourceTotal = (float)($officialTotals['ceiling'] ?? 0);
  $grandPeriodTotal = (float)($officialTotals['period_total'] ?? 0);
  $percentPeriod = $sourceTotal > 0 ? ($grandPeriodTotal / $sourceTotal) * 100 : 0;
?>

<style>
/* =========================================================
   Excel / Spreadsheet Styled Theme for RPA Table (Compact & Clean)
   ========================================================= */
.excel-table-container {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  position: relative;
}

/* Scroll modes */
.excel-scroll-viewport {
  overflow-x: auto;
  overflow-y: auto;
  max-height: 75vh;
  min-height: 480px;
  position: relative;
  scrollbar-width: thin;
  scrollbar-color: #94a3b8 #f1f5f9;
}

.excel-scroll-viewport::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
.excel-scroll-viewport::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}
.excel-scroll-viewport::-webkit-scrollbar-thumb {
  background: #94a3b8;
  border-radius: 4px;
  border: 2px solid #f1f5f9;
}
.excel-scroll-viewport::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}

/* Full Page Scroll Mode */
.excel-scroll-fullpage {
  overflow-x: auto !important;
  overflow-y: visible !important;
  max-height: none !important;
}

/* Table styling like Excel - Compact */
.table-excel {
  border-collapse: separate;
  border-spacing: 0;
  width: 100%;
  font-size: 11.5px;
  color: #1e293b;
  margin-bottom: 0;
}

.table-excel th,
.table-excel td {
  border-right: 1px solid #e2e8f0;
  border-bottom: 1px solid #e2e8f0;
  padding: 4px 6px;
  vertical-align: middle;
}

/* Sticky Thead */
.table-excel thead tr.header-columns th {
  background: #0f172a;
  color: #f8fafc;
  font-weight: 600;
  font-size: 11px;
  letter-spacing: 0.3px;
  text-align: center;
  position: sticky;
  top: 0;
  z-index: 25;
  box-shadow: 0 1px 2px rgba(0,0,0,0.1);
  padding: 6px 5px;
  border-bottom: 1px solid #334155;
  white-space: nowrap;
}

/* Sticky top grand total in thead (seamlessly attached below header-columns) */
.table-excel thead tr.excel-grand-total th {
  background: #fef08a !important;
  color: #854d0e !important;
  font-weight: 700;
  border-bottom: 2px solid #ca8a04;
  position: sticky;
  top: 30px;
  z-index: 24;
  font-size: 11px;
  padding: 6px 5px;
  box-shadow: 0 3px 6px rgba(0,0,0,0.06);
  white-space: nowrap;
}

/* Row Hierarchies */
.table-excel tr.row-program td {
  background: #fef9c3;
  color: #713f12;
  font-weight: 700;
  border-bottom: 1px solid #fde047;
}

.table-excel tr.row-kegiatan td {
  background: #e0f2fe;
  color: #0369a1;
  font-weight: 600;
  border-bottom: 1px solid #bae6fd;
}

.table-excel tr.row-akun td {
  background: #f8fafc;
  font-weight: 600;
  color: #1e293b;
  border-bottom: 1px solid #cbd5e1;
}

.table-excel tr.row-sublevel td {
  background: #ffffff;
  font-weight: 500;
  color: #0f172a;
}

.table-excel tr.row-leaf td {
  background: #ffffff;
  color: #334155;
}

.table-excel tr.row-leaf:hover td,
.table-excel tr.row-sublevel:hover td {
  background: #f1f5f9;
}

/* Bottom Grand Total */
.table-excel tr.excel-grand-total-bottom td {
  background: #fef08a !important;
  color: #854d0e !important;
  font-weight: 700;
  border-top: 2px solid #ca8a04;
  border-bottom: 2px solid #ca8a04;
  white-space: nowrap;
}

/* Numeric alignment */
.cell-num {
  text-align: right;
  font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
  font-size: 11px;
  white-space: nowrap;
}

.cell-code {
  font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
  font-weight: 600;
  font-size: 11px;
  color: #0f172a;
  white-space: nowrap;
}

.cell-period-total {
  background: #dbeafe !important;
  font-weight: 700;
  color: #1e40af !important;
}

.cell-ceiling {
  background: #fef3c7 !important;
  font-weight: 700;
  color: #92400e !important;
}

.cell-desc {
  max-width: 380px;
  min-width: 280px;
  white-space: normal !important;
  word-break: break-word;
  line-height: 1.35;
}

/* Jump buttons */
.floating-jump-btns {
  position: fixed;
  bottom: 24px;
  right: 24px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  z-index: 1050;
}
.floating-jump-btns .btn {
  box-shadow: 0 4px 10px rgba(0,0,0,0.15);
  border-radius: 50px;
  padding: 8px 14px;
  font-size: 12px;
  font-weight: 600;
}
</style>

<!-- Floating Jump Controls -->
<div class="floating-jump-btns">
  <button type="button" class="btn btn-primary" onclick="scrollToTableTop()" title="Scroll ke baris pertama">
    <i class="bi bi-arrow-up-circle me-1"></i> Ke Atas
  </button>
  <button type="button" class="btn btn-dark" onclick="scrollToTableBottom()" title="Scroll ke baris total paling bawah">
    <i class="bi bi-arrow-down-circle me-1"></i> Ke Bawah
  </button>
</div>

<!-- Header & Actions -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <h2 class="h5 fw-bold mb-0 text-dark">RENCANA PENARIKAN ANGGARAN (RPA)</h2>
      <span class="badge bg-primary px-2 py-1">T.A. <?= esc($fy['year'] ?? date('Y')) ?></span>
      <span class="badge bg-warning text-dark border border-dark-subtle px-2 py-1">
        <?= $revision ? 'Revisi ' . esc($revision['revision_no']) : 'Belum Ada Revisi' ?>
      </span>
    </div>
    <div class="text-muted small mt-1">
      <?= $revision ? esc($revision['revision_name']) : 'Belum ada data RPA untuk T.A. ' . esc($fy['year'] ?? '') ?>
      <?php if ($revision && !empty($revision['revision_date'])): ?>
        · Per <?= date('d F Y', strtotime($revision['revision_date'])) ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="d-flex align-items-center gap-2 flex-wrap">
    <a href="<?= base_url('kegiatan/uraian?year=' . esc($fy['year'] ?? date('Y'))) ?>" class="btn-simran btn-sm border bg-white d-flex align-items-center gap-1 text-success fw-semibold" title="Buka 110 Rincian Operasional Kegiatan RKAKL">
      <i class="bi bi-card-checklist"></i>
      <span>Uraian Kegiatan (110 Giat)</span>
    </a>

    <a href="<?= base_url('admin/pagu?year=' . esc($fy['year'] ?? date('Y'))) ?>" class="btn-simran btn-sm border bg-white d-flex align-items-center gap-1 text-primary fw-semibold" title="Atur Pagu Induk DIPA">
      <i class="bi bi-bank"></i>
      <span>Pagu DIPA</span>
    </a>

    <div class="d-flex align-items-center gap-2">
      <span class="text-muted" style="font-size: 13px;">Tahun Anggaran:</span>
      <select class="form-select-simran" style="width: auto; font-weight: 600;" onchange="window.simranSwitchYear ? window.simranSwitchYear(this.value) : (location.href='<?= base_url('set-year') ?>/' + this.value)">
        <?php foreach ($allYears as $y): ?>
          <option value="<?= esc($y['year']) ?>" <?= (($fy['year'] ?? 0) == $y['year']) ? 'selected' : '' ?>>
            <?= esc($y['name']) ?> <?= $y['is_active'] ? '(Aktif)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="button" class="btn-simran btn-simran-primary d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#importRpaModal">
      <i class="bi bi-cloud-arrow-up-fill"></i>
      <span>Import PDF / Excel</span>
    </button>
  </div>
</div>

<!-- Modal Import PDF & Excel RPA -->
<div class="modal fade" id="importRpaModal" tabindex="-1" aria-labelledby="importRpaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card-simran p-0 border-0 shadow-lg">
      <div class="modal-header border-bottom p-3">
        <h5 class="modal-title h6 fw-bold mb-0 text-dark" id="importRpaModalLabel">
          <i class="bi bi-cloud-arrow-up-fill text-primary me-2"></i>Import Dokumen RPA (PDF / Excel)
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="<?= base_url('admin/rpa/import') ?>" enctype="multipart/form-data" id="importRpaForm">
        <?= csrf_field() ?>
        <div class="modal-body p-3">
          <div class="mb-3">
            <label for="modal_fiscal_year_id" class="form-label text-muted small fw-semibold">Tahun Anggaran Target</label>
            <select name="fiscal_year_id" id="modal_fiscal_year_id" class="form-select-simran w-100" required>
              <?php foreach ($allYears as $y): ?>
                <option value="<?= esc($y['id']) ?>" <?= ($fy['id'] == $y['id']) ? 'selected' : '' ?>>
                  <?= esc($y['name']) ?> <?= $y['is_active'] ? '(Aktif Default)' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="text-muted" style="font-size: 11.5px; margin-top: 4px;">
              Dokumen RPA yang diimpor akan otomatis disimpan pada tahun anggaran target yang dipilih.
            </div>
          </div>

          <div class="mb-3">
            <label for="modal_rpa_file" class="form-label text-muted small fw-semibold">Pilih Berkas File RPA</label>
            <input type="file" name="rpa_file" id="modal_rpa_file" accept=".pdf,.xlsx,.xls" class="form-control-simran w-100" required>
            <div class="text-muted" style="font-size: 11.5px; margin-top: 4px;">
              Format didukung: <strong>PDF (.pdf)</strong> Rencana Penarikan Anggaran resmi atau Excel <strong>(.xlsx, .xls)</strong>.
            </div>
          </div>

          <div class="p-2 px-3 rounded bg-light border text-muted" style="font-size: 12px;">
            <i class="bi bi-info-circle text-primary me-1"></i>
            Import dokumen baru akan membuat nomor revisi baru dan mengarsipkan revisi aktif sebelumnya untuk tahun anggaran tersebut.
          </div>
        </div>
        <div class="modal-footer border-top p-2 px-3 d-flex justify-content-end gap-2 bg-light">
          <button type="button" class="btn-simran" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn-simran btn-simran-primary" id="btnSubmitImport">
            <i class="bi bi-cloud-arrow-up-fill me-1"></i>
            <span>Unggah &amp; Proses Dokumen</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Executive Summary Cards -->
<div class="row g-2 mb-3">
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card-simran h-100 p-2 px-3 border-start border-4 border-warning mb-0">
      <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px;">Pagu Anggaran Satker (DIPA)</div>
      <div class="h5 fw-bold text-dark mb-0">Rp <?= number_format($sourceTotal, 0, ',', '.') ?></div>
      <div class="text-muted small" style="font-size: 11px;">Total pagu 100% T.A. <?= esc($fy['year'] ?? date('Y')) ?></div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card-simran h-100 p-2 px-3 border-start border-4 border-primary mb-0">
      <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px;">Rencana 4 Bulan Akhir (Sep-Des)</div>
      <div class="h5 fw-bold text-primary mb-0">Rp <?= number_format($grandPeriodTotal, 0, ',', '.') ?></div>
      <div class="text-muted small" style="font-size: 11px;"><?= number_format($percentPeriod, 1, ',', '.') ?>% dari seluruh pagu DIPA</div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card-simran h-100 p-2 px-3 border-start border-4 border-success mb-0">
      <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px;">Rencana September &amp; Oktober</div>
      <div class="d-flex justify-content-between align-items-center" style="font-size: 12px;">
        <span class="text-muted">September:</span>
        <span class="fw-bold font-monospace">Rp <?= number_format($officialTotals['months'][9] ?? 0, 0, ',', '.') ?></span>
      </div>
      <div class="d-flex justify-content-between align-items-center" style="font-size: 12px;">
        <span class="text-muted">Oktober:</span>
        <span class="fw-bold font-monospace">Rp <?= number_format($officialTotals['months'][10] ?? 0, 0, ',', '.') ?></span>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card-simran h-100 p-2 px-3 border-start border-4 border-info mb-0">
      <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px;">Rencana November &amp; Desember</div>
      <div class="d-flex justify-content-between align-items-center" style="font-size: 12px;">
        <span class="text-muted">November:</span>
        <span class="fw-bold font-monospace">Rp <?= number_format($officialTotals['months'][11] ?? 0, 0, ',', '.') ?></span>
      </div>
      <div class="d-flex justify-content-between align-items-center" style="font-size: 12px;">
        <span class="text-muted">Desember:</span>
        <span class="fw-bold font-monospace">Rp <?= number_format($officialTotals['months'][12] ?? 0, 0, ',', '.') ?></span>
      </div>
    </div>
  </div>
</div>

<!-- Toolbar: Filter Periode, Hierarki & Scroll Mode -->
<div class="card-simran p-2 px-3 mb-2">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-2">
    <!-- Period Buttons -->
    <?php
      $yearQuery = '&year=' . urlencode($fy['year'] ?? '');
      $searchQuery = $keyword ? '&q=' . urlencode($keyword) : '';
    ?>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="small fw-semibold text-muted text-uppercase" style="font-size: 11px;">Periode:</span>
      <div class="btn-group btn-group-sm flex-wrap" role="group">
        <a href="<?= base_url('admin/rpa?period=end4' . $yearQuery . $searchQuery) ?>"
           class="btn <?= $period === 'end4' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>">
          <i class="bi bi-calendar4-week me-1"></i> 4 Bulan Akhir (Sep - Des)
        </a>
        <a href="<?= base_url('admin/rpa?period=all' . $yearQuery . $searchQuery) ?>"
           class="btn <?= $period === 'all' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>">
          Semua (12 Bulan)
        </a>
        <a href="<?= base_url('admin/rpa?period=q1' . $yearQuery . $searchQuery) ?>"
           class="btn <?= $period === 'q1' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>">
          Triwulan I
        </a>
        <a href="<?= base_url('admin/rpa?period=q2' . $yearQuery . $searchQuery) ?>"
           class="btn <?= $period === 'q2' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>">
          Triwulan II
        </a>
        <a href="<?= base_url('admin/rpa?period=q3' . $yearQuery . $searchQuery) ?>"
           class="btn <?= $period === 'q3' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>">
          Triwulan III
        </a>
        <a href="<?= base_url('admin/rpa?period=q4' . $yearQuery . $searchQuery) ?>"
           class="btn <?= $period === 'q4' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>">
          Triwulan IV
        </a>
      </div>
    </div>

    <!-- Quick Level Filter (Program / Kegiatan / Akun) -->
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="small fw-semibold text-muted text-uppercase" style="font-size: 11px;">Tampilkan:</span>
      <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-outline-dark btn-sm active" id="filterBtnAll" onclick="filterLevel('all')">Semua</button>
        <button type="button" class="btn btn-outline-dark btn-sm" id="filterBtnProgram" onclick="filterLevel('program')">Program &amp; Kegiatan</button>
        <button type="button" class="btn btn-outline-dark btn-sm" id="filterBtnAkun" onclick="filterLevel('akun')">Sampai Akun</button>
      </div>

      <!-- Mode Scroll Toggle -->
      <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" id="toggleScrollModeBtn" onclick="toggleScrollMode()" title="Ganti antara mode jendela kotak atau scroll bebas ke bawah">
        <i class="bi bi-arrows-vertical"></i>
        <span id="scrollModeText">Scroll Bebas (Halaman Penuh)</span>
      </button>
    </div>
  </div>

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-2 pt-2 border-top">
    <div class="d-flex align-items-center gap-2 small text-muted">
      <span class="badge bg-secondary" id="visibleRowCountBadge"><?= count($items) ?> baris ditampilkan</span>
      <span>• Geser horizontal/vertikal untuk melihat rincian selengkapnya</span>
    </div>

    <!-- Live Search Filter -->
    <div class="input-group input-group-sm" style="max-width: 340px;">
      <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
      <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Saring kode akun, kegiatan, belanja..." value="<?= esc($keyword) ?>">
      <button type="button" class="btn btn-outline-secondary" onclick="clearSearch()" title="Hapus pencarian"><i class="bi bi-x"></i></button>
    </div>
  </div>
</div>

<!-- Spreadsheet Container -->
<div class="excel-table-container">
  <div class="excel-scroll-viewport" id="tableScrollContainer">
    <table class="table-excel" id="rpaTable">
      <thead>
        <!-- Header Kolom Utama -->
        <tr class="header-columns">
          <th style="width: 38px; min-width: 38px; max-width: 38px;">NO</th>
          <th style="width: 95px; min-width: 95px; max-width: 110px; text-align: left;">KODE</th>
          <th class="cell-desc" style="min-width: 280px; max-width: 380px; text-align: left;">RINCIAN / KEGIATAN</th>
          <th class="cell-ceiling" style="width: 105px; min-width: 105px;">PAGU</th>
          <?php foreach ($selectedMonths as $m): ?>
            <th style="width: 82px; min-width: 82px;" title="<?= esc($allMonthNames[$m]) ?>">
              <?= esc($shortMonthNames[$m] ?? $allMonthNames[$m]) ?>
            </th>
          <?php endforeach; ?>
          <th class="cell-period-total" style="width: 110px; min-width: 110px;">
            <?= count($selectedMonths) == 12 ? 'JUMLAH' : (count($selectedMonths) == 4 ? 'JML 4 BLN' : 'JUMLAH') ?>
          </th>
        </tr>

        <!-- Grand Total Summary Row (Sticky menyatu di bawah header) -->
        <tr class="excel-grand-total" id="rowGrandTotalTop">
          <th class="text-center" style="font-size: 11px;">★</th>
          <th class="cell-code text-center">-</th>
          <th style="text-align: left; font-size: 11px;">
            <strong class="text-uppercase"><i class="bi bi-award-fill me-1"></i> JUMLAH KESELURUHAN DIPA T.A. <?= esc($fy['year'] ?? date('Y')) ?></strong>
          </th>
          <th class="cell-num">
            Rp <?= number_format($sourceTotal, 0, ',', '.') ?>
          </th>
          <?php foreach ($selectedMonths as $m): ?>
            <th class="cell-num">
              <?= number_format($officialTotals['months'][$m] ?? 0, 0, ',', '.') ?>
            </th>
          <?php endforeach; ?>
          <th class="cell-num cell-period-total">
            Rp <?= number_format($grandPeriodTotal, 0, ',', '.') ?>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr>
            <td colspan="<?= count($selectedMonths) + 5 ?>" class="text-center text-muted py-5 bg-white">
              <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
              <div class="fw-bold text-dark fs-6 mb-1">Belum Ada Data RPA untuk T.A. <?= esc($fy['year'] ?? '') ?></div>
              <div class="small text-muted mb-3">Silakan unggah dokumen PDF atau berkas Excel Rencana Penarikan Anggaran untuk tahun ini.</div>
              <button type="button" class="btn-simran btn-simran-primary py-2 px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#importRpaModal">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Unggah Dokumen RPA T.A. <?= esc($fy['year'] ?? '') ?>
              </button>
            </td>
          </tr>
        <?php else: ?>
          <?php
            $no = 1;
            foreach ($items as $item):
              $code = trim((string)$item['activity_code']);
              $name = trim((string)$item['activity_name']);
              $ceiling = (float)$item['ceiling'];

              // Calculate period sum for this row
              $rowPeriodSum = 0.0;
              foreach ($selectedMonths as $m) {
                $rowPeriodSum += (float)$item['month_' . $m];
              }

              // Row hierarchy detection
              $isProgram = str_starts_with($code, '060.01.');
              $isKegiatan = preg_match('/^\d{4}$/', $code);
              $isAkun = preg_match('/^\d{6}$/', $code);
              $isSubHeader = str_starts_with($name, '>') || str_starts_with($name, '>>');
              $isLeafItem = str_starts_with($name, '-');

              // Determine level class for filtering and styling
              $rowClass = 'row-leaf';
              $levelType = 'leaf';
              $indentStyle = 'padding-left: 32px;';

              if ($isProgram) {
                $rowClass = 'row-program';
                $levelType = 'program';
                $indentStyle = 'padding-left: 8px;';
              } elseif ($isKegiatan) {
                $rowClass = 'row-kegiatan';
                $levelType = 'kegiatan';
                $indentStyle = 'padding-left: 14px;';
              } elseif ($isAkun) {
                $rowClass = 'row-akun';
                $levelType = 'akun';
                $indentStyle = 'padding-left: 20px;';
              } elseif ($isSubHeader) {
                $rowClass = 'row-sublevel';
                $levelType = 'sublevel';
                $indentStyle = str_starts_with($name, '>>') ? 'padding-left: 28px;' : 'padding-left: 22px;';
              }
          ?>
            <tr class="<?= $rowClass ?> rpa-row"
                data-level="<?= $levelType ?>"
                data-search="<?= strtolower(esc($code . ' ' . $name)) ?>">
              <td class="text-center text-muted" style="font-size: 11px; width: 38px;"><?= $no++ ?></td>
              <td class="cell-code" style="width: 95px;">
                <?php if ($isProgram): ?>
                  <span class="badge bg-warning text-dark border border-dark-subtle"><?= esc($code) ?></span>
                <?php elseif ($isKegiatan): ?>
                  <span class="badge bg-info text-dark"><?= esc($code) ?></span>
                <?php elseif ($isAkun): ?>
                  <span class="badge bg-dark-subtle text-dark"><?= esc($code) ?></span>
                <?php elseif (!empty($code)): ?>
                  <?= esc($code) ?>
                <?php endif; ?>
              </td>
              <td class="cell-desc" style="<?= $indentStyle ?>" title="<?= esc($name) ?>">
                <?php if ($isProgram || $isKegiatan): ?>
                  <strong><?= esc($name) ?></strong>
                <?php elseif ($isAkun): ?>
                  <span class="fw-semibold"><?= esc($name) ?></span>
                <?php elseif ($isSubHeader): ?>
                  <span class="text-primary fw-medium"><?= esc($name) ?></span>
                <?php else: ?>
                  <?= esc($name) ?>
                <?php endif; ?>
              </td>
              <td class="cell-num cell-ceiling">
                <?= $ceiling > 0 ? number_format($ceiling, 0, ',', '.') : '<span class="text-muted opacity-50">-</span>' ?>
              </td>
              <?php foreach ($selectedMonths as $m): $val = (float)$item['month_' . $m]; ?>
                <td class="cell-num">
                  <?= $val > 0 ? number_format($val, 0, ',', '.') : '<span class="text-muted opacity-50">-</span>' ?>
                </td>
              <?php endforeach; ?>
              <td class="cell-num cell-period-total">
                <?= $rowPeriodSum > 0 ? number_format($rowPeriodSum, 0, ',', '.') : '<span class="text-muted opacity-50">-</span>' ?>
              </td>
            </tr>
          <?php endforeach; ?>

          <!-- Bottom Footer Row -->
          <tr class="excel-grand-total-bottom" id="rowGrandTotalBottom">
            <td class="text-center fw-bold">★</td>
            <td class="cell-code text-center">-</td>
            <td>
              <strong class="text-uppercase"><i class="bi bi-check-all me-1"></i> TOTAL AKUMULASI DIPA</strong>
            </td>
            <td class="cell-num">
              Rp <?= number_format($sourceTotal, 0, ',', '.') ?>
            </td>
            <?php foreach ($selectedMonths as $m): ?>
              <td class="cell-num">
                <?= number_format($officialTotals['months'][$m] ?? 0, 0, ',', '.') ?>
              </td>
            <?php endforeach; ?>
            <td class="cell-num cell-period-total">
              Rp <?= number_format($grandPeriodTotal, 0, ',', '.') ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
let currentLevel = 'all';
let isFullPageScroll = false;

function scrollToTableTop() {
  const container = document.getElementById('tableScrollContainer');
  if (isFullPageScroll) {
    window.scrollTo({ top: document.querySelector('.excel-table-container').offsetTop - 70, behavior: 'smooth' });
  } else {
    container.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

function scrollToTableBottom() {
  const container = document.getElementById('tableScrollContainer');
  if (isFullPageScroll) {
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
  } else {
    container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
  }
}

function toggleScrollMode() {
  const container = document.getElementById('tableScrollContainer');
  const textSpan = document.getElementById('scrollModeText');

  isFullPageScroll = !isFullPageScroll;

  if (isFullPageScroll) {
    container.classList.add('excel-scroll-fullpage');
    textSpan.innerText = 'Mode Jendela Spreadsheet';
    // Di fullpage scroll mode, sticky header melekat di bawah navbar
    document.querySelectorAll('.table-excel thead tr.header-columns th').forEach(th => {
      th.style.top = '65px';
    });
    document.querySelectorAll('.table-excel thead tr.excel-grand-total th').forEach(th => {
      th.style.top = '95px';
    });
  } else {
    container.classList.remove('excel-scroll-fullpage');
    textSpan.innerText = 'Scroll Bebas (Halaman Penuh)';
    document.querySelectorAll('.table-excel thead tr.header-columns th').forEach(th => {
      th.style.top = '0px';
    });
    document.querySelectorAll('.table-excel thead tr.excel-grand-total th').forEach(th => {
      th.style.top = '30px';
    });
  }
}

function filterLevel(level) {
  currentLevel = level;
  document.getElementById('filterBtnAll').classList.toggle('active', level === 'all');
  document.getElementById('filterBtnProgram').classList.toggle('active', level === 'program');
  document.getElementById('filterBtnAkun').classList.toggle('active', level === 'akun');

  applyFilters();
}

function clearSearch() {
  const searchInput = document.getElementById('tableSearch');
  searchInput.value = '';
  applyFilters();
}

function applyFilters() {
  const query = document.getElementById('tableSearch').value.toLowerCase().trim();
  const rows = document.querySelectorAll('.rpa-row');
  let visibleCount = 0;

  rows.forEach(row => {
    const level = row.getAttribute('data-level');
    const text = row.getAttribute('data-search') || '';

    // Hierarchy filter check
    let levelMatch = true;
    if (currentLevel === 'program') {
      levelMatch = (level === 'program' || level === 'kegiatan');
    } else if (currentLevel === 'akun') {
      levelMatch = (level === 'program' || level === 'kegiatan' || level === 'akun');
    }

    // Search query check
    const searchMatch = (query === '' || text.includes(query));

    if (levelMatch && searchMatch) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });

  const badge = document.getElementById('visibleRowCountBadge');
  if (badge) {
    badge.innerText = visibleCount.toLocaleString('id-ID') + ' baris ditampilkan';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('tableSearch');
  if (searchInput) {
    searchInput.addEventListener('input', applyFilters);
  }

  // Loading spinner on import submit
  const importForm = document.getElementById('importRpaForm');
  const submitBtn = document.getElementById('btnSubmitImport');
  if (importForm && submitBtn) {
    importForm.addEventListener('submit', function() {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses PDF/Excel...';
    });
  }
});
</script>

<?= $this->endSection() ?>
