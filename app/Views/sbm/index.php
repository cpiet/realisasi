<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('css/sbm.css?v=' . (@filemtime(FCPATH . 'css/sbm.css') ?: '1')) ?>">

<div class="page-heading mb-3">
  <div class="eyebrow">Standar Biaya Masukan</div>
  <h2 class="page-title mb-1">Standar Biaya SBM</h2>
  <p class="text-muted mb-0">Cari satuan biaya, langsung lihat harga, ketentuan, dan sumber peraturannya.</p>
</div>

<div id="sbmBanner" class="sbm-banner" role="status"></div>

<div class="sbm-banner" role="note">
  <strong>Perlu diingat:</strong> angka pada halaman ini adalah <em>batas tertinggi</em> (tidak boleh dilampaui),
  bukan target belanja. Karena kebijakan efisiensi anggaran, realisasi belanja umumnya perlu berada di bawah angka SBM:
  gunakan secara selektif sesuai kebutuhan dan prinsip ekonomis, efisien, dan efektif.
  Satuan biaya berlabel <em>Dapat dilampaui</em> boleh melebihi angka SBM hanya dengan mempertimbangkan harga pasar.
</div>

<div class="card-simran">
  <div class="sbm-filters">
    <div>
      <label class="form-label small fw-semibold" for="sbmQ">Kata kunci</label>
      <input type="search" id="sbmQ" class="form-control-simran w-100" maxlength="100" autocomplete="off"
             placeholder="mis. fullboard, snack, uang harian">
    </div>
    <div>
      <label class="form-label small fw-semibold" for="sbmReg">Regulasi / T.A.</label>
      <select id="sbmReg" class="form-select-simran w-100">
        <?php foreach ($regulasi_list as $r): ?>
          <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === (int)$default_reg ? 'selected' : '' ?>>
            <?= esc($r['jenis'] . ' ' . $r['nomor']) ?> (TA <?= esc($r['tahun_anggaran']) ?>)<?= $r['punya_nominal'] ? '' : ' — nominal belum ada' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="form-label small fw-semibold" for="sbmProv">Provinsi</label>
      <select id="sbmProv" class="form-select-simran w-100">
        <?php foreach ($wilayah_list as $w): ?>
          <option value="<?= (int)$w['id'] ?>" <?= (int)$w['id'] === (int)$default_wil ? 'selected' : '' ?>><?= esc($w['nama']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="sbm-chips" aria-label="Pencarian cepat">
    <?php foreach ($chips as $c): ?>
      <button type="button" class="sbm-chip" data-q="<?= esc($c) ?>"><?= esc($c) ?></button>
    <?php endforeach; ?>
  </div>
</div>

<div id="sbmQuick" class="card-simran mt-3" style="display:none">
  <div id="sbmFavBox" style="display:none">
    <div class="small fw-semibold mb-1">⭐ Favorit</div>
    <div id="sbmFavList" class="sbm-chips mt-0 mb-2"></div>
  </div>
  <div id="sbmRecentBox" style="display:none">
    <div class="small fw-semibold mb-1">🕘 Terakhir dilihat
      <button type="button" id="sbmClearRecent" class="btn btn-link btn-sm p-0 ms-2">bersihkan</button></div>
    <div id="sbmRecentList" class="sbm-chips mt-0 mb-0"></div>
  </div>
</div>

<div id="sbmResults" class="mt-3" aria-live="polite"></div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>window.sbmConfig = { defaultQuery: 'fullboard' };</script>
<script src="<?= base_url('js/simran-sbm.js?v=' . (@filemtime(FCPATH . 'js/simran-sbm.js') ?: '1')) ?>"></script>
<?= $this->endSection() ?>
