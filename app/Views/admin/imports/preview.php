<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="card-simran">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h2 class="h5 fw-bold mb-1">Pratinjau &amp; Validasi Data Import</h2>
      <div class="text-muted" style="font-size: 13px;">
        File: <b><?= esc($file_name) ?></b> · Tanggal Posisi: <b><?= esc($position_date) ?></b> · T.A. <b><?= esc($preview['fiscal_year']) ?></b>
      </div>
    </div>
    <div class="d-flex gap-2">
      <form method="post" action="<?= base_url('admin/import/commit') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn-simran btn-simran-primary py-2 px-3" <?= $preview['valid_rows'] === 0 ? 'disabled' : '' ?>>
          ✅ Konfirmasi &amp; Simpan ke Database
        </button>
      </form>
      <a href="<?= base_url('admin/import') ?>" class="btn-simran py-2">
        ❌ Batalkan
      </a>
    </div>
  </div>

  <?php if (!empty($preview['import_notes'])): ?>
    <div class="alert alert-warning py-2 px-3 mb-3" style="font-size: 12.5px;">
      <div class="fw-semibold mb-1">Catatan kontrol workbook:</div>
      <ul class="mb-0 ps-3">
        <?php foreach ($preview['import_notes'] as $note): ?>
          <li><?= esc($note) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- Summary Badges -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
      <div class="p-3 bg-light rounded border text-center">
        <div class="text-muted" style="font-size: 12px;">TOTAL BARIS DATA</div>
        <div class="h4 mb-0 fw-bold"><?= $preview['total_rows'] ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="p-3 bg-success-subtle rounded border border-success-subtle text-center">
        <div class="text-success fw-medium" style="font-size: 12px;">VALID UNTUK IMPORT</div>
        <div class="h4 mb-0 fw-bold text-success"><?= $preview['valid_rows'] ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="p-3 bg-warning-subtle rounded border border-warning-subtle text-center">
        <div class="text-warning-emphasis fw-medium" style="font-size: 12px;">PERINGATAN (WARNING)</div>
        <div class="h4 mb-0 fw-bold text-warning-emphasis"><?= $preview['warning_rows'] ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="p-3 bg-danger-subtle rounded border border-danger-subtle text-center">
        <div class="text-danger fw-medium" style="font-size: 12px;">ERROR (DILEWATI)</div>
        <div class="h4 mb-0 fw-bold text-danger"><?= $preview['error_rows'] ?></div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card-simran mb-0 bg-light-subtle">
        <div class="text-muted" style="font-size: 12px;">TOTAL PAGU TERDETEKSI</div>
        <div class="h4 mb-0 fw-bold text-primary">Rp <?= number_format($preview['total_pagu'], 0, ',', '.') ?></div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card-simran mb-0 bg-light-subtle">
        <div class="text-muted" style="font-size: 12px;">TOTAL REALISASI TERDETEKSI</div>
        <div class="h4 mb-0 fw-bold text-success">Rp <?= number_format($preview['total_realisasi'], 0, ',', '.') ?></div>
      </div>
    </div>
  </div>

  <!-- Rows Table -->
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h3 class="h6 fw-bold mb-0">Rincian Baris Terdeteksi (Menampilkan 50 Baris Pertama)</h3>
    <small class="text-muted">Total: <?= count($preview['all_rows']) ?> baris</small>
  </div>

  <div class="table-responsive" style="max-height: 480px;">
    <table class="table-simran table-bordered">
      <thead>
        <tr>
          <th style="width: 50px;">No</th>
          <th>Kode Akun / Level</th>
          <th>Uraian Kegiatan / Akun</th>
          <th class="text-end">Pagu (Rp)</th>
          <th class="text-end">Realisasi (Rp)</th>
          <th class="text-center" style="width: 100px;">Status</th>
          <th>Catatan Validasi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($preview['rows_sample'] as $row): ?>
          <tr>
            <td class="text-muted"><?= $row['row_index'] ?></td>
            <td>
              <span class="badge bg-light text-dark border font-monospace"><?= esc($row['code'] ?: '—') ?></span>
              <span class="badge bg-secondary-subtle text-secondary ms-1"><?= esc($row['level']) ?></span>
            </td>
            <td><?= esc($row['description']) ?></td>
            <td class="text-end fw-medium"><?= number_format($row['pagu'], 0, ',', '.') ?></td>
            <td class="text-end fw-medium text-primary"><?= number_format($row['realisasi'], 0, ',', '.') ?></td>
            <td class="text-center">
              <?php if ($row['status'] === 'valid'): ?>
                <span class="pill ok">Valid</span>
              <?php elseif ($row['status'] === 'warning'): ?>
                <span class="pill warn">Warning</span>
              <?php else: ?>
                <span class="pill bad">Error</span>
              <?php endif; ?>
            </td>
            <td class="text-muted" style="font-size: 11.5px;">
              <?= esc($row['error_message'] ?: 'Siap di-commit ke database') ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>
