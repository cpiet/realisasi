<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('css/sbm.css?v=' . (@filemtime(FCPATH . 'css/sbm.css') ?: '1')) ?>">

<div class="page-heading mb-3">
  <div class="eyebrow">Admin</div>
  <h2 class="page-title mb-1">Kelola Standar Biaya SBM</h2>
  <p class="text-muted mb-0">Tambah regulasi/KMK baru dan ubah harga bila ada perubahan. <a href="<?= base_url('sbm') ?>">Lihat halaman publik</a></p>
</div>

<div class="card-simran sbm-card">
  <h3 class="mb-2">Regulasi</h3>
  <div class="sbm-table-wrap">
    <table class="sbm-table">
      <thead><tr><th>Regulasi</th><th>TA</th><th>Status</th><th>Nominal</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($regulasi as $r): ?>
        <tr>
          <td><?= esc($r['jenis'] . ' ' . $r['nomor']) ?><div class="text-muted small"><?= esc($r['judul']) ?></div></td>
          <td><?= esc($r['tahun_anggaran']) ?></td>
          <td><?= esc(str_replace('_', ' ', $r['status'])) ?></td>
          <td><?= $r['punya_nominal'] ? 'Ada' : 'Belum' ?></td>
          <td><a class="btn-simran" href="<?= base_url('admin/sbm/regulasi/' . $r['id']) ?>">Kelola</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card-simran sbm-card">
  <h3 class="mb-2">Tambah Regulasi / KMK Baru</h3>
  <form method="post" action="<?= base_url('admin/sbm/regulasi/create') ?>" class="row g-2">
    <?= csrf_field() ?>
    <div class="col-md-2"><label class="form-label small">Jenis</label>
      <select name="jenis" class="form-select-simran w-100"><option>KMK</option><option>PMK</option></select></div>
    <div class="col-md-3"><label class="form-label small">Nomor</label>
      <input name="nomor" class="form-control-simran w-100" required maxlength="60" placeholder="mis. 123 Tahun 2026"></div>
    <div class="col-md-5"><label class="form-label small">Judul</label>
      <input name="judul" class="form-control-simran w-100" required maxlength="200" placeholder="Besaran Standar Biaya Masukan TA 2027"></div>
    <div class="col-md-2"><label class="form-label small">Tahun anggaran</label>
      <input name="tahun_anggaran" type="number" class="form-control-simran w-100" required value="2027"></div>
    <div class="col-md-3"><label class="form-label small">Status</label>
      <select name="status" class="form-select-simran w-100">
        <option value="berlaku">Berlaku</option><option value="belum_berlaku">Belum berlaku</option><option value="dicabut">Dicabut</option>
      </select></div>
    <div class="col-md-5"><label class="form-label small">Salin item &amp; harga dari (opsional)</label>
      <select name="salin_dari" class="form-select-simran w-100">
        <option value="0">— kosong —</option>
        <?php foreach ($regulasi as $r): ?>
          <option value="<?= (int)$r['id'] ?>"><?= esc($r['jenis'] . ' ' . $r['nomor'] . ' (TA ' . $r['tahun_anggaran'] . ')') ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-4 d-flex align-items-end"><label class="small"><input type="checkbox" name="punya_nominal" value="1" checked> Sudah memuat nominal</label></div>
    <div class="col-12"><label class="form-label small">Catatan</label>
      <input name="catatan" class="form-control-simran w-100" maxlength="500"></div>
    <div class="col-12"><button class="btn-simran btn-simran-primary" type="submit">Simpan regulasi</button></div>
  </form>
  <p class="text-muted small mt-2 mb-0">Tips: pilih "salin dari" PMK 32/2025, lalu ubah angka yang berubah di KMK. Item dan ketentuan ikut tersalin.</p>
</div>

<div class="card-simran sbm-card">
  <h3 class="mb-2">Riwayat perubahan terakhir</h3>
  <div class="sbm-table-wrap">
    <table class="sbm-table">
      <thead><tr><th>Waktu</th><th>Keterangan</th><th>Lama</th><th>Baru</th><th>Oleh</th></tr></thead>
      <tbody>
      <?php foreach ($log as $l): ?>
        <tr><td><?= esc($l['created_at']) ?></td><td><?= esc($l['keterangan']) ?></td>
            <td><?= esc($l['nilai_lama']) ?></td><td><?= esc($l['nilai_baru']) ?></td><td><?= esc($l['oleh']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$log): ?><tr><td colspan="5" class="text-muted">Belum ada perubahan.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?= $this->endSection() ?>
