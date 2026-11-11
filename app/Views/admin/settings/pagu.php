<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Header & Year Selector -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="h5 fw-bold mb-1">🏛️ Input &amp; Kelola Pagu DIPA Induk</h2>
    <div class="text-muted" style="font-size: 13px;">
      Penetapan total pagu DIPA per jenis belanja (Belanja Pegawai, Belanja Barang, Belanja Modal) dan target RPD triwulan
    </div>
  </div>
  <div class="d-flex align-items-center gap-2">
    <span class="text-muted" style="font-size: 13px;">Tahun Anggaran:</span>
    <select class="form-select-simran" style="width: auto; font-weight: 600;" onchange="window.simranSwitchYear ? window.simranSwitchYear(this.value) : (location.href='<?= base_url('set-year') ?>/' + this.value)">
      <?php foreach ($all_years as $fy): ?>
        <option value="<?= $fy['year'] ?>" <?= ($active_year['year'] == $fy['year']) ? 'selected' : '' ?>>
          <?= esc($fy['name']) ?> <?= $fy['is_active'] ? '(Aktif)' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<?php 
  $totalPagu = array_sum($pagus);
?>

<!-- KPI Stat Cards for Pagu Breakdown -->
<div class="row g-3 mb-4">
  <?php foreach ($budget_types as $bt): 
    $pVal = $pagus[$bt['id']] ?? 0;
    $pct = $totalPagu > 0 ? ($pVal / $totalPagu) * 100 : 0;
    $icon = $bt['code'] == '51' ? '👔' : ($bt['code'] == '52' ? '📦' : '🏢');
  ?>
    <div class="col-md-3">
      <div class="card-simran p-3 h-100 border-start border-4" style="border-left-color: <?= esc($bt['color'] ?? '#2756b5') ?> !important;">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span class="text-muted" style="font-size: 12px; font-weight: 600;">
            <?= $icon ?> <?= esc($bt['name']) ?> (<?= esc($bt['code']) ?>)
          </span>
          <span class="badge bg-light text-dark border" style="font-size: 11px;">
            <?= number_format($pct, 1, ',', '.') ?>%
          </span>
        </div>
        <div class="h5 fw-bold mb-0 text-truncate" title="Rp <?= number_format($pVal, 0, ',', '.') ?>">
          Rp <?= number_format($pVal / 1e9, 2, ',', '.') ?> M
        </div>
        <div class="text-muted text-truncate mt-1" style="font-size: 12px;">
          Rp <?= number_format($pVal, 0, ',', '.') ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="col-md-3">
    <div class="card-simran p-3 h-100 bg-primary text-white border-0 shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="text-white-50" style="font-size: 12px; font-weight: 600;">
          🏛️ TOTAL PAGU DIPA
        </span>
        <span class="badge bg-white text-primary" style="font-size: 11px;">
          100%
        </span>
      </div>
      <div class="h5 fw-bold mb-0 text-white" id="stat-total-pagu-m">
        Rp <?= number_format($totalPagu / 1e9, 2, ',', '.') ?> M
      </div>
      <div class="text-white-50 text-truncate mt-1" style="font-size: 12px;" id="stat-total-pagu-rp">
        Rp <?= number_format($totalPagu, 0, ',', '.') ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <!-- Form Input Pagu DIPA -->
    <div class="card-simran border-primary border-2 shadow-sm mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="h5 fw-bold mb-1 text-primary">✏️ Input Pagu DIPA per Jenis Belanja</h3>
          <div class="text-muted" style="font-size: 13px;">
            Masukkan angka nominal rupiah penuh untuk Belanja Pegawai, Barang, dan Modal T.A. <?= esc($active_year['year']) ?>
          </div>
        </div>
      </div>

      <form method="post" action="<?= base_url('admin/settings/save-pagu-dipa') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="fiscal_year_id" value="<?= esc($active_year['id']) ?>">

        <div class="d-flex flex-column gap-3 mb-4">
          <?php foreach ($budget_types as $bt): 
            $val = $pagus[$bt['id']] ?? 0;
            $icon = $bt['code'] == '51' ? '👔' : ($bt['code'] == '52' ? '📦' : '🏢');
          ?>
            <div class="p-3 rounded border bg-light bg-opacity-50">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label fw-bold mb-0" for="pagu-input-<?= $bt['id'] ?>" style="font-size: 13.5px;">
                  <?= $icon ?> Pagu <?= esc($bt['name']) ?> (Akun <?= esc($bt['code']) ?>)
                </label>
                <span class="badge bg-secondary" id="hint-m-<?= $bt['id'] ?>">
                  <?= number_format($val / 1e9, 2, ',', '.') ?> Miliar
                </span>
              </div>
              <div class="input-group input-group-lg mt-2">
                <span class="input-group-text bg-light text-muted fw-bold">Rp</span>
                <input type="number" 
                       class="form-control-simran pagu-input-field text-end fw-bold fs-5" 
                       name="pagu[<?= $bt['id'] ?>]" 
                       id="pagu-input-<?= $bt['id'] ?>" 
                       value="<?= $val ?>" 
                       data-bt-id="<?= $bt['id'] ?>"
                       step="1" 
                       required>
              </div>
              <div class="text-muted mt-1" style="font-size: 12px;" id="hint-text-<?= $bt['id'] ?>">
                Terbaca: Rp <?= number_format($val, 0, ',', '.') ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="p-3 bg-light rounded border mb-4">
          <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold" style="font-size: 14px;">Total Akumulasi Pagu DIPA:</span>
            <div class="text-end">
              <div class="h5 fw-bold text-primary mb-0" id="live-total-rp">
                Rp <?= number_format($totalPagu, 0, ',', '.') ?>
              </div>
              <div class="text-muted" style="font-size: 12.5px;" id="live-total-m">
                (<?= number_format($totalPagu / 1e9, 2, ',', '.') ?> Miliar)
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
          <a href="<?= base_url('admin/realisasi') ?>" class="btn-simran">
            ← Menuju Input Realisasi
          </a>
          <button type="submit" class="btn-simran btn-simran-primary px-4 py-2 fs-6">
            💾 Simpan Perubahan Pagu DIPA
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-5">
    <!-- Target RPD Triwulan -->
    <div class="card-simran mb-4">
      <div class="mb-3">
        <h3 class="h5 fw-bold mb-1">🎯 Target Penyerapan Triwulan (%)</h3>
        <div class="text-muted" style="font-size: 13px;">
          Persentase minimal penyerapan kumulatif (RPD Kemenkeu)
        </div>
      </div>

      <form method="post" action="<?= base_url('admin/settings/save-targets') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="fiscal_year_id" value="<?= esc($active_year['id']) ?>">

        <div class="table-responsive mb-3">
          <table class="table-simran align-middle">
            <thead>
              <tr>
                <th>Jenis Belanja</th>
                <th class="text-center" style="width: 65px;">TW I</th>
                <th class="text-center" style="width: 65px;">TW II</th>
                <th class="text-center" style="width: 65px;">TW III</th>
                <th class="text-center" style="width: 65px;">TW IV</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($budget_types as $bt): 
                $t = $targets[$bt['id']] ?? ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
              ?>
                <tr>
                  <td><b><?= esc($bt['name']) ?></b></td>
                  <td>
                    <input type="number" class="form-control-simran text-center p-1" name="targets[<?= $bt['id'] ?>][q1]" value="<?= $t['q1'] ?>" min="0" max="100" step="0.1" required>
                  </td>
                  <td>
                    <input type="number" class="form-control-simran text-center p-1" name="targets[<?= $bt['id'] ?>][q2]" value="<?= $t['q2'] ?>" min="0" max="100" step="0.1" required>
                  </td>
                  <td>
                    <input type="number" class="form-control-simran text-center p-1" name="targets[<?= $bt['id'] ?>][q3]" value="<?= $t['q3'] ?>" min="0" max="100" step="0.1" required>
                  </td>
                  <td>
                    <input type="number" class="form-control-simran text-center p-1" name="targets[<?= $bt['id'] ?>][q4]" value="<?= $t['q4'] ?>" min="0" max="100" step="0.1" required>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="alert alert-secondary p-2 mb-3" style="font-size: 11.5px;">
          Standar Target Nasional: Pegawai (20 / 50 / 75 / 95%), Barang (15 / 40 / 70 / 90%), Modal (10 / 40 / 70 / 90%).
        </div>

        <div class="text-end">
          <button type="submit" class="btn-simran btn-simran-primary w-100">
            💾 Simpan Target Triwulan
          </button>
        </div>
      </form>
    </div>

    <!-- Quick Navigation Card -->
    <div class="card-simran">
      <h3 class="h6 fw-bold mb-2">📌 Akses Cepat Terkait (T.A. <?= esc($active_year['year']) ?>)</h3>
      <div class="list-group list-group-flush" style="font-size: 13px;">
        <a href="<?= base_url('?year=' . esc($active_year['year'])) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2">
          <span>🖥️ Lihat di Dashboard Pimpinan</span>
          <span>→</span>
        </a>
        <a href="<?= base_url('admin/rpa?year=' . esc($active_year['year'])) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2 text-primary fw-semibold">
          <span>📑 Rencana Penarikan Anggaran (RPA)</span>
          <span>→</span>
        </a>
        <a href="<?= base_url('kegiatan/uraian?year=' . esc($active_year['year'])) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2 text-success fw-semibold">
          <span>📋 Tabel Uraian Kegiatan (110 Giat)</span>
          <span>→</span>
        </a>
        <a href="<?= base_url('kegiatan?year=' . esc($active_year['year'])) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2">
          <span>🔍 Rincian Akun &amp; Realisasi</span>
          <span>→</span>
        </a>
        <a href="<?= base_url('admin/realisasi?year=' . esc($active_year['year'])) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2">
          <span>📊 Input Realisasi Bulanan</span>
          <span>→</span>
        </a>
        <a href="<?= base_url('admin/import?year=' . esc($active_year['year'])) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2">
          <span>📥 Import File LRA Excel</span>
          <span>→</span>
        </a>
        <a href="<?= base_url('admin/ikpa?year=' . esc($active_year['year'])) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2">
          <span>🎯 Kelola Indikator Nilai IKPA</span>
          <span>→</span>
        </a>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const inputs = document.querySelectorAll('.pagu-input-field');
  const liveTotalRp = document.getElementById('live-total-rp');
  const liveTotalM = document.getElementById('live-total-m');

  function updateLivePagu() {
    let total = 0;
    inputs.forEach(inp => {
      const btId = inp.dataset.btId;
      const val = parseFloat(inp.value) || 0;
      total += val;

      const hintM = document.getElementById('hint-m-' + btId);
      const hintText = document.getElementById('hint-text-' + btId);

      if (hintM) {
        hintM.textContent = (val / 1e9).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' Miliar';
      }
      if (hintText) {
        hintText.textContent = 'Terbaca: Rp ' + val.toLocaleString('id-ID');
      }
    });

    if (liveTotalRp) {
      liveTotalRp.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }
    if (liveTotalM) {
      liveTotalM.textContent = '(' + (total / 1e9).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' Miliar)';
    }
  }

  inputs.forEach(inp => {
    inp.addEventListener('input', updateLivePagu);
  });
});
</script>
<?= $this->endSection() ?>
