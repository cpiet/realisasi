<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row justify-content-center py-5">
  <div class="col-md-5 col-lg-4">
    <div class="card-simran shadow-md border-0" style="border-radius: 16px;">
      <div class="text-center mb-4">
        <div class="simran-logo-badge mx-auto mb-2" style="width: 52px; height: 52px; font-size: 22px; font-weight: 700; background: linear-gradient(135deg, #3b5bdb, #2242b5); box-shadow: 0 4px 14px rgba(59,91,219,0.35);">HP</div>
        <h2 class="h5 fw-bold mb-1 text-dark">Login Administrator</h2>
        <div class="text-muted" style="font-size: 13px;">SIMRAN — Divisi Humas Polri</div>
      </div>

      <!-- Tab Switcher Metode Login -->
      <div class="d-flex p-1 bg-light rounded-pill mb-4 border" style="font-size: 13px;">
        <button type="button" class="btn btn-sm flex-fill rounded-pill py-1 fw-semibold transition-all btn-primary" id="btnTabPassword" onclick="switchLoginMethod('password')">
          🔑 Password
        </button>
        <button type="button" class="btn btn-sm flex-fill rounded-pill py-1 fw-semibold transition-all btn-light text-muted" id="btnTabPin" onclick="switchLoginMethod('pin')">
          🔢 PIN 6 Digit
        </button>
      </div>

      <!-- Form Login Utama -->
      <form method="post" action="<?= base_url('auth/process') ?>" id="loginForm">
        <?= csrf_field() ?>
        <input type="hidden" name="login_type" id="login_type" value="password">

        <!-- Panel Form Password -->
        <div id="panelPassword">
          <div class="mb-3">
            <label for="username" class="form-label text-muted" style="font-size: 12.5px; font-weight: 500;">Username</label>
            <input type="text" class="form-control-simran" id="username" name="username" value="<?= old('username') ?>" autofocus placeholder="Masukkan username admin">
          </div>

          <div class="mb-4">
            <label for="password" class="form-label text-muted" style="font-size: 12.5px; font-weight: 500;">Password</label>
            <div class="position-relative">
              <input type="password" class="form-control-simran pe-5" id="password" name="password" placeholder="Masukkan password">
              <button type="button" class="btn btn-link text-muted position-absolute top-50 end-0 translate-middle-y me-2 p-1 text-decoration-none" id="togglePasswordBtn" onclick="togglePasswordVisibility()" title="Tampilkan / Sembunyikan Password" style="z-index: 5;">
                <i class="bi bi-eye" id="togglePasswordIcon" style="font-size: 1.15rem;"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn-simran btn-simran-primary w-100 justify-content-center py-2 fs-6 shadow-sm" style="border-radius: 10px;">
            Masuk ke Sistem
          </button>
        </div>

        <!-- Panel Form PIN 6 Digit -->
        <div id="panelPin" style="display: none;">
          <div class="mb-3 text-center">
            <label for="pin" class="form-label text-muted fw-semibold mb-2" style="font-size: 13px;">Masukkan 6 Digit PIN Akun</label>
            
            <div class="position-relative mx-auto" style="max-width: 260px;">
              <input type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                     class="form-control form-control-lg text-center font-monospace fw-bold shadow-sm"
                     id="pin" name="pin" placeholder="••••••" autocomplete="one-time-code"
                     style="letter-spacing: 10px; font-size: 1.55rem; border-radius: 12px; border: 1.5px solid #ced4da; padding-left: 18px;"
                     oninput="handlePinInput(this)">
              <button type="button" class="btn btn-sm btn-link text-muted position-absolute top-50 end-0 translate-middle-y me-2 p-1 text-decoration-none"
                      onclick="togglePinVisibility()" title="Lihat PIN" style="z-index: 5;">
                <i class="bi bi-eye" id="togglePinIcon" style="font-size: 1.1rem;"></i>
              </button>
            </div>

            <div class="d-flex justify-content-center gap-2 mt-2">
              <button class="btn btn-sm btn-outline-secondary py-1 px-2" type="button" onclick="pastePinFromClipboard()" style="font-size: 12px;">
                <i class="bi bi-clipboard me-1"></i> Tempel PIN
              </button>
            </div>

            <div class="text-muted mt-3 text-start px-2 py-2 rounded bg-light border" style="font-size: 11.5px; line-height: 1.45;">
              <i class="bi bi-shield-check text-primary me-1"></i>
              Gunakan <strong>PIN 6 Digit</strong> yang telah didaftarkan dari menu <strong>Manajemen Pengguna</strong>. Masuk instan tanpa perlu password.
            </div>
          </div>

          <button type="submit" id="btnSubmitPin" class="btn-simran btn-simran-primary w-100 justify-content-center py-2 fs-6 shadow-sm mt-3" style="border-radius: 10px;">
            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk dengan PIN
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Fungsi Toggle Show / Hide Password
function togglePasswordVisibility() {
  const pwdInput = document.getElementById('password');
  const icon = document.getElementById('togglePasswordIcon');
  
  if (pwdInput.type === 'password') {
    pwdInput.type = 'text';
    icon.classList.remove('bi-eye');
    icon.classList.add('bi-eye-slash');
  } else {
    pwdInput.type = 'password';
    icon.classList.remove('bi-eye-slash');
    icon.classList.add('bi-eye');
  }
}

// Fungsi Toggle Show / Hide PIN
function togglePinVisibility() {
  const pinInput = document.getElementById('pin');
  const icon = document.getElementById('togglePinIcon');
  
  if (pinInput.type === 'password') {
    pinInput.type = 'text';
    icon.classList.remove('bi-eye');
    icon.classList.add('bi-eye-slash');
  } else {
    pinInput.type = 'password';
    icon.classList.remove('bi-eye-slash');
    icon.classList.add('bi-eye');
  }
}

// Handler input PIN: hanya izinkan digit & auto submit jika 6 digit
let pinAutoSubmitTimer = null;
function handlePinInput(input) {
  // Hanya angka
  input.value = input.value.replace(/[^0-9]/g, '');
  if (pinAutoSubmitTimer) clearTimeout(pinAutoSubmitTimer);

  if (input.value.length === 6) {
    pinAutoSubmitTimer = setTimeout(() => {
      document.getElementById('loginForm').submit();
    }, 400);
  }
}

// Fungsi Switch Metode Login (Password vs PIN)
function switchLoginMethod(method) {
  const btnPass = document.getElementById('btnTabPassword');
  const btnPin = document.getElementById('btnTabPin');
  const panelPass = document.getElementById('panelPassword');
  const panelPin = document.getElementById('panelPin');
  const loginType = document.getElementById('login_type');
  const userField = document.getElementById('username');
  const passField = document.getElementById('password');
  const pinField = document.getElementById('pin');

  if (method === 'pin') {
    loginType.value = 'pin';
    btnPin.className = 'btn btn-sm flex-fill rounded-pill py-1 fw-semibold transition-all btn-primary';
    btnPass.className = 'btn btn-sm flex-fill rounded-pill py-1 fw-semibold transition-all btn-light text-muted';
    panelPass.style.display = 'none';
    panelPin.style.display = 'block';

    userField.removeAttribute('required');
    passField.removeAttribute('required');
    pinField.setAttribute('required', 'required');
    setTimeout(() => { pinField.focus(); }, 100);
  } else {
    loginType.value = 'password';
    btnPass.className = 'btn btn-sm flex-fill rounded-pill py-1 fw-semibold transition-all btn-primary';
    btnPin.className = 'btn btn-sm flex-fill rounded-pill py-1 fw-semibold transition-all btn-light text-muted';
    panelPass.style.display = 'block';
    panelPin.style.display = 'none';

    pinField.removeAttribute('required');
    userField.setAttribute('required', 'required');
    passField.setAttribute('required', 'required');
    setTimeout(() => { userField.focus(); }, 100);
  }
}

// Fungsi Paste PIN dari Clipboard
function pastePinFromClipboard() {
  if (navigator.clipboard && navigator.clipboard.readText) {
    navigator.clipboard.readText().then(text => {
      const clean = text.trim().replace(/[^0-9]/g, '').substring(0, 6);
      if (clean.length > 0) {
        const pinField = document.getElementById('pin');
        pinField.value = clean;
        handlePinInput(pinField);
      } else {
        alert('Clipboard tidak mengandung angka PIN valid.');
      }
    }).catch(err => {
      alert('Tidak dapat membaca clipboard: ' + err);
    });
  } else {
    alert('Browser tidak mengizinkan akses clipboard otomatis. Silakan tempel secara manual (Ctrl+V).');
  }
}
</script>

<?= $this->endSection() ?>
