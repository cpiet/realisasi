<?php
$statusBadge = '<span class="badge bg-success px-3 py-1 rounded-pill">' . esc($account['status'] ?? 'Aktif') . '</span>';
if (str_contains($account['status'] ?? '', 'Tidak digunakan')) {
    $statusBadge = '<span class="badge bg-danger px-3 py-1 rounded-pill">' . esc($account['status']) . '</span>';
} elseif (str_contains($account['status'] ?? '', '[Rev]')) {
    $statusBadge = '<span class="badge bg-warning text-dark px-3 py-1 rounded-pill">' . esc($account['status']) . '</span>';
}
?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
  <div>
    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
      <span class="badge bg-primary px-3 py-2 font-monospace" style="font-size: 1.15rem; border-radius: 8px;">
        <i class="bi bi-tag-fill me-1"></i> <?= esc($account['kode']) ?>
      </span>
      <span class="badge bg-secondary bg-opacity-25 text-dark border px-3 py-2" style="font-size: 0.85rem; border-radius: 8px;">
        <?= esc($account['kelompok']) ?>
      </span>
      <?= $statusBadge ?>
    </div>
    <h3 class="fw-bold mb-1 text-primary mt-2"><?= esc($account['uraian']) ?></h3>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= base_url('akun/' . $account['kode']) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3" title="Buka tautan permanen">
      <i class="bi bi-box-arrow-up-right me-1"></i> Halaman Khusus
    </a>
    <button type="button" class="btn btn-light btn-sm rounded-pill px-3 border" onclick="document.getElementById('focusedAccountCard').style.display='none'">
      <i class="bi bi-x-lg"></i> Tutup
    </button>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="p-3 rounded-3 h-100" style="background: rgba(37, 99, 235, 0.04); border-left: 4px solid #2563eb;">
      <div class="fw-bold text-primary mb-2" style="font-size: 0.95rem;">
        <i class="bi bi-card-text me-1"></i> Untuk Apa / Penjelasan Peruntukan
      </div>
      <p class="mb-0 text-muted" style="line-height: 1.6; font-size: 0.92rem;">
        <?= esc($account['penjelasan']) ?>
      </p>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="p-3 rounded-3 h-100" style="background: rgba(14, 165, 233, 0.04); border-left: 4px solid #0ea5e9;">
      <div class="fw-bold text-info mb-2" style="font-size: 0.95rem;">
        <i class="bi bi-shield-check me-1"></i> Batasan / Pembeda Kunci
      </div>
      <p class="mb-0 text-muted" style="line-height: 1.6; font-size: 0.92rem;">
        <?= esc($account['batasan']) ?>
      </p>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="p-3 rounded-3 h-100" style="background: rgba(239, 68, 68, 0.04); border-left: 4px solid #ef4444;">
      <div class="fw-bold text-danger mb-2" style="font-size: 0.95rem;">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> Kesalahan Umum yang Sering Terjadi
      </div>
      <p class="mb-0 text-muted" style="line-height: 1.6; font-size: 0.92rem;">
        <?= esc($account['kesalahan_umum']) ?>
      </p>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="p-3 rounded-3 h-100" style="background: rgba(16, 185, 129, 0.04); border-left: 4px solid #10b981;">
      <div class="fw-bold text-success mb-2" style="font-size: 0.95rem;">
        <i class="bi bi-building me-1"></i> Contoh Kasus Riil di Lingkungan Divhumas Polri
      </div>
      <p class="mb-0 text-muted" style="line-height: 1.6; font-size: 0.92rem;">
        <?= esc($account['contoh_divhumas']) ?>
      </p>
    </div>
  </div>
</div>

<div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2 text-muted small">
  <div>
    <i class="bi bi-journal-bookmark me-1 text-primary"></i> <strong>Dasar Rujukan:</strong> <?= esc($account['sumber']) ?>
  </div>
  <div>
    <i class="bi bi-info-circle me-1 text-secondary"></i> Standar BAS KEP-331/PB/2021 &amp; KEP-291/PB/2022
  </div>
</div>
