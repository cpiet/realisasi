<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('css/sbm.css?v=' . (@filemtime(FCPATH . 'css/sbm.css') ?: '1')) ?>">

<div class="page-heading mb-3">
  <div class="eyebrow"><a href="<?= base_url('sbm') ?>">← Standar Biaya SBM</a></div>
  <h2 class="page-title mb-1"><?= esc($item['kode'] . '. ' . $item['nama']) ?></h2>
  <span class="sbm-badge <?= $item['sifat'] === 'batas_tertinggi' ? 'max' : 'over' ?>"><?= esc($item['sifat_label']) ?></span>
</div>

<?php if (!$regulasi['punya_nominal']): ?>
  <div class="sbm-banner warn">
    <strong><?= esc($regulasi['label']) ?> (TA <?= esc($regulasi['tahun_anggaran']) ?>)</strong>: nominal belum ditetapkan (menunggu KMK).
    <?php if ($item['acuan']): ?>
      <a href="<?= base_url('sbm/item/' . $item['acuan']['item_id']) ?>">Lihat angka acuan <?= esc($item['acuan']['label']) ?></a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="sbm-banner">
    Angka mengacu pada <strong><?= esc($regulasi['label']) ?> (TA <?= esc($regulasi['tahun_anggaran']) ?>)</strong>.
    Untuk RKA TA 2027, nominal baru berlaku setelah ditetapkan Menteri Keuangan; sementara angka ini dapat dijadikan acuan.
  </div>
<?php endif; ?>

<?php if ($item['sifat'] === 'batas_tertinggi'): ?>
  <div class="sbm-banner" role="note">
    <strong>Batas tertinggi:</strong> angka ini tidak boleh dilampaui dan bukan target belanja. Dengan adanya efisiensi anggaran,
    realisasi umumnya perlu lebih rendah; gunakan secara selektif sesuai kebutuhan.
  </div>
<?php else: ?>
  <div class="sbm-banner" role="note">
    <strong>Dapat dilampaui:</strong> angka ini dapat dilampaui dengan mempertimbangkan harga pasar serta prinsip ekonomis,
    efisien, dan efektif. Efisiensi anggaran tetap berlaku.
  </div>
<?php endif; ?>

<div class="card-simran sbm-card">
  <form method="get" class="mb-2 d-flex flex-wrap gap-2 align-items-end">
    <div>
    <label class="form-label small fw-semibold" for="prov">Provinsi</label>
    <select id="prov" name="prov" class="form-select-simran" onchange="this.form.submit()">
      <?php foreach ($wilayah_list as $w): ?>
        <option value="<?= (int)$w['id'] ?>" <?= (int)$w['id'] === (int)$wil_id ? 'selected' : '' ?>><?= esc($w['nama']) ?></option>
      <?php endforeach; ?>
    </select>
    </div>
    <div>
      <label class="form-label small fw-semibold" for="q">Cari di dalam item ini</label>
      <input id="q" name="q" type="search" maxlength="100" class="form-control-simran" value="<?= esc($q) ?>" placeholder="mis. ambon, golongan a">
    </div>
    <button class="btn-simran" type="submit">Cari</button>
  </form>
  <?php if ($total_baris > $max_baris): ?>
    <div class="sbm-banner warn">Menampilkan <?= (int)$max_baris ?> dari <?= (int)$total_baris ?> baris. Persempit dengan kolom pencarian di atas.</div>
  <?php endif; ?>

  <?php if ($item['deskripsi']): ?><p class="text-muted mb-2"><?= esc($item['deskripsi']) ?></p><?php endif; ?>

  <?php if ($item['harga']): ?>
    <div class="sbm-table-wrap">
      <table class="sbm-table">
        <thead><tr><th>Uraian</th><th>Satuan</th><th class="text-end">Besaran</th></tr></thead>
        <tbody>
        <?php foreach ($item['harga'] as $g): ?>
          <tr class="grp"><td colspan="3"><?= esc($g['kelompok']) ?></td></tr>
          <?php foreach ($g['baris'] as $b): ?>
            <tr>
              <td><?= esc($b['kolom']) ?><?= $b['needs_review'] ? ' <span class="sbm-badge review">perlu verifikasi</span>' : '' ?></td>
              <td><?= esc($b['satuan']) ?></td>
              <td class="num"><?php
                if ($b['besaran'] === null) { echo esc($b['keterangan'] ?: '-'); }
                elseif (($b['mata_uang'] ?? 'IDR') === 'USD') { echo 'US$' . number_format($b['besaran'], 0, '.', ','); }
                else { echo 'Rp' . number_format($b['besaran'], 0, ',', '.'); } ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php if ($item['ketentuan']): ?>
    <details class="sbm-ketentuan" open>
      <summary>Ketentuan &amp; Penjelasan</summary>
      <?php $inList = false; foreach ($item['ketentuan'] as $line): ?>
        <?php if (str_starts_with($line, '•')): ?>
          <?php if (!$inList): ?><ul><?php $inList = true; endif; ?>
          <li><?= esc(ltrim(mb_substr($line, 1))) ?></li>
        <?php else: ?>
          <?php if ($inList): ?></ul><?php $inList = false; endif; ?>
          <p><?= esc($line) ?></p>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if ($inList): ?></ul><?php endif; ?>
    </details>
  <?php endif; ?>

  <div class="sbm-source">Sumber: <?= esc($item['sumber']) ?></div>
</div>
<?= $this->endSection() ?>
