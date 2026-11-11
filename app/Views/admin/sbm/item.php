<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('css/sbm.css?v=' . (@filemtime(FCPATH . 'css/sbm.css') ?: '1')) ?>">

<div class="page-heading mb-3">
  <div class="eyebrow"><a href="<?= base_url('admin/sbm/regulasi/' . $reg['id']) ?>">← <?= esc($reg['jenis'] . ' ' . $reg['nomor']) ?></a></div>
  <h2 class="page-title mb-1"><?= esc($item['kode'] . '. ' . $item['nama']) ?></h2>
  <a href="<?= base_url('sbm/item/' . $item['id']) ?>">Lihat tampilan publik</a>
</div>

<div class="card-simran sbm-card">
  <h3 class="mb-2">Harga (<?= (int)$total ?> baris di seluruh wilayah)</h3>
  <form method="get" class="mb-2 d-flex flex-wrap gap-2 align-items-end">
    <div style="min-width:240px">
    <label class="form-label small" for="prov">Tampilkan provinsi</label>
    <select id="prov" name="prov" class="form-select-simran w-100" onchange="this.form.submit()">
      <option value="0" <?= !$wil_id ? 'selected' : '' ?>>— hanya baris tanpa provinsi —</option>
      <?php foreach ($wilayah as $w): ?>
        <option value="<?= (int)$w['id'] ?>" <?= (int)$w['id'] === (int)$wil_id ? 'selected' : '' ?>><?= esc($w['nama']) ?></option>
      <?php endforeach; ?>
    </select>
    </div>
    <div style="min-width:240px">
      <label class="form-label small" for="q">Cari baris</label>
      <input id="q" name="q" class="form-control-simran w-100" maxlength="100" value="<?= esc($q) ?>" placeholder="mis. snack, golongan a, ambon">
    </div>
    <button class="btn-simran" type="submit">Cari</button>
  </form>
  <?php if ($shown_total > count($harga)): ?>
    <div class="sbm-banner warn">Menampilkan <?= count($harga) ?> dari <?= (int)$shown_total ?> baris. Gunakan kolom cari untuk mempersempit.</div>
  <?php endif; ?>
  <p class="text-muted small">Klik angka, ubah, lalu tekan Enter atau pindah kolom untuk menyimpan. Perubahan tercatat di riwayat.</p>

  <div class="sbm-table-wrap">
    <table class="sbm-table" id="hargaTable">
      <thead><tr><th>Uraian</th><th>Kolom</th><th>Satuan</th><th>Besaran</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($harga as $h): ?>
        <tr>
          <td><?= esc($h['kelompok']) ?></td>
          <td><?= esc($h['kolom']) ?></td>
          <td><?= esc($h['satuan']) ?></td>
          <td>
            <input class="form-control-simran harga-input" style="max-width:150px; text-align:right"
                   inputmode="numeric" data-id="<?= (int)$h['id'] ?>" data-old="<?= (int)$h['besaran'] ?>"
                   value="<?= $h['besaran'] === null ? '' : number_format((int)$h['besaran'], 0, ',', '.') ?>">
            <span class="small ms-1"><?= (($h['mata_uang'] ?? 'IDR') === 'USD') ? 'US$' : 'Rp' ?></span>
            <span class="small ms-1 harga-status" aria-live="polite"></span>
          </td>
          <td>
            <form method="post" action="<?= base_url('admin/sbm/harga/delete/' . $h['id']) ?>" onsubmit="return confirm('Hapus baris ini?');">
              <?= csrf_field() ?><button class="btn-simran text-danger" type="submit">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$harga): ?><tr><td colspan="5" class="text-muted">Tidak ada baris untuk pilihan ini.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <h3 class="mt-3 mb-2">Tambah baris harga</h3>
  <form method="post" action="<?= base_url('admin/sbm/item/' . $item['id'] . '/harga/create') ?>" class="row g-2">
    <?= csrf_field() ?>
    <div class="col-md-4"><label class="form-label small">Uraian / kelompok</label>
      <input name="kelompok" class="form-control-simran w-100" required maxlength="200" placeholder="mis. Rapat Biasa"></div>
    <div class="col-md-3"><label class="form-label small">Kolom</label>
      <input name="kolom" class="form-control-simran w-100" required maxlength="100" placeholder="mis. Kudapan (Snack)"></div>
    <div class="col-md-2"><label class="form-label small">Satuan</label>
      <input name="satuan" class="form-control-simran w-100" maxlength="40" placeholder="Orang/Kali"></div>
    <div class="col-md-3"><label class="form-label small">Besaran (Rp)</label>
      <input name="besaran" class="form-control-simran w-100" required inputmode="numeric"></div>
    <div class="col-md-6"><label class="form-label small">Provinsi</label>
      <select name="wilayah_id" class="form-select-simran w-100">
        <option value="0">— berlaku nasional —</option>
        <?php foreach ($wilayah as $w): ?><option value="<?= (int)$w['id'] ?>"><?= esc($w['nama']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-12"><button class="btn-simran btn-simran-primary" type="submit">Tambah baris</button></div>
  </form>
</div>

<div class="card-simran sbm-card">
  <h3 class="mb-2">Teks item</h3>
  <form method="post" action="<?= base_url('admin/sbm/item/update/' . $item['id']) ?>" class="row g-2">
    <?= csrf_field() ?>
    <div class="col-md-8"><label class="form-label small">Nama</label>
      <input name="nama" class="form-control-simran w-100" required value="<?= esc($item['nama']) ?>"></div>
    <div class="col-md-4"><label class="form-label small">Sifat</label>
      <select name="sifat" class="form-select-simran w-100">
        <option value="batas_tertinggi" <?= $item['sifat'] === 'batas_tertinggi' ? 'selected' : '' ?>>Batas tertinggi</option>
        <option value="dapat_dilampaui" <?= $item['sifat'] === 'dapat_dilampaui' ? 'selected' : '' ?>>Dapat dilampaui</option>
      </select></div>
    <div class="col-md-4"><label class="form-label small">Kode kanonik</label>
      <input name="kode_kanonik" class="form-control-simran w-100" value="<?= esc($item['kode_kanonik']) ?>"></div>
    <div class="col-md-4"><label class="form-label small">Halaman tabel</label>
      <input name="halaman_tabel" class="form-control-simran w-100" value="<?= esc($item['halaman_tabel']) ?>"></div>
    <div class="col-md-4"><label class="form-label small">Halaman ketentuan</label>
      <input name="halaman_penjelasan" class="form-control-simran w-100" value="<?= esc($item['halaman_penjelasan']) ?>"></div>
    <div class="col-12"><label class="form-label small">Deskripsi singkat</label>
      <textarea name="deskripsi" rows="2" class="form-control-simran w-100"><?= esc($item['deskripsi']) ?></textarea></div>
    <div class="col-12"><label class="form-label small">Ketentuan &amp; penjelasan (satu paragraf per baris; awali dengan • untuk butir)</label>
      <textarea name="penjelasan" rows="12" class="form-control-simran w-100"><?= esc($item['penjelasan']) ?></textarea></div>
    <div class="col-12 d-flex gap-2">
      <button class="btn-simran btn-simran-primary" type="submit">Simpan teks</button>
    </div>
  </form>
  <form method="post" action="<?= base_url('admin/sbm/item/delete/' . $item['id']) ?>" class="mt-2"
        onsubmit="return confirm('Hapus item beserta seluruh harganya?');">
    <?= csrf_field() ?><button class="btn-simran text-danger" type="submit">Hapus item</button>
  </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  const csrf = window.simranCsrf;
  document.querySelectorAll('.harga-input').forEach(function (inp) {
    function save() {
      const digits = inp.value.replace(/\D/g, '');
      const status = inp.parentElement.querySelector('.harga-status');
      if (digits === '' || digits === inp.dataset.old) { return; }
      const body = new FormData();
      body.append(csrf.name, csrf.value);
      body.append('besaran', digits);
      fetch(window.simranBaseUrl + '/admin/sbm/harga/update/' + inp.dataset.id, {
        method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest' }
      }).then(function (r) { return r.json(); }).then(function (d) {
        if (d.status === 'success') {
          inp.dataset.old = String(d.besaran);
          inp.value = Number(d.besaran).toLocaleString('id-ID');
          status.textContent = 'tersimpan ✓'; status.style.color = 'var(--ok)';
        } else {
          status.textContent = d.message; status.style.color = 'var(--bad)';
        }
      }).catch(function () { status.textContent = 'gagal menyimpan'; status.style.color = 'var(--bad)'; });
    }
    inp.addEventListener('change', save);
    inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
  });
})();
</script>
<?= $this->endSection() ?>
