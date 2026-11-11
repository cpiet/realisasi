<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row justify-content-center">
  <div class="col-lg-7 col-xl-6">
    <div class="page-heading mb-4">
      <div class="eyebrow">Keamanan akun</div>
      <h2 class="page-title mb-1">Profil &amp; Password</h2>
      <p class="text-muted mb-0">Kelola keamanan akun yang sedang digunakan.</p>
    </div>

    <div class="card-simran">
      <div class="d-flex align-items-center gap-3 mb-4">
        <div class="simran-logo-badge"><?= esc(strtoupper(substr($user['name'], 0, 1))) ?></div>
        <div>
          <div class="fw-bold"><?= esc($user['name']) ?></div>
          <div class="text-muted small">@<?= esc($user['username']) ?> · <?= esc(ucfirst($user['role'])) ?></div>
        </div>
      </div>

      <form method="post" action="<?= base_url('account/change-password') ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Password Saat Ini</label>
          <input type="password" class="form-control-simran" name="current_password" required autocomplete="current-password">
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Password Baru</label>
            <input type="password" class="form-control-simran" name="new_password" minlength="10" required autocomplete="new-password">
          </div>
          <div class="col-md-6">
            <label class="form-label">Ulangi Password Baru</label>
            <input type="password" class="form-control-simran" name="new_password_confirmation" minlength="10" required autocomplete="new-password">
          </div>
        </div>
        <div class="text-muted mt-2 mb-3" style="font-size:12px;">Minimal 10 karakter dan gunakan kombinasi huruf, angka, serta simbol.</div>
        <button type="submit" class="btn-simran btn-simran-primary">🔐 Perbarui Password</button>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
