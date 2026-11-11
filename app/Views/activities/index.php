<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
  <a href="<?= base_url('kegiatan?year=' . esc($selected_year['year'])) ?>" class="btn-simran btn-simran-primary btn-sm py-1 px-3">
    <i class="bi bi-grid-3x3-gap-fill me-1"></i> Rincian Akun &amp; Realisasi
  </a>
  <a href="<?= base_url('kegiatan/uraian?year=' . esc($selected_year['year'])) ?>" class="btn-simran btn-sm py-1 px-3 bg-white border text-success fw-semibold">
    <i class="bi bi-card-checklist me-1"></i> Tabel Uraian Kegiatan (110 Giat)
  </a>
  <a href="<?= base_url('admin/rpa?year=' . esc($selected_year['year'])) ?>" class="btn-simran btn-sm py-1 px-3 bg-white border text-primary fw-semibold">
    <i class="bi bi-table me-1"></i> Lembar RPA
  </a>
  <a href="<?= base_url('admin/pagu?year=' . esc($selected_year['year'])) ?>" class="btn-simran btn-sm py-1 px-3 bg-white border text-secondary fw-semibold">
    <i class="bi bi-bank me-1"></i> Pagu DIPA
  </a>
</div>

<div class="card-simran">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h2 class="h5 fw-bold mb-1">Eksplorasi Data Kegiatan &amp; Anggaran</h2>
      <div class="text-muted" style="font-size: 13px;">Daftar rincian akun, pagu, dan perkembangan realisasi Divhumas Polri</div>
    </div>
    <div>
      <span class="badge bg-primary fs-6"><?= count($items) ?> Detail Anggaran</span>
    </div>
  </div>

  <!-- Filter Bar (Modern, Aligned, Auto-Filter) -->
  <form method="get" action="<?= base_url('kegiatan') ?>" class="d-flex align-items-center flex-wrap gap-2 mb-3" id="kegiatanFilterForm">
    <input type="hidden" name="year" value="<?= esc($selected_year['year']) ?>">

    <!-- Search Input with Integrated Icon -->
    <div class="position-relative flex-grow-1" style="min-width: 250px;">
      <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 14px; opacity: 0.5; pointer-events: none;">🔍</span>
      <input type="text" 
             name="q" 
             id="filterKeyword" 
             class="form-control-simran" 
             style="padding-left: 36px; padding-right: <?= !empty($keyword) ? '32px' : '12px' ?>; height: 38px;" 
             placeholder="Cari kode akun, uraian, kegiatan..." 
             value="<?= esc($keyword) ?>">
      <?php if (!empty($keyword)): ?>
        <a href="<?= base_url('kegiatan') ?>?year=<?= esc($selected_year['year']) ?>&program_id=<?= esc($selected_program) ?>&budget_type_id=<?= esc($selected_belanja) ?>" 
           style="position: absolute; right: 9px; top: 50%; transform: translateY(-50%); color: var(--muted); text-decoration: none; font-size: 12px; font-weight: bold; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: var(--border2);" 
           title="Hapus kata kunci">✕</a>
      <?php endif; ?>
    </div>

    <!-- Dropdown Program -->
    <div style="min-width: 230px;" class="flex-grow-1">
      <select name="program_id" id="filterProgram" class="form-select-simran w-100" style="height: 38px;" onchange="this.form.submit()">
        <option value="">— Semua Program —</option>
        <?php foreach ($programs as $pr): ?>
          <option value="<?= $pr['id'] ?>" <?= $selected_program == $pr['id'] ? 'selected' : '' ?>>
            <?= esc($pr['code']) ?> - <?= esc($pr['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Dropdown Jenis Belanja -->
    <div style="min-width: 190px;" class="flex-grow-1">
      <select name="budget_type_id" id="filterBudgetType" class="form-select-simran w-100" style="height: 38px;" onchange="this.form.submit()">
        <option value="">— Semua Jenis Belanja —</option>
        <?php foreach ($budget_types as $bt): ?>
          <option value="<?= $bt['id'] ?>" <?= $selected_belanja == $bt['id'] ? 'selected' : '' ?>>
            <?= esc($bt['code']) ?> - <?= esc($bt['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Reset Filter Button (Shown if filtered) -->
    <?php if (!empty($keyword) || !empty($selected_program) || !empty($selected_belanja)): ?>
      <a href="<?= base_url('kegiatan') ?>?year=<?= esc($selected_year['year']) ?>" class="btn-simran text-danger border-danger px-3" style="height: 38px; display: inline-flex; align-items: center; gap: 5px;" title="Reset semua filter">
        <span>↺</span> <span>Reset</span>
      </a>
    <?php endif; ?>
  </form>

  <!-- Table Rincian Anggaran -->
  <div class="table-responsive">
    <table class="table-simran align-middle activities-table">
      <thead>
        <tr>
          <th style="width: 130px; text-align: left;">Kode &amp; Akun</th>
          <th style="text-align: left;">Uraian / Kegiatan</th>
          <th style="width: 160px; text-align: left;">Unit / Bagian</th>
          <th class="text-end" style="width: 145px;">Pagu (Rp)</th>
          <th class="text-end" style="width: 145px;">Realisasi (Rp)</th>
          <th class="text-end" style="width: 145px;">Sisa (Rp)</th>
          <th class="text-center" style="width: 110px;">Penyerapan</th>
          <th class="text-center" style="width: 75px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              Tidak ada data rincian kegiatan yang sesuai kriteria pencarian.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($items as $it): ?>
            <tr style="cursor: pointer;" onclick="openActivityDetail(<?= $it['id'] ?>)">
              <!-- Kode Akun -->
              <td data-label="Kode & Akun" style="text-align: left;">
                <?php if (!empty($it['account_code']) && strlen($it['account_code']) >= 5): ?>
                  <a href="<?= base_url('akun/' . esc($it['account_code'])) ?>" target="_blank" onclick="event.stopPropagation();" class="badge bg-light text-primary border font-monospace text-decoration-none hover-shadow" title="Klik untuk melihat penjelasan akun di Kamus BAS" style="font-size: 12px;">
                    <i class="bi bi-book me-1"></i><?= esc($it['account_code']) ?>
                  </a>
                <?php else: ?>
                  <span class="badge bg-light text-dark border font-monospace" style="font-size: 12px;"><?= esc($it['account_code'] ?: '—') ?></span>
                <?php endif; ?>
                <div class="small text-muted mt-1" style="font-size: 11.5px;"><?= esc($it['budget_type_name'] ?? '') ?></div>
              </td>

              <!-- Uraian Kegiatan -->
              <td data-label="Uraian" style="text-align: left;">
                <div class="fw-semibold text-dark" style="font-size: 13.5px; line-height: 1.4;"><?= esc($it['description'] ?: $it['activity_name']) ?></div>
                <small class="text-muted" style="font-size: 12px;"><?= esc($it['activity_name'] ?? '') ?></small>
              </td>

              <!-- Unit -->
              <td data-label="Unit" style="text-align: left;">
                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 11.5px; font-weight: 500;"><?= esc($it['unit_name'] ?? 'Divhumas') ?></span>
              </td>

              <!-- Pagu -->
              <td data-label="Pagu" class="text-end fw-semibold nowrap" style="font-size: 13px;">
                Rp <?= number_format($it['final_ceiling'], 0, ',', '.') ?>
              </td>

              <!-- Realisasi -->
              <td data-label="Realisasi" class="text-end fw-semibold text-primary nowrap" style="font-size: 13px;">
                Rp <?= number_format($it['realisasi'], 0, ',', '.') ?>
              </td>

              <!-- Sisa -->
              <td data-label="Sisa" class="text-end text-muted nowrap" style="font-size: 13px;">
                Rp <?= number_format($it['sisa'], 0, ',', '.') ?>
              </td>

              <!-- Penyerapan -->
              <td data-label="Penyerapan" class="text-center nowrap">
                <span class="pill <?= $it['serap_pct'] >= 70 ? 'ok' : ($it['serap_pct'] >= 40 ? 'warn' : 'bad') ?>">
                  <?= number_format($it['serap_pct'], 2, ',', '.') ?>%
                </span>
              </td>

              <!-- Aksi -->
              <td data-label="Aksi" class="text-center">
                <button type="button" class="btn-simran btn-sm py-1 px-2" style="height: 28px; font-size: 12px;" onclick="event.stopPropagation(); openActivityDetail(<?= $it['id'] ?>)">
                  Detail
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const kwInput = document.getElementById('filterKeyword');
  const form = document.getElementById('kegiatanFilterForm');
  let debounceTimer;

  // Jika ada pencarian di URL, fokuskan cursor ke ujung input
  const urlParams = new URLSearchParams(window.location.search);
  if (kwInput && urlParams.has('q') && kwInput.value.length > 0) {
    kwInput.focus();
    const len = kwInput.value.length;
    kwInput.setSelectionRange(len, len);
  }

  // Auto-filter saat mengetik pencarian (debounce 500ms)
  if (kwInput && form) {
    kwInput.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        form.submit();
      }, 500);
    });
  }
});
</script>
<?= $this->endSection() ?>
