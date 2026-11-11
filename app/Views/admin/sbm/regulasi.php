<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('css/sbm.css?v=' . (@filemtime(FCPATH . 'css/sbm.css') ?: '1')) ?>">

<div class="page-heading mb-3">
  <div class="eyebrow"><a href="<?= base_url('admin/sbm') ?>">← Kelola SBM</a></div>
  <h2 class="page-title mb-1"><?= esc($reg['jenis'] . ' ' . $reg['nomor']) ?> (TA <?= esc($reg['tahun_anggaran']) ?>)</h2>
</div>

<div class="card-simran sbm-card">
  <h3 class="mb-2">Data regulasi</h3>
  <form method="post" action="<?= base_url('admin/sbm/regulasi/update/' . $reg['id']) ?>" class="row g-2">
    <?= csrf_field() ?>
    <div class="col-md-3"><label class="form-label small">Nomor</label>
      <input name="nomor" class="form-control-simran w-100" required value="<?= esc($reg['nomor']) ?>"></div>
    <div class="col-md-5"><label class="form-label small">Judul</label>
      <input name="judul" class="form-control-simran w-100" required value="<?= esc($reg['judul']) ?>"></div>
    <div class="col-md-2"><label class="form-label small">Tahun anggaran</label>
      <input name="tahun_anggaran" type="number" class="form-control-simran w-100" required value="<?= esc($reg['tahun_anggaran']) ?>"></div>
    <div class="col-md-2"><label class="form-label small">Status</label>
      <select name="status" class="form-select-simran w-100">
        <?php foreach (['berlaku' => 'Berlaku', 'belum_berlaku' => 'Belum berlaku', 'dicabut' => 'Dicabut'] as $v => $t): ?>
          <option value="<?= $v ?>" <?= $reg['status'] === $v ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-9"><label class="form-label small">Catatan</label>
      <input name="catatan" class="form-control-simran w-100" value="<?= esc($reg['catatan']) ?>"></div>
    <div class="col-md-3 d-flex align-items-end"><label class="small"><input type="checkbox" name="punya_nominal" value="1" <?= $reg['punya_nominal'] ? 'checked' : '' ?>> Sudah memuat nominal</label></div>
    <div class="col-12 d-flex gap-2">
      <button class="btn-simran btn-simran-primary" type="submit">Simpan</button>
    </div>
  </form>
  <form method="post" action="<?= base_url('admin/sbm/regulasi/delete/' . $reg['id']) ?>" class="mt-2"
        onsubmit="return confirm('Hapus regulasi beserta seluruh item dan harganya?');">
    <?= csrf_field() ?>
    <button class="btn-simran text-danger" type="submit">Hapus regulasi</button>
  </form>
</div>

<div class="card-simran sbm-card">
  <h3 class="mb-2">Item satuan biaya</h3>
  <div class="sbm-table-wrap">
    <table class="sbm-table">
      <thead><tr><th>Lamp.</th><th>No.</th><th>Nama</th><th>Sifat</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($items as $i): ?>
        <tr><td><?= esc($i['lampiran']) ?></td><td><?= esc($i['kode']) ?></td><td><?= esc($i['nama']) ?></td>
            <td><?= $i['sifat'] === 'batas_tertinggi' ? 'Batas tertinggi' : 'Dapat dilampaui' ?></td>
            <td><a class="btn-simran" href="<?= base_url('admin/sbm/item/' . $i['id']) ?>">Edit</a></td></tr>
      <?php endforeach; ?>
      <?php if (!$items): ?><tr><td colspan="5" class="text-muted">Belum ada item.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <h3 class="mt-3 mb-2">Tambah item</h3>
  <form method="post" action="<?= base_url('admin/sbm/regulasi/' . $reg['id'] . '/item/create') ?>" class="row g-2">
    <?= csrf_field() ?>
    <div class="col-md-2"><label class="form-label small">Lampiran</label>
      <select name="lampiran" class="form-select-simran w-100"><option>I</option><option>II</option></select></div>
    <div class="col-md-2"><label class="form-label small">No.</label>
      <input name="kode" class="form-control-simran w-100" required maxlength="10"></div>
    <div class="col-md-5"><label class="form-label small">Nama</label>
      <input name="nama" class="form-control-simran w-100" required maxlength="250"></div>
    <div class="col-md-3"><label class="form-label small">Sifat</label>
      <select name="sifat" class="form-select-simran w-100">
        <option value="batas_tertinggi">Batas tertinggi</option><option value="dapat_dilampaui">Dapat dilampaui</option>
      </select></div>
    <div class="col-md-6"><label class="form-label small">Kode kanonik (pengikat antar tahun, opsional)</label>
      <input name="kode_kanonik" class="form-control-simran w-100" placeholder="mis. rapat_luar_kantor"></div>
    <div class="col-12"><button class="btn-simran btn-simran-primary" type="submit">Tambah item</button></div>
  </form>
</div>
<?= $this->endSection() ?>
