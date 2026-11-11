<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-xl py-4">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
      <li class="breadcrumb-item"><a href="<?= base_url() ?>">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= base_url('akun') ?>">Kamus Kode Akun</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= esc($account['kode']) ?></li>
    </ol>
  </nav>

  <!-- Detail Card -->
  <div class="card-simran p-4 mb-4 border shadow-sm" style="border-radius: 16px;">
    <?= view('account_codes/_card_detail', ['account' => $account]) ?>
  </div>

  <div class="row g-4">
    <!-- Kasus Terkait jika ada -->
    <?php if (!empty($divhumasCases)): ?>
      <div class="col-lg-6">
        <div class="card-simran p-4 h-100 border shadow-sm" style="border-radius: 16px;">
          <h5 class="fw-bold mb-3 text-success">
            <i class="bi bi-building me-2"></i>Contoh Kasus Divhumas Terkait Akun Ini
          </h5>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($divhumasCases as $c): ?>
              <div class="p-3 rounded-3 border bg-light">
                <div class="badge bg-primary mb-2 font-monospace">Kasus #<?= esc($c['no']) ?></div>
                <div class="fw-semibold mb-2" style="font-size: 0.95rem;"><?= esc($c['situasi']) ?></div>
                <div class="small text-success fw-bold mb-1">
                  <i class="bi bi-check-circle-fill me-1"></i> Akun: <?= esc($c['akun_tepat']) ?>
                </div>
                <div class="small text-muted"><?= esc($c['alasan']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Akun Terkait / Dalam Satu Kelompok -->
    <div class="col-lg-<?= empty($divhumasCases) ? '12' : '6' ?>">
      <div class="card-simran p-4 h-100 border shadow-sm" style="border-radius: 16px;">
        <h5 class="fw-bold mb-3 text-primary">
          <i class="bi bi-collection me-2"></i>Akun Lain dalam Kelompok <?= esc($account['kelompok']) ?>
        </h5>
        <div class="list-group list-group-flush">
          <?php foreach ($relatedAccounts as $rel): ?>
            <a href="<?= base_url('akun/' . $rel['kode']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 px-2 <?= $rel['kode'] === $account['kode'] ? 'bg-primary bg-opacity-10 text-primary fw-bold' : '' ?>">
              <div>
                <span class="badge bg-secondary bg-opacity-10 text-secondary border font-monospace me-2"><?= esc($rel['kode']) ?></span>
                <span><?= esc($rel['uraian']) ?></span>
              </div>
              <i class="bi bi-chevron-right text-muted small"></i>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="text-center mt-4">
    <a href="<?= base_url('akun') ?>" class="btn btn-outline-secondary rounded-pill px-4">
      <i class="bi bi-arrow-left me-1"></i> Kembali ke Direktori Kamus Akun
    </a>
  </div>

</div>
<?= $this->endSection() ?>
