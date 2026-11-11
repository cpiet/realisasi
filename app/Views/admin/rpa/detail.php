<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
  $names = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
  ];
  $displayMonths = [9, 10, 11, 12];
  $sum = fn($key) => array_sum(array_map(fn($row) => (float)$row[$key], $details));
?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-3">
  <div>
    <a href="<?= base_url('admin/rpa' . (!empty($selectedYear['year']) ? '?year=' . $selectedYear['year'] : '')) ?>" class="text-decoration-none">← Kembali ke RPA (T.A. <?= esc($selectedYear['year'] ?? '') ?>)</a>
    <h2 class="h4 fw-bold mt-2 mb-1"><?= esc($item['activity_code'] ?: '-') ?> — <?= esc($item['activity_name']) ?></h2>
    <div class="text-muted">Revisi <?= esc($revision['revision_no'] ?? '-') ?> · Tampilan 4 bulan akhir (September - Desember)</div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-2">
    <div class="card-simran p-3">
      <div class="text-muted small">Total Pagu</div>
      <div class="h6 fw-bold mb-0 text-primary">Rp <?= number_format($item['ceiling'], 0, ',', '.') ?></div>
    </div>
  </div>
  <?php foreach ($displayMonths as $m): ?>
    <div class="col-md-2">
      <div class="card-simran p-3">
        <div class="text-muted small"><?= $names[$m] ?></div>
        <div class="h6 fw-bold mb-0">Rp <?= number_format((float)($item['month_' . $m] ?? $sum('month_' . $m)), 0, ',', '.') ?></div>
      </div>
    </div>
  <?php endforeach; ?>
  <div class="col-md-2">
    <div class="card-simran p-3 bg-light">
      <div class="text-muted small">Total 4 Bulan</div>
      <?php
        $fourTot = 0;
        foreach ($displayMonths as $m) $fourTot += (float)($item['month_' . $m] ?? $sum('month_' . $m));
      ?>
      <div class="h6 fw-bold mb-0 text-success">Rp <?= number_format($fourTot, 0, ',', '.') ?></div>
    </div>
  </div>
</div>

<div class="card-simran p-3">
  <h3 class="h6 fw-bold mb-3">Rincian Anggaran</h3>
  <?php if (empty($details)): ?>
    <div class="text-muted small py-3">Tidak ada rincian sub-komponen terpisah untuk kegiatan ini. Rencana penarikan bulanan tercantum pada ringkasan di atas.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle text-nowrap mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-center" style="width: 40px;">No</th>
            <th>Rincian</th>
            <th>Satuan</th>
            <th class="text-end">Harga</th>
            <th class="text-end">Jumlah</th>
            <th class="text-end">Total</th>
            <?php foreach ($displayMonths as $m): ?>
              <th class="text-end"><?= $names[$m] ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($details as $i => $detail): ?>
            <tr>
              <td class="text-center text-muted"><?= $i + 1 ?></td>
              <td><?= esc($detail['detail_name']) ?></td>
              <td><?= esc($detail['unit_name'] ?: '-') ?></td>
              <td class="text-end font-monospace">Rp <?= number_format($detail['unit_price'], 0, ',', '.') ?></td>
              <td class="text-end font-monospace"><?= number_format($detail['quantity'], 2, ',', '.') ?></td>
              <td class="text-end font-monospace">Rp <?= number_format($detail['total_amount'], 0, ',', '.') ?></td>
              <?php foreach ($displayMonths as $m): ?>
                <td class="text-end font-monospace"><?= (float)$detail['month_' . $m] > 0 ? 'Rp ' . number_format($detail['month_' . $m], 0, ',', '.') : '-' ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
          <tr class="fw-bold table-secondary">
            <td colspan="5">TOTAL</td>
            <td class="text-end font-monospace">Rp <?= number_format(array_sum(array_map(fn($r) => (float)$r['total_amount'], $details)), 0, ',', '.') ?></td>
            <?php foreach ($displayMonths as $m): ?>
              <td class="text-end font-monospace">Rp <?= number_format($sum('month_' . $m), 0, ',', '.') ?></td>
            <?php endforeach; ?>
          </tr>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>
