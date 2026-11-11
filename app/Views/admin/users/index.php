<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="page-heading d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
  <div>
    <div class="eyebrow">Akses &amp; keamanan</div>
    <h2 class="page-title mb-1">Manajemen Pengguna</h2>
    <p class="text-muted mb-0">Kelola maksimal <?= $max_users ?> akun dan level aksesnya.</p>
  </div>
  <span class="status-dot-badge"><?= count($users) ?> / <?= $max_users ?> akun</span>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <?php foreach ($users as $user): ?>
      <div class="card-simran mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
          <div>
            <div class="fw-bold fs-6"><?= esc($user['name']) ?></div>
            <div class="text-muted small">@<?= esc($user['username']) ?> · Login terakhir: <?= esc($user['last_login_at'] ?: 'Belum pernah') ?></div>
          </div>
          <span class="pill <?= $user['is_active'] ? 'ok' : 'bad' ?>"><?= $user['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
        </div>

        <form method="post" action="<?= base_url('admin/users/update/' . $user['id']) ?>" class="row g-2 align-items-end mb-3">
          <?= csrf_field() ?>
          <div class="col-md-5">
            <label class="form-label small text-muted">Nama</label>
            <input class="form-control-simran" name="name" value="<?= esc($user['name']) ?>" required maxlength="150">
          </div>
          <div class="col-md-3">
            <label class="form-label small text-muted">Level</label>
            <select class="form-select-simran" name="role" <?= (int)$user['id'] === (int)session()->get('user_id') ? 'disabled' : '' ?>>
              <?php foreach ($role_labels as $role => $label): ?>
                <option value="<?= esc($role) ?>" <?= $user['role'] === $role ? 'selected' : '' ?>><?= esc($label) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ((int)$user['id'] === (int)session()->get('user_id')): ?><input type="hidden" name="role" value="admin"><?php endif; ?>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted">Status</label>
            <select class="form-select-simran" name="is_active" <?= (int)$user['id'] === (int)session()->get('user_id') ? 'disabled' : '' ?>>
              <option value="1" <?= $user['is_active'] ? 'selected' : '' ?>>Aktif</option>
              <option value="0" <?= !$user['is_active'] ? 'selected' : '' ?>>Nonaktif</option>
            </select>
            <?php if ((int)$user['id'] === (int)session()->get('user_id')): ?><input type="hidden" name="is_active" value="1"><?php endif; ?>
          </div>
          <div class="col-md-2"><button class="btn-simran w-100" type="submit">Simpan</button></div>
        </form>

        <!-- PIN Login 6 Digit Pengguna -->
        <div class="p-3 rounded-3 bg-light border mb-3">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
              <span class="text-muted small fw-semibold" style="font-size: 11.5px;">🔢 PIN LOGIN OPERASIONAL (6 DIGIT):</span>
              <div class="d-flex align-items-center gap-2 mt-1">
                <span class="fs-5 fw-bold px-3 py-1 rounded border bg-white text-primary font-monospace tracking-wide" id="pinText_<?= $user['id'] ?>" style="letter-spacing: 2px;">
                  <?= esc($user['login_token'] ?: 'Belum dibuat') ?>
                </span>
                <?php if (!empty($user['login_token'])): ?>
                  <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="copyPin('<?= esc($user['login_token']) ?>', this)" title="Salin 6 digit PIN login">
                    📋 Salin
                  </button>
                <?php endif; ?>
              </div>
            </div>
            
            <form method="post" action="<?= base_url('admin/users/generate-pin/' . $user['id']) ?>" onsubmit="return confirm('Buat PIN 6 digit baru secara acak untuk <?= esc($user['name']) ?>?')">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-primary py-1 px-2" type="submit" title="Generate PIN 6 digit baru secara acak">
                🎲 <?= empty($user['login_token']) ? 'Buat PIN Acak' : 'Acak PIN Baru' ?>
              </button>
            </form>
          </div>

          <!-- Form Edit PIN Kustom -->
          <div class="pt-2 border-top mt-2">
            <form method="post" action="<?= base_url('admin/users/update-pin/' . $user['id']) ?>" class="d-flex align-items-center flex-wrap gap-2">
              <?= csrf_field() ?>
              <span class="small text-muted fw-semibold" style="font-size: 11.5px;">Edit PIN:</span>
              <div class="input-group input-group-sm" style="max-width: 170px;">
                <input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                       class="form-control font-monospace text-center fw-bold"
                       name="pin" value="<?= esc($user['login_token']) ?>" required placeholder="6 digit"
                       style="letter-spacing: 2px;"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                <button class="btn btn-primary" type="submit" title="Simpan perubahan PIN">Simpan</button>
              </div>
              <span class="text-muted small" style="font-size: 11px;">(Tepat 6 digit angka)</span>
            </form>
          </div>

          <div class="text-muted small mt-2" style="font-size: 11px;">
            <i class="bi bi-info-circle text-primary me-1"></i>
            Pengguna dapat login langsung dengan mengetikkan 6 digit PIN ini di halaman login tanpa perlu password.
          </div>
        </div>

        <div class="d-flex gap-2 flex-wrap border-top pt-3">
          <?php if ((int)$user['id'] !== (int)session()->get('user_id')): ?>
            <form method="post" action="<?= base_url('admin/users/reset-password/' . $user['id']) ?>" class="d-flex gap-2 flex-grow-1">
              <?= csrf_field() ?>
              <input type="password" class="form-control-simran" name="new_password" minlength="10" required placeholder="Password kuat baru">
              <button class="btn-simran" type="submit">Reset Password</button>
            </form>
            <form method="post" action="<?= base_url('admin/users/delete/' . $user['id']) ?>" onsubmit="return confirm('Hapus pengguna ini?')">
              <?= csrf_field() ?>
              <button class="btn-simran text-danger border-danger" type="submit">Hapus</button>
            </form>
          <?php else: ?>
            <a href="<?= base_url('account') ?>" class="btn-simran">Ganti password akun saya</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <script>
  function copyPin(text, btn) {
    if (!navigator.clipboard) {
      const el = document.createElement('textarea');
      el.value = text;
      document.body.appendChild(el);
      el.select();
      document.execCommand('copy');
      document.body.removeChild(el);
      btn.innerText = '✅ Tersalin!';
      setTimeout(() => { btn.innerText = '📋 Salin'; }, 2000);
      return;
    }
    navigator.clipboard.writeText(text).then(function() {
      const orig = btn.innerHTML;
      btn.innerHTML = '✅ Tersalin!';
      btn.classList.replace('btn-outline-secondary', 'btn-success');
      setTimeout(() => {
        btn.innerHTML = orig;
        btn.classList.replace('btn-success', 'btn-outline-secondary');
      }, 2000);
    });
  }
  function copyToken(text, btn) {
    copyPin(text, btn);
  }
  </script>

  <div class="col-xl-4">
    <div class="card-simran">
      <h3 class="h6 fw-bold">Tambah Pengguna</h3>
      <?php if (count($users) >= $max_users): ?>
        <div class="alert alert-info mb-0">Batas <?= $max_users ?> pengguna sudah tercapai. Hapus akun yang tidak diperlukan untuk menambah akun baru.</div>
      <?php else: ?>
        <form method="post" action="<?= base_url('admin/users/create') ?>">
          <?= csrf_field() ?>
          <div class="mb-3"><label class="form-label">Nama</label><input class="form-control-simran" name="name" value="<?= old('name') ?>" required maxlength="150"></div>
          <div class="mb-3"><label class="form-label">Username</label><input class="form-control-simran" name="username" value="<?= old('username') ?>" required minlength="4" maxlength="40" autocomplete="off"></div>
          <div class="mb-3">
            <label class="form-label">Level Akses</label>
            <select class="form-select-simran" name="role" required>
              <option value="operator">Operator</option>
              <option value="pimpinan">Pimpinan</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
          <div class="mb-3"><label class="form-label">Password Awal</label><input type="password" class="form-control-simran" name="password" minlength="10" required autocomplete="new-password"></div>
          <div class="mb-3">
            <label class="form-label">PIN Login Awal (6 Digit, Opsional)</label>
            <input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control-simran font-monospace text-center" name="pin" placeholder="Contoh: 123456 (kosongkan untuk acak)" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            <div class="form-text" style="font-size: 11px;">Biarkan kosong jika ingin sistem membuatkan 6 digit PIN secara acak otomatis.</div>
          </div>
          <button class="btn-simran btn-simran-primary w-100" type="submit">＋ Tambah Pengguna</button>
        </form>
      <?php endif; ?>
    </div>

    <div class="card-simran">
      <h3 class="h6 fw-bold">Pembagian Akses</h3>
      <div class="small text-muted d-grid gap-2">
        <div><b class="text-dark">Administrator</b><br>Semua fitur, pengaturan, backup, dan pengguna.</div>
        <div><b class="text-dark">Operator</b><br>Pagu, realisasi, impor Excel, IKPA, dan ekspor.</div>
        <div><b class="text-dark">Pimpinan</b><br>Dashboard dan rincian kegiatan saja.</div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
