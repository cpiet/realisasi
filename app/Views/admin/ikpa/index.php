<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card-simran">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="h5 fw-bold mb-1">Pengelolaan Nilai IKPA</h2>
          <div class="text-muted" style="font-size: 13px;">
            Tahun Anggaran <?= esc($selected_year['year']) ?> · Input nilai indikator sesuai data OMSPAN / Monev SAKTI
          </div>
        </div>
      </div>

      <div class="p-3 rounded border mb-4" style="background: var(--surface);">
        <div class="fw-semibold mb-1">Impor PDF IKPA Kemenkeu</div>
        <div class="text-muted small mb-3">Periode, tujuh indikator, dispensasi, dan nilai akhir dibaca otomatis. Data hanya disimpan jika hasil hitung cocok dengan PDF.</div>
        <form method="post" action="<?= base_url('admin/ikpa/import-pdf') ?>" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap">
          <?= csrf_field() ?>
          <input type="hidden" name="fiscal_year_id" value="<?= esc($selected_year['id']) ?>">
          <input type="file" class="form-control-simran flex-grow-1" name="ikpa_pdf" accept="application/pdf,.pdf" required>
          <button type="submit" class="btn-simran btn-simran-primary">📄 Impor &amp; Verifikasi PDF</button>
        </form>
      </div>

      <?php if ($periods): ?>
        <div class="d-flex gap-2 flex-wrap align-items-center mb-4">
          <span class="small text-muted">Riwayat periode:</span>
          <?php foreach ($periods as $periodRow): ?>
            <a class="btn-simran btn-sm <?= (int)$periodRow['period_month'] === $selected_period ? 'btn-simran-primary' : '' ?>" href="<?= base_url('admin/ikpa') ?>?year=<?= esc($selected_year['year']) ?>&period=<?= (int)$periodRow['period_month'] ?>">
              <?= esc(\App\Models\IkpaModel::monthName((int)$periodRow['period_month'])) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= base_url('admin/ikpa/save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="fiscal_year_id" value="<?= esc($selected_year['id']) ?>">

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label for="period_month" class="form-label mb-1">Periode Laporan</label>
            <select class="form-select-simran" id="period_month" name="period_month" required>
              <?php for ($month = 1; $month <= 12; $month++): ?>
                <option value="<?= $month ?>" <?= $month === $selected_period ? 'selected' : '' ?>><?= esc(\App\Models\IkpaModel::monthName($month)) ?> <?= esc($selected_year['year']) ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <?php if (!empty($ikpa['source_filename'])): ?>
            <div class="col-md-6">
              <label class="form-label mb-1">Sumber Data</label>
              <div class="small p-2 rounded border text-muted">PDF: <?= esc($ikpa['source_filename']) ?><?= $ikpa['official_score'] !== null ? ' · Nilai resmi ' . number_format((float)$ikpa['official_score'], 2, ',', '.') : '' ?></div>
            </div>
          <?php endif; ?>
        </div>

        <div class="row g-3 mb-3">
          <?php foreach ($indicators as $key => $ind): ?>
            <div class="col-md-6">
              <label for="ik-<?= $key ?>" class="form-label mb-1" style="font-size: 13px; font-weight: 500;">
                <?= esc($ind['label']) ?> 
                <span class="badge bg-secondary-subtle text-secondary ms-1">Bobot <?= $ind['weight'] ?>%</span>
              </label>
              <div class="input-group">
                <input type="number" 
                       class="form-control-simran ik-calc-input" 
                       id="ik-<?= $key ?>" 
                       name="<?= $key ?>" 
                       data-weight="<?= $ind['weight'] ?>"
                       value="<?= esc($ikpa[$key] ?? '') ?>" 
                       min="0" 
                       max="120" 
                       step="0.01" 
                       placeholder="0.00 – 100.00">
                <span class="input-group-text bg-light border-start-0 text-muted" style="font-size: 13px;">poin</span>
              </div>
            </div>
          <?php endforeach; ?>

          <div class="col-md-6">
            <label for="ik-dispensasi" class="form-label mb-1" style="font-size: 13px; font-weight: 500;">
              Dispensasi SPM <span class="badge bg-danger-subtle text-danger ms-1">Poin Pengurang</span>
            </label>
            <div class="input-group">
              <input type="number" 
                     class="form-control-simran ik-calc-input" 
                     id="ik-dispensasi" 
                     name="dispensasi" 
                     data-is-disp="true"
                     value="<?= esc($ikpa['dispensasi'] ?? 0) ?>" 
                     min="0" 
                     max="50" 
                     step="0.01" 
                     placeholder="0.00">
              <span class="input-group-text bg-light border-start-0 text-muted" style="font-size: 13px;">pengurang</span>
            </div>
          </div>
        </div>

        <div class="mb-4">
          <label for="notes" class="form-label mb-1" style="font-size: 13px; font-weight: 500;">Catatan / Keterangan Monev</label>
          <textarea class="form-control-simran" id="notes" name="notes" rows="2" placeholder="Catatan opsional mengenai deviasi atau dispensasi SPM"><?= esc($ikpa['notes'] ?? '') ?></textarea>
        </div>

        <div class="d-flex justify-content-end">
          <button type="submit" class="btn-simran btn-simran-primary px-4 py-2">
            💾 Simpan Nilai IKPA
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-4">
    <!-- Live Score Preview Card -->
    <div class="card-simran">
      <h3 class="h6 fw-bold mb-3">Simulasi Skor Komposit IKPA</h3>

      <div class="p-4 rounded border text-center mb-3" style="background: var(--surface);">
        <div class="text-muted mb-1" style="font-size: 12.5px;">NILAI KOMPOSIT TERBOBOT</div>
        <div class="display-5 fw-bold mb-2 text-primary" id="sim-score">
          <?= $calc ? esc($calc['score']) : '0.00' ?>
        </div>
        <span class="pill <?= $calc ? esc($calc['class']) : 'bad' ?> fs-6 py-1 px-3" id="sim-category">
          <?= $calc ? esc($calc['category']) : 'Belum Lengkap' ?>
        </span>
      </div>

      <div class="text-muted" style="font-size: 12px; line-height: 1.6;">
        <b>Kategori Kinerja Kemenkeu:</b>
        <div class="d-flex justify-content-between mt-1"><span>Sangat Baik</span> <span class="fw-semibold text-success">≥ 95.00</span></div>
        <div class="d-flex justify-content-between"><span>Baik</span> <span class="fw-semibold text-warning">89.00 – 94.99</span></div>
        <div class="d-flex justify-content-between"><span>Cukup</span> <span class="fw-semibold text-warning">70.00 – 88.99</span></div>
        <div class="d-flex justify-content-between"><span>Kurang</span> <span class="fw-semibold text-danger">&lt; 70.00</span></div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const inputs = document.querySelectorAll('.ik-calc-input');
  const scoreEl = document.getElementById('sim-score');
  const catEl = document.getElementById('sim-category');

  function calculateLive() {
    let vsum = 0;
    let wsum = 0;
    let disp = 0;

    inputs.forEach(inp => {
      const val = parseFloat(inp.value);
      if (!isNaN(val)) {
        if (inp.dataset.isDisp) {
          disp = val;
        } else {
          const w = parseFloat(inp.dataset.weight) || 0;
          vsum += val * (w / 100);
          wsum += w;
        }
      }
    });

    if (wsum === 0) {
      scoreEl.textContent = '0.00';
      catEl.textContent = 'Belum Ada Nilai';
      catEl.className = 'pill bad fs-6 py-1 px-3';
      return;
    }

    const composite = (vsum / (wsum / 100));
    const finalScore = Math.max(0, composite - disp);

    scoreEl.textContent = finalScore.toFixed(2);

    if (finalScore >= 95) {
      catEl.textContent = 'Sangat Baik';
      catEl.className = 'pill ok fs-6 py-1 px-3';
    } else if (finalScore >= 89) {
      catEl.textContent = 'Baik';
      catEl.className = 'pill warn fs-6 py-1 px-3';
    } else if (finalScore >= 70) {
      catEl.textContent = 'Cukup';
      catEl.className = 'pill warn fs-6 py-1 px-3';
    } else {
      catEl.textContent = 'Kurang';
      catEl.className = 'pill bad fs-6 py-1 px-3';
    }
  }

  inputs.forEach(inp => inp.addEventListener('input', calculateLive));
});
</script>
<?= $this->endSection() ?>
