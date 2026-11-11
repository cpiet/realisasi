<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row g-3">
  <div class="col-lg-8">
    
    <!-- Info Banner Pagu DIPA Dipusatkan -->
    <div class="card-simran mb-4 p-3 bg-light border-primary border-start border-4 shadow-sm">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h2 class="h6 fw-bold mb-1 text-primary">🏛️ Pengelolaan Pagu DIPA &amp; Target Triwulan</h2>
          <div class="text-muted" style="font-size: 13px;">
            Penginputan Pagu DIPA (Belanja Pegawai, Belanja Barang, Belanja Modal) dan Target RPD kini dipusatkan di menu khusus agar lebih teratur dan praktis.
          </div>
        </div>
        <a href="<?= base_url('admin/pagu') ?>" class="btn-simran btn-simran-primary">
          🏛️ Buka Kelola Pagu DIPA &amp; Target →
        </a>
      </div>
    </div>

    <!-- Kelola Tahun Anggaran -->
    <div class="card-simran">
      <h2 class="h5 fw-bold mb-1">Pengelolaan Multi-Tahun Anggaran</h2>
      <div class="text-muted mb-3" style="font-size: 13px;">Kelola daftar tahun anggaran dan tetapkan tahun default aktif</div>

      <div class="row g-3 mb-4">
        <!-- Set Active Year Form -->
        <div class="col-md-6">
          <form method="post" action="<?= base_url('admin/settings/set-active-year') ?>" class="p-3 bg-light rounded border">
            <?= csrf_field() ?>
            <label class="form-label fw-bold mb-1" style="font-size: 13px;">Pilih Tahun Aktif Default</label>
            <div class="d-flex gap-2">
              <select name="fiscal_year_id" class="form-select-simran">
                <?php foreach ($all_years as $fy): ?>
                  <option value="<?= $fy['id'] ?>" <?= $fy['is_active'] ? 'selected' : '' ?>>
                    <?= esc($fy['name']) ?> <?= $fy['is_active'] ? '(Sedang Aktif)' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn-simran btn-simran-primary text-nowrap">
                Aktifkan
              </button>
            </div>
          </form>
        </div>

        <!-- Add Year Form -->
        <div class="col-md-6">
          <form method="post" action="<?= base_url('admin/settings/add-year') ?>" class="p-3 bg-light rounded border">
            <?= csrf_field() ?>
            <label class="form-label fw-bold mb-1" style="font-size: 13px;">Tambah Tahun Anggaran Baru</label>
            <div class="d-flex gap-2">
              <input type="number" name="year" class="form-control-simran" placeholder="Contoh: 2027" min="2020" max="2050" required>
              <button type="submit" class="btn-simran btn-simran-primary text-nowrap">
                + Tambah
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Year List Table -->
      <table class="table-simran">
        <thead>
          <tr>
            <th>Tahun</th>
            <th>Nama Label</th>
            <th>Status</th>
            <th>Dibuat Pada</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($all_years as $fy): ?>
            <tr>
              <td><b><?= esc($fy['year']) ?></b></td>
              <td><?= esc($fy['name']) ?></td>
              <td>
                <?php if ($fy['is_active']): ?>
                  <span class="pill ok">Aktif Default</span>
                <?php else: ?>
                  <span class="pill text-muted">Arsip / Non-aktif</span>
                <?php endif; ?>
              </td>
              <td class="text-muted" style="font-size: 12px;"><?= esc($fy['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>

  <div class="col-lg-4">
    <!-- Backup Database Card -->
    <div class="card-simran">
      <h3 class="h6 fw-bold mb-2">Backup Basis Data SQLite</h3>
      <p class="text-muted" style="font-size: 12.5px;">
        Unduh salinan berkas database <code>simran.sqlite</code> (data realisasi) dan <code>sbm.sqlite</code> (Standar Biaya SBM, termasuk harga yang Anda ubah). Berkas ini dapat dipindahkan atau dipulihkan kapan saja tanpa memerlukan server database MySQL terpisah.
      </p>
      <a href="<?= base_url('admin/settings/backup-all') ?>" class="btn-simran btn-simran-primary w-100 justify-content-center py-2 mb-2">
        📦 Unduh Backup Lengkap (ZIP: SIMRAN + SBM)
      </a>
      <a href="<?= base_url('admin/settings/backup') ?>" class="btn-simran w-100 justify-content-center py-2 mb-2">
        💾 Backup SIMRAN saja (simran.sqlite)
      </a>
      <a href="<?= base_url('admin/settings/backup-sbm') ?>" class="btn-simran w-100 justify-content-center py-2">
        💲 Backup Standar Biaya SBM saja (sbm.sqlite)
      </a>
    </div>

    <!-- Export Excel Card -->
    <div class="card-simran">
      <h3 class="h6 fw-bold mb-2">Ekspor Laporan Excel</h3>
      <p class="text-muted" style="font-size: 12.5px;">
        Unduh rekapitulasi realisasi per jenis belanja, KPI, dan target triwulanan dalam format Microsoft Excel (.xlsx).
      </p>
      <a href="<?= base_url('admin/settings/export-excel') ?>" class="btn-simran w-100 justify-content-center py-2">
        📊 Ekspor Laporan Excel (.xlsx)
      </a>
    </div>

    <!-- Pengaturan Umum -->
    <div class="card-simran">
      <h3 class="h6 fw-bold mb-3">Identitas Satuan Kerja</h3>
      <form method="post" action="<?= base_url('admin/settings/save-general') ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label text-muted" style="font-size: 12px;">Nama Aplikasi</label>
          <input type="text" class="form-control-simran" name="app_name" value="<?= esc($app_name) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label text-muted" style="font-size: 12px;">Instansi / Satuan Kerja</label>
          <input type="text" class="form-control-simran" name="institution" value="<?= esc($institution) ?>">
        </div>
        <button type="submit" class="btn-simran w-100 justify-content-center">
          Simpan Identitas
        </button>
      </form>
    </div>

    <div class="card-simran">
      <h3 class="h6 fw-bold mb-2">Keamanan Administrator</h3>
      <p class="text-muted" style="font-size: 12.5px;">Ganti password bawaan segera setelah aplikasi dipasang di hosting.</p>
      <form method="post" action="<?= base_url('account/change-password') ?>">
        <?= csrf_field() ?>
        <div class="mb-2">
          <label class="form-label text-muted" style="font-size: 12px;">Password Saat Ini</label>
          <input type="password" class="form-control-simran" name="current_password" required autocomplete="current-password">
        </div>
        <div class="mb-2">
          <label class="form-label text-muted" style="font-size: 12px;">Password Baru</label>
          <input type="password" class="form-control-simran" name="new_password" minlength="10" required autocomplete="new-password">
        </div>
        <div class="mb-3">
          <label class="form-label text-muted" style="font-size: 12px;">Ulangi Password Baru</label>
          <input type="password" class="form-control-simran" name="new_password_confirmation" minlength="10" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn-simran w-100 justify-content-center">🔐 Ganti Password</button>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
