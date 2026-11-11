<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card-simran">
      <h2 class="h5 fw-bold mb-1">Import Data LRA (Microsoft Excel)</h2>
      <div class="text-muted mb-4" style="font-size: 13px;">
        Unggah file Excel (.xlsx / .xls) laporan realisasi anggaran untuk diproses dan divalidasi oleh sistem
      </div>

      <form method="post" action="<?= base_url('admin/import/preview') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label for="fiscal_year_id" class="form-label text-muted" style="font-size: 12.5px; font-weight: 500;">Tahun Anggaran Target</label>
          <select class="form-select-simran" id="fiscal_year_id" name="fiscal_year_id" required>
            <?php foreach ($all_years as $fy): ?>
              <option value="<?= $fy['id'] ?>" <?= $active_year && $active_year['id'] == $fy['id'] ? 'selected' : '' ?>>
                <?= esc($fy['name']) ?> <?= $fy['is_active'] ? '(Aktif Default)' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label for="position_date" class="form-label text-muted" style="font-size: 12.5px; font-weight: 500;">Tanggal Posisi Data LRA</label>
          <input type="date" class="form-control-simran" id="position_date" name="position_date" value="<?= date('Y-m-d') ?>" required>
          <div class="text-muted" style="font-size: 11.5px; margin-top: 4px;">
            Bulan dan tahun realisasi transaksi akan disesuaikan dengan tanggal posisi ini.
          </div>
        </div>

        <div class="mb-4">
          <label for="excel_file" class="form-label text-muted" style="font-size: 12.5px; font-weight: 500;">Pilih File Excel (.xlsx / .xls)</label>
          <input type="file" class="form-control-simran" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
        </div>

        <div class="upload-actions d-flex gap-2">
          <button type="submit" class="btn-simran btn-simran-primary py-2 px-3">
            🔍 Unggah &amp; Periksa Validasi (Preview)
          </button>
          <a href="<?= base_url('admin/import/history') ?>" class="btn-simran py-2">
            Riwayat Import
          </a>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-5">
    <!-- Template & Petunjuk -->
    <div class="card-simran">
      <h3 class="h6 fw-bold mb-2">Format File</h3>
      <p class="text-muted" style="font-size: 13px;">
        Anda dapat langsung mengunggah workbook LRA operasional Divhumas yang memiliki sheet
        <b>LRA</b> dan <b>Perbln</b>. Anda <b>tidak wajib</b> memindahkan datanya ke template.
      </p>
      <div class="mb-3">
        <a href="<?= base_url('admin/import/template') ?>" class="btn-simran w-100 justify-content-center">
          📄 Unduh Template Rekap Bulanan (Opsional)
        </a>
        <div class="text-muted mt-2" style="font-size: 11.5px;">
          Gunakan template hanya jika Anda membuat rekap secara manual dan tidak memiliki workbook LRA operasional.
        </div>
      </div>

      <div class="border-top pt-3">
        <h4 class="h6 fw-bold mb-2" style="font-size: 13px;">Aturan Validasi SIMRAN:</h4>
        <ul class="text-muted ps-3 mb-0" style="font-size: 12px; line-height: 1.6;">
          <li>File <b>tidak langsung disimpan</b> ke database sebelum Anda memeriksa hasil validasi pada halaman preview.</li>
          <li>Sistem menggunakan strategi <b>upsert</b> agar data tidak terhitung ganda (double counting).</li>
          <li>Workbook operasional dibaca dari rekap bulanan Pegawai, Barang, dan Modal agar subtotal tidak terhitung ganda.</li>
          <li>Saat dikonfirmasi, workbook operasional menjadi snapshot aktif dan menggantikan data LRA lama pada tahun anggaran yang dipilih.</li>
          <li>Template opsional berisi pagu dan realisasi Januari–Desember untuk Pegawai, Barang, dan Modal.</li>
          <li>Baris kosong dan format non-numerik akan difilter secara otomatis.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
