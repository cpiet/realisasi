<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Ringkasan Pagu DIPA Aktif -->
<?php if (!empty($pagus)): 
  $totPagu = array_sum($pagus);
?>
  <div class="card-simran mb-3 p-3 bg-light bg-opacity-75 border">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
      <div class="d-flex align-items-center gap-2">
        <span class="fs-5">🏛️</span>
        <div>
          <h3 class="h6 fw-bold mb-0">Pagu DIPA T.A. <?= esc($selected_year['year']) ?></h3>
          <span class="text-muted" style="font-size: 12px;">Plafon anggaran dasar per jenis belanja</span>
        </div>
      </div>
      <a href="<?= base_url('admin/pagu') ?>?year=<?= esc($selected_year['year']) ?>" class="btn-simran btn-simran-primary btn-sm">
        ✏️ Edit Pagu DIPA &amp; Target RPD
      </a>
    </div>

    <div class="row g-2 pt-1">
      <?php foreach ($budget_types as $bt): 
        $p = $pagus[$bt['id']] ?? 0;
        $icon = $bt['code'] == '51' ? '👔' : ($bt['code'] == '52' ? '📦' : '🏢');
      ?>
        <div class="col-6 col-md-3">
          <div class="bg-white p-2 rounded border">
            <div class="text-muted" style="font-size: 11.5px;"><?= $icon ?> <?= esc($bt['name']) ?></div>
            <div class="fw-bold fs-6">Rp <?= number_format($p / 1e9, 2, ',', '.') ?> M</div>
            <div class="text-muted text-truncate" style="font-size: 11px;">Rp <?= number_format($p, 0, ',', '.') ?></div>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="col-6 col-md-3">
        <div class="bg-primary text-white p-2 rounded">
          <div class="text-white-50" style="font-size: 11.5px;">🏛️ Total Pagu DIPA</div>
          <div class="fw-bold fs-6 text-white">Rp <?= number_format($totPagu / 1e9, 2, ',', '.') ?> M</div>
          <div class="text-white-50 text-truncate" style="font-size: 11px;">Rp <?= number_format($totPagu, 0, ',', '.') ?></div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="card-simran">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h2 class="h5 fw-bold mb-1">Input Cepat Realisasi Bulanan</h2>
      <div class="text-muted" style="font-size: 13px;">
        T.A. <?= esc($selected_year['year']) ?> · Nilai realisasi per jenis belanja berdasarkan SP2D / SAKTI
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="<?= base_url('admin/pagu') ?>?year=<?= esc($selected_year['year']) ?>" class="btn-simran btn-sm" title="Atur Pagu DIPA Belanja Pegawai, Barang, Modal">
        🏛️ Atur Pagu DIPA
      </a>
      <div class="save-indicator ms-2" id="save-indicator">
        <span class="dot"></span> tersimpan di database
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table-simran align-middle">
      <thead>
        <tr>
          <th style="width: 100px;">Bulan</th>
          <?php foreach ($grid as $btId => $item): ?>
            <th class="text-end" style="min-width: 180px;">
              <?= esc($item['type']['name']) ?>
            </th>
          <?php endforeach; ?>
          <th class="text-end" style="width: 180px;">Total Bulan (Rp)</th>
        </tr>
      </thead>
      <tbody>
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <tr>
            <td class="fw-semibold text-muted"><?= $months[$m - 1] ?></td>
            <?php foreach ($grid as $btId => $item): ?>
              <td>
                <input type="number" 
                       class="form-control-simran text-end real-input" 
                       data-fy-id="<?= $selected_year['id'] ?>" 
                       data-bt-id="<?= $btId ?>" 
                       data-month="<?= $m ?>" 
                       value="<?= $item['months'][$m] > 0 ? $item['months'][$m] : '' ?>" 
                       placeholder="0" 
                       step="1">
              </td>
            <?php endforeach; ?>
            <td class="text-end fw-bold text-muted" id="rowtot-<?= $m ?>">—</td>
          </tr>
        <?php endfor; ?>
      </tbody>
      <tfoot>
        <tr class="fw-bold border-top-2">
          <td>TOTAL</td>
          <?php foreach ($grid as $btId => $item): ?>
            <td class="text-end text-primary" id="coltot-<?= $btId ?>">Rp 0</td>
          <?php endforeach; ?>
          <td class="text-end text-success fs-6" id="grandtot">Rp 0</td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="alert alert-info d-flex align-items-center gap-2 mt-3 mb-0" style="font-size: 12.5px;">
    <span>💡</span>
    <span>
      Perubahan nilai pada input di atas langsung disimpan otomatis ke basis data SQLite tanpa perlu menekan tombol simpan atau memuat ulang halaman.
    </span>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/simran-realisasi.js?v=' . (@filemtime(FCPATH . 'js/simran-realisasi.js') ?: '1.2')) ?>"></script>
<?= $this->endSection() ?>
