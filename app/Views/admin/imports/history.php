<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="card-simran">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h2 class="h5 fw-bold mb-1">Riwayat Batch Import LRA</h2>
      <div class="text-muted" style="font-size: 13px;">Log rekam jejak berkas Excel yang telah diimpor ke dalam sistem</div>
    </div>
    <div>
      <a href="<?= base_url('admin/import') ?>" class="btn-simran btn-simran-primary">
        ➕ Import Berkas Baru
      </a>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table-simran align-middle">
      <thead>
        <tr>
          <th>ID Batch</th>
          <th>Nama File</th>
          <th>Tahun Anggaran</th>
          <th>Tanggal Posisi</th>
          <th>Total Baris</th>
          <th>Valid / Error</th>
          <th>Status</th>
          <th>Waktu Import / Operator</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($batches)): ?>
          <tr>
            <td colspan="8" class="text-center py-4 text-muted">
              Belum ada riwayat batch import file Excel.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($batches as $b): ?>
            <tr>
              <td><span class="badge bg-secondary">#<?= $b['id'] ?></span></td>
              <td class="fw-semibold"><?= esc($b['filename']) ?></td>
              <td>T.A. <?= esc($b['fiscal_year'] ?? '—') ?></td>
              <td><?= esc($b['position_date']) ?></td>
              <td><?= $b['total_rows'] ?></td>
              <td>
                <span class="text-success fw-medium"><?= $b['valid_rows'] ?></span> / 
                <span class="text-danger fw-medium"><?= $b['error_rows'] ?></span>
              </td>
              <td>
                <span class="pill ok"><?= esc($b['status']) ?></span>
              </td>
              <td class="text-muted" style="font-size: 12px;">
                <?= esc($b['created_at']) ?> · <b><?= esc($b['imported_by']) ?></b>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>
