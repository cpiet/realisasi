<?php
  $currentRole = strtolower((string)session()->get('role'));
  $canOperate = in_array($currentRole, ['admin', 'operator'], true);
  $isAdmin = $currentRole === 'admin';

  // Pastikan daftar tahun dan tahun terpilih selalu konsisten di semua halaman
  $fyModel = new \App\Models\FiscalYearModel();
  $navYears = $all_years ?? $allYears ?? $fyModel->orderBy('year', 'DESC')->findAll();
  $reqYearParam = service('request')->getGet('year');
  $navSelectedYear = $fyModel->determineSelectedYear($reqYearParam ? (int)$reqYearParam : null);
  $navYearVal = (int)($navSelectedYear['year'] ?? date('Y'));
  $yParam = '?year=' . $navYearVal;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token-name" content="<?= csrf_token() ?>">
  <meta name="csrf-token-value" content="<?= csrf_hash() ?>">
  <title><?= esc($title ?? 'SIMRAN — Divhumas Polri') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('css/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= base_url('css/simran.css?v=' . (@filemtime(FCPATH . 'css/simran.css') ?: '2.0')) ?>">
  <script>
    window.simranBaseUrl = <?= json_encode(rtrim(base_url(), '/')) ?>;
    window.simranCsrf = {
      name: <?= json_encode(csrf_token()) ?>,
      value: <?= json_encode(csrf_hash()) ?>
    };
    window.simranCurrentYear = <?= json_encode($navYearVal) ?>;
    window.simranSwitchYear = function (year) {
      if (!year) return;
      const currentUrl = new URL(window.location.href);
      currentUrl.searchParams.set('year', year);
      const switchEndpoint = (window.simranBaseUrl || '') + '/set-year/' + encodeURIComponent(year) + '?redirect=' + encodeURIComponent(currentUrl.toString());
      window.location.href = switchEndpoint;
    };
  </script>
  <script src="<?= base_url('js/simran-theme.js?v=' . (@filemtime(FCPATH . 'js/simran-theme.js') ?: '1.2')) ?>"></script>
</head>
<body>
  <!-- Top Navbar (macOS Sonoma / Apple HIG Aesthetic) -->
  <header class="simran-navbar">
    <div class="container-xl">
      <!-- Top Branding & Quick Actions Row -->
      <div class="topbar-row d-flex align-items-center justify-content-between gap-3">
        
        <!-- Brand & Satker Identity -->
        <div class="d-flex align-items-center gap-3">
          <a href="<?= base_url($yParam) ?>" class="simran-brand">
            <div class="simran-logo-badge">HP</div>
            <div>
              <div class="d-flex align-items-center">
                <span class="simran-title">SIMRAN</span>
                <span class="simran-version-chip">v1.0</span>
              </div>
              <div class="simran-subtitle">Divhumas Polri · Monitoring Realisasi &amp; IKPA</div>
            </div>
          </a>
        </div>

        <!-- Center macOS Cupertino Search Pill -->
        <div class="search-pill-wrapper d-none d-md-block">
          <span class="search-pill-icon"><i class="bi bi-search"></i></span>
          <input type="text" id="globalSearchInput" class="search-pill-input" placeholder="Cari kegiatan, akun, program..." autocomplete="off">
          <kbd class="search-kbd">⌘K</kbd>
          <div id="searchResultsBox" class="search-results-box" style="display: none;"></div>
        </div>

        <!-- Right Utilities & Profile -->
        <div class="topbar-actions">
          <!-- Theme Switcher Pill -->
          <button type="button" class="pill-btn pill-btn-bordered" id="theme-toggle-btn" title="Ganti Tema (Light/Dark)">
            <span id="theme-icon">🌙</span> <span id="theme-text" class="d-none d-sm-inline">Tema</span>
          </button>

          <!-- Year Selector Pill (Top Bar) -->
          <?php if (!empty($navYears)): ?>
            <div class="dropdown d-none d-sm-inline-block">
              <button class="pill-btn pill-btn-filled dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span>T.A. <?= esc($navYearVal) ?></span>
                <span class="text-warning">★</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; min-width: 140px;">
                <?php foreach ($navYears as $fyItem): ?>
                  <li>
                    <a class="dropdown-item <?= ($navYearVal == $fyItem['year']) ? 'active fw-bold' : '' ?>" href="javascript:void(0)" onclick="window.simranSwitchYear('<?= $fyItem['year'] ?>')">
                      T.A. <?= esc($fyItem['year']) ?> <?= !empty($fyItem['is_active']) ? '★' : '' ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <!-- User Session / Login Link -->
          <?php if (session()->get('is_logged_in')): 
            $userName = session()->get('name') ?? 'Admin';
            $userInitials = strtoupper(substr($userName, 0, 2));
          ?>
            <div class="dropdown">
              <button class="pill-btn pill-btn-bordered dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar-badge-circle"><?= esc($userInitials) ?></span>
                <span class="d-none d-md-inline" style="max-width: 130px; overflow: hidden; text-overflow: ellipsis; font-size: 12px; font-weight: 500;"><?= esc($userName) ?></span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 240px;">
                <li><span class="dropdown-item-text text-muted" style="font-size: 11.5px;">Login sebagai <b><?= esc(ucfirst($currentRole)) ?></b></span></li>
                <li><a class="dropdown-item" href="<?= base_url('account' . $yParam) ?>"><i class="bi bi-person me-2"></i> Profil &amp; Password</a></li>
                <?php if ($canOperate): ?>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item fw-semibold text-primary" href="<?= base_url('admin/pagu' . $yParam) ?>"><i class="bi bi-bank me-2"></i> Input Pagu DIPA &amp; Target</a></li>
                  <li><a class="dropdown-item" href="<?= base_url('admin/realisasi' . $yParam) ?>"><i class="bi bi-pencil-square me-2"></i> Input Realisasi</a></li>
                  <li><a class="dropdown-item" href="<?= base_url('admin/import' . $yParam) ?>"><i class="bi bi-file-earmark-excel me-2"></i> Import LRA Excel</a></li>
                  <li><a class="dropdown-item fw-semibold text-primary" href="<?= base_url('admin/rpa' . $yParam) ?>"><i class="bi bi-table me-2"></i> RPA &amp; Rencana Penarikan</a></li>
                  <li><a class="dropdown-item" href="<?= base_url('kegiatan/uraian' . $yParam) ?>"><i class="bi bi-card-checklist me-2"></i> Uraian Kegiatan (110 Giat)</a></li>
                  <li><a class="dropdown-item" href="<?= base_url('admin/ikpa' . $yParam) ?>"><i class="bi bi-speedometer2 me-2"></i> Kelola Nilai IKPA</a></li>
                <?php endif; ?>
                <?php if ($isAdmin): ?>
                  <li><a class="dropdown-item" href="<?= base_url('admin/users' . $yParam) ?>"><i class="bi bi-people me-2"></i> Kelola Pengguna</a></li>
                  <li><a class="dropdown-item" href="<?= base_url('admin/settings' . $yParam) ?>"><i class="bi bi-sliders me-2"></i> Pengaturan &amp; Backup</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i> Keluar (Logout)</a></li>
              </ul>
            </div>
          <?php else: ?>
            <a href="<?= base_url('login') ?>" class="btn-simran btn-simran-primary">
              <span class="d-none d-sm-inline">Masuk Admin</span>
              <span class="d-inline d-sm-none">Masuk</span>
            </a>
          <?php endif; ?>

          <!-- Mobile Menu Hamburger Button -->
          <button class="mobile-menu-btn d-lg-none" id="mobileMenuBtn" type="button" aria-controls="simranNavigation" aria-expanded="false" aria-label="Buka menu navigasi">
            <span></span><span></span><span></span>
          </button>
        </div>

      </div>

      <!-- Bottom Navigation Row (Segmented Controls) -->
      <div class="collapse d-lg-block" id="simranNavigation">
        
        <!-- Mobile Search (shown only when drawer opened on small screens) -->
        <div class="d-md-none mobile-search-box position-relative my-2">
          <span class="search-pill-icon"><i class="bi bi-search"></i></span>
          <input type="text" id="globalSearchInputMobile" class="search-pill-input" placeholder="Cari kegiatan, akun, program..." autocomplete="off">
        </div>

        <!-- Mobile Year Switcher (shown only on phones) -->
        <?php if (!empty($navYears)): ?>
          <div class="d-sm-none d-flex align-items-center justify-content-between p-2 mb-2 rounded-3" style="background: var(--apple-well); border: 1px solid var(--apple-border);">
            <span class="small fw-semibold text-secondary">Tahun Anggaran:</span>
            <select class="form-select form-select-sm w-auto fw-bold" style="border-radius: 8px;" onchange="window.simranSwitchYear(this.value)">
              <?php foreach ($navYears as $fyItem): ?>
                <option value="<?= $fyItem['year'] ?>" <?= ($navYearVal == $fyItem['year']) ? 'selected' : '' ?>>
                  T.A. <?= esc($fyItem['year']) ?> <?= !empty($fyItem['is_active']) ? '★' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <div class="nav-row-simran">
          <nav class="nav-pills-simran" aria-label="Navigasi Utama">
            <!-- 1. Dashboard -->
            <a href="<?= base_url($yParam) ?>" class="nav-link <?= uri_string() === '' || uri_string() === 'dashboard' ? 'active' : '' ?>">
              <i class="bi bi-speedometer2"></i>
              <span>Dashboard</span>
            </a>

            <!-- 2. Kegiatan (Dropdown) -->
            <?php if ($canOperate): ?>
              <?php $isKegiatanActive = str_starts_with(uri_string(), 'kegiatan'); ?>
              <div class="dropdown">
                <button class="dropdown-toggle <?= $isKegiatanActive ? 'active' : '' ?>" type="button" data-simran-dropdown="true" aria-expanded="false">
                  <i class="bi bi-card-checklist"></i>
                  <span>Kegiatan</span>
                </button>
                <ul class="dropdown-menu shadow-sm">
                  <li>
                    <a class="dropdown-item <?= uri_string() === 'kegiatan/uraian' ? 'active' : '' ?>" href="<?= base_url('kegiatan/uraian' . $yParam) ?>">
                      <div class="fw-semibold"><i class="bi bi-card-checklist me-2 text-success"></i> Uraian Kegiatan (110 Giat)</div>
                      <div class="small text-muted ps-4">Katalog operasional RKAKL &amp; RAB Biro</div>
                    </a>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <a class="dropdown-item <?= uri_string() === 'kegiatan' ? 'active' : '' ?>" href="<?= base_url('kegiatan' . $yParam) ?>">
                      <div class="fw-semibold"><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i> Rincian Akun &amp; Realisasi</div>
                      <div class="small text-muted ps-4">Plafon &amp; serapan belanja per akun</div>
                    </a>
                  </li>
                </ul>
              </div>

              <!-- 3. Perencanaan (Dropdown) -->
              <?php $isPerencanaanActive = str_starts_with(uri_string(), 'admin/pagu') || str_starts_with(uri_string(), 'admin/rpa'); ?>
              <div class="dropdown">
                <button class="dropdown-toggle <?= $isPerencanaanActive ? 'active' : '' ?>" type="button" data-simran-dropdown="true" aria-expanded="false">
                  <i class="bi bi-bank"></i>
                  <span>Perencanaan</span>
                </button>
                <ul class="dropdown-menu shadow-sm">
                  <li>
                    <a class="dropdown-item <?= str_starts_with(uri_string(), 'admin/pagu') ? 'active' : '' ?>" href="<?= base_url('admin/pagu' . $yParam) ?>">
                      <div class="fw-semibold"><i class="bi bi-bank me-2 text-primary"></i> Pagu DIPA &amp; Target RPD</div>
                      <div class="small text-muted ps-4">Plafon Belanja 51, 52, 53 &amp; target TW</div>
                    </a>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <a class="dropdown-item <?= str_starts_with(uri_string(), 'admin/rpa') ? 'active' : '' ?>" href="<?= base_url('admin/rpa' . $yParam) ?>">
                      <div class="fw-semibold"><i class="bi bi-table me-2 text-warning"></i> Rencana Penarikan (RPA)</div>
                      <div class="small text-muted ps-4">Spreadsheet bulanan &amp; impor dokumen DIPA</div>
                    </a>
                  </li>
                </ul>
              </div>

              <!-- 4. Realisasi (Dropdown) -->
              <?php $isRealisasiActive = str_starts_with(uri_string(), 'admin/realisasi') || str_starts_with(uri_string(), 'admin/import') || str_starts_with(uri_string(), 'admin/ikpa'); ?>
              <div class="dropdown">
                <button class="dropdown-toggle <?= $isRealisasiActive ? 'active' : '' ?>" type="button" data-simran-dropdown="true" aria-expanded="false">
                  <i class="bi bi-graph-up-arrow"></i>
                  <span>Realisasi</span>
                </button>
                <ul class="dropdown-menu shadow-sm">
                  <li>
                    <a class="dropdown-item <?= str_starts_with(uri_string(), 'admin/realisasi') ? 'active' : '' ?>" href="<?= base_url('admin/realisasi' . $yParam) ?>">
                      <div class="fw-semibold"><i class="bi bi-pencil-square me-2 text-info"></i> Input Cepat Realisasi SP2D</div>
                      <div class="small text-muted ps-4">Pengisian matriks bulanan otomatis tersimpan</div>
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item <?= str_starts_with(uri_string(), 'admin/import') ? 'active' : '' ?>" href="<?= base_url('admin/import' . $yParam) ?>">
                      <div class="fw-semibold"><i class="bi bi-file-earmark-excel me-2 text-success"></i> Import File LRA Excel</div>
                      <div class="small text-muted ps-4">Validasi &amp; upsert laporan LRA Satker</div>
                    </a>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <a class="dropdown-item <?= str_starts_with(uri_string(), 'admin/ikpa') ? 'active' : '' ?>" href="<?= base_url('admin/ikpa' . $yParam) ?>">
                      <div class="fw-semibold"><i class="bi bi-speedometer2 me-2 text-danger"></i> Kinerja IKPA Kemenkeu</div>
                      <div class="small text-muted ps-4">Scorecard 7 indikator &amp; dispensasi SPM</div>
                    </a>
                  </li>
                </ul>
              </div>
            <?php endif; ?>

            <!-- 5. Standar Biaya (SBM) -->
            <?php if ($isAdmin): ?>
              <?php $isSbmActive = uri_string() === 'sbm' || str_starts_with(uri_string(), 'sbm/') || str_starts_with(uri_string(), 'admin/sbm'); ?>
              <div class="dropdown">
                <button class="dropdown-toggle <?= $isSbmActive ? 'active' : '' ?>" type="button" data-simran-dropdown="true" aria-expanded="false">
                  <i class="bi bi-book"></i>
                  <span>SBM</span>
                </button>
                <ul class="dropdown-menu shadow-sm">
                  <li>
                    <a class="dropdown-item <?= (uri_string() === 'sbm' || str_starts_with(uri_string(), 'sbm/')) ? 'active' : '' ?>" href="<?= base_url('sbm' . $yParam) ?>">
                      <div class="fw-semibold"><i class="bi bi-book me-2 text-primary"></i> Standar Biaya Masukan (SBM)</div>
                      <div class="small text-muted ps-4">Pencarian tarif &amp; batas tertinggi biaya</div>
                    </a>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <a class="dropdown-item <?= str_starts_with(uri_string(), 'admin/sbm') ? 'active' : '' ?>" href="<?= base_url('admin/sbm' . $yParam) ?>">
                      <div class="fw-semibold"><i class="bi bi-gear-wide-connected me-2 text-secondary"></i> Kelola Regulasi SBM</div>
                      <div class="small text-muted ps-4">Input regulasi PMK &amp; patokan tarif baru</div>
                    </a>
                  </li>
                </ul>
              </div>
            <?php else: ?>
              <a href="<?= base_url('sbm' . $yParam) ?>" class="nav-link <?= uri_string() === 'sbm' || str_starts_with(uri_string(), 'sbm/') ? 'active' : '' ?>">
                <i class="bi bi-book"></i>
                <span>Standar Biaya SBM</span>
              </a>
            <?php endif; ?>

            <!-- 6. Sistem / Pengaturan (Khusus Admin) -->
            <?php if ($isAdmin): ?>
              <?php $isSistemActive = str_starts_with(uri_string(), 'admin/users') || str_starts_with(uri_string(), 'admin/settings'); ?>
              <div class="dropdown">
                <button class="dropdown-toggle <?= $isSistemActive ? 'active' : '' ?>" type="button" data-simran-dropdown="true" aria-expanded="false">
                  <i class="bi bi-gear"></i>
                  <span>Sistem</span>
                </button>
                <ul class="dropdown-menu shadow-sm dropdown-menu-end">
                  <li>
                    <a class="dropdown-item <?= str_starts_with(uri_string(), 'admin/users') ? 'active' : '' ?>" href="<?= base_url('admin/users' . $yParam) ?>">
                      <div class="fw-semibold"><i class="bi bi-people me-2 text-primary"></i> Kelola Pengguna &amp; PIN</div>
                      <div class="small text-muted ps-4">Daftar pengguna &amp; PIN login 6 digit</div>
                    </a>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <a class="dropdown-item <?= str_starts_with(uri_string(), 'admin/settings') ? 'active' : '' ?>" href="<?= base_url('admin/settings' . $yParam) ?>">
                      <div class="fw-semibold"><i class="bi bi-sliders me-2 text-secondary"></i> Pengaturan &amp; Backup</div>
                      <div class="small text-muted ps-4">Multi-tahun &amp; unduh backup database</div>
                    </a>
                  </li>
                </ul>
              </div>
            <?php endif; ?>
          </nav>

          <!-- Desktop Year Switcher in Nav Bar -->
          <?php if (!empty($navYears)): ?>
            <div class="year-switcher-pill d-none d-lg-inline-flex">
              <span class="text-secondary" style="font-size: 11.5px;">T.A.:</span>
              <select id="globalNavYearSelect" onchange="window.simranSwitchYear(this.value)">
                <?php foreach ($navYears as $fyItem): ?>
                  <option value="<?= $fyItem['year'] ?>" <?= ($navYearVal == $fyItem['year']) ? 'selected' : '' ?>>
                    <?= esc($fyItem['name']) ?> <?= !empty($fyItem['is_active']) ? '★' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </header>

  <!-- Flash Messages & Main App Content -->
  <main class="<?= !empty($isFluid) ? 'container-fluid px-3 px-xl-4' : 'container-xl' ?> app-main flex-grow-1">
    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" style="border-radius: 12px; background: var(--apple-green-tint); color: var(--apple-green); border: 1px solid var(--apple-green-border) !important;" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" style="border-radius: 12px; background: var(--apple-red-tint); color: var(--apple-red); border: 1px solid var(--apple-red-border) !important;" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?= $this->renderSection('content') ?>
  </main>

  <!-- Activity Detail Modal (macOS Cupertino Rounded) -->
  <div class="modal fade" id="activityDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content card-simran p-0 border-0" style="border-radius: 20px; overflow: hidden; box-shadow: var(--shadow-dropdown);">
        <div class="modal-header border-bottom p-3 px-4" style="background: var(--apple-card-subtle);">
          <h5 class="modal-title h6 fw-bold mb-0" id="modalDetailTitle">Detail Kegiatan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4" id="modalDetailBody">
          <!-- Dynamic Content Loaded by JS -->
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <div class="container-xl">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <strong style="color: var(--apple-charcoal);">SIMRAN v1.0</strong> — Sistem Monitoring Realisasi Anggaran &amp; IKPA
        </div>
        <div>
          Divisi Hubungan Masyarakat Kepolisian Negara Republik Indonesia
        </div>
      </div>
    </div>
  </footer>

  <script src="<?= base_url('js/bootstrap.bundle.min.js') ?>"></script>
  <script src="<?= base_url('js/chart.umd.min.js') ?>"></script>
  <script src="<?= base_url('js/simran-dashboard.js?v=' . (@filemtime(FCPATH . 'js/simran-dashboard.js') ?: '1.3')) ?>"></script>
  
  <script>
    // Handlers untuk Navigasi, Dropdown & Keyboard Shortcut ⌘K
    document.addEventListener('DOMContentLoaded', function () {
      function closeNavDropdown(dropdown) {
        const menu = dropdown.querySelector('.dropdown-menu');
        const button = dropdown.querySelector('[data-simran-dropdown]');
        dropdown.classList.remove('show');
        if (menu) menu.classList.remove('show');
        if (button) {
          button.classList.remove('show');
          button.setAttribute('aria-expanded', 'false');
          button.blur();
        }
      }

      document.querySelectorAll('.nav-pills-simran [data-simran-dropdown]').forEach(function (toggleBtn) {
        toggleBtn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          const dropdown = toggleBtn.closest('.dropdown');
          const willOpen = !dropdown.classList.contains('show');

          document.querySelectorAll('.nav-pills-simran .dropdown.show').forEach(function (other) {
            if (other !== dropdown) closeNavDropdown(other);
          });

          dropdown.classList.toggle('show', willOpen);
          toggleBtn.classList.toggle('show', willOpen);
          toggleBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
          const menu = dropdown.querySelector('.dropdown-menu');
          if (menu) menu.classList.toggle('show', willOpen);
        });
      });

      // Tutup dropdown saat klik di luar
      document.addEventListener('click', function (e) {
        if (!e.target.closest('.nav-pills-simran .dropdown')) {
          document.querySelectorAll('.nav-pills-simran .dropdown.show').forEach(closeNavDropdown);
        }
      });

      // Escape key handler
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          document.querySelectorAll('.nav-pills-simran .dropdown.show').forEach(closeNavDropdown);
          const searchBox = document.getElementById('searchResultsBox');
          if (searchBox) searchBox.style.display = 'none';
        }

        // ⌘K or Ctrl+K shortcut to focus search
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
          e.preventDefault();
          const searchInput = document.getElementById('globalSearchInput') || document.getElementById('globalSearchInputMobile');
          if (searchInput) {
            searchInput.focus();
            searchInput.select();
          }
        }
      });

      // Hamburger Mobile Menu Toggle (#simranNavigation)
      const mobileBtn = document.getElementById('mobileMenuBtn') || document.querySelector('.mobile-menu-btn');
      const navCollapse = document.getElementById('simranNavigation');
      if (mobileBtn && navCollapse) {
        mobileBtn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          const isOpen = navCollapse.classList.contains('show');
          if (isOpen) {
            navCollapse.classList.remove('show');
            mobileBtn.setAttribute('aria-expanded', 'false');
          } else {
            navCollapse.classList.add('show');
            mobileBtn.setAttribute('aria-expanded', 'true');
          }
        });
      }

      // Sync mobile search to same search function
      const mobileSearchInput = document.getElementById('globalSearchInputMobile');
      if (mobileSearchInput) {
        mobileSearchInput.addEventListener('input', function () {
          const val = this.value;
          const desktopInput = document.getElementById('globalSearchInput');
          if (desktopInput) {
            desktopInput.value = val;
            desktopInput.dispatchEvent(new Event('input'));
          }
        });
      }
    });
  </script>

  <?= $this->renderSection('scripts') ?>
</body>
</html>
