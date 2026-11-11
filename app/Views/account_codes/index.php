<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-xl py-4">

  <!-- Header Banner -->
  <div class="card-simran p-4 mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.08) 0%, rgba(30, 41, 59, 0.02) 100%); border-radius: 16px;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
          <span class="badge bg-primary px-3 py-2 rounded-pill font-monospace" style="font-size: 0.85rem;">
            <i class="bi bi-journal-code me-1"></i> MODUL BELAJAR RKAKL TA 2027
          </span>
          <span class="badge bg-dark bg-opacity-75 text-white px-2 py-1 rounded-pill small">
            Satker Divhumas Polri (642321)
          </span>
          <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill small">
            <?= esc($meta['version'] ?? 'Versi Revisi [Rev]') ?>
          </span>
        </div>
        <h2 class="fw-bold mb-1" style="letter-spacing: -0.5px;">Kamus Kode Akun Belanja</h2>
        <p class="text-muted mb-0" style="max-width: 720px; font-size: 0.95rem;">
          Matriks, penjelasan fungsi peruntukan, batasan pembeda, kesalahan umum, dan contoh kasus riil belanja di lingkungan Divhumas Polri (Biro Penmas, PID, Multimedia, dan Bagrenmin).
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="#tab-kuis" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-2" onclick="switchMainTab('tab-kuis')">
          <i class="bi bi-mortarboard-fill me-1"></i> Latihan Soal
        </a>
        <a href="#tab-kasus" class="btn btn-primary btn-sm rounded-pill px-3 py-2 shadow-sm" onclick="switchMainTab('tab-kasus')">
          <i class="bi bi-building-check me-1"></i> 15 Kasus Divhumas
        </a>
      </div>
    </div>

    <!-- Quick Search Bar (Ketik Kode Akun) -->
    <div class="mt-4 pt-3 border-top border-secondary border-opacity-10">
      <div class="row g-2 align-items-center">
        <div class="col-lg-8 col-md-7">
          <div class="position-relative">
            <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted" style="font-size: 1.15rem;">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" id="accountQuickSearch" class="form-control form-control-lg ps-5 rounded-pill shadow-none" 
                   style="border: 2px solid rgba(37, 99, 235, 0.25); font-size: 1rem; background: var(--bs-card-bg, #fff);" 
                   placeholder="Ketik 6 digit kode akun (misal: 521211, 524114, 532111) atau kata kunci (bahan, honor, sewa, kamera, seminar)..."
                   value="<?= esc($initialCode ?: $searchQuery) ?>" autocomplete="off">
            <button type="button" id="clearSearchBtn" class="btn btn-link text-muted position-absolute top-50 end-0 translate-middle-y pe-3" style="display: none; text-decoration: none;">
              <i class="bi bi-x-circle-fill"></i>
            </button>
          </div>
        </div>

        <div class="col-lg-4 col-md-5">
          <div class="d-flex gap-2">
            <select id="groupFilterSelect" class="form-select form-select-lg rounded-pill shadow-none" style="font-size: 0.9rem;">
              <option value="all">Semua Kelompok Belanja (46 Akun)</option>
              <?php foreach ($groups as $g): ?>
                <option value="<?= esc($g['code']) ?>" <?= $selectedGroup === $g['code'] ? 'selected' : '' ?>>
                  <?= esc($g['code']) ?> — <?= esc($g['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Quick Chips Filter -->
      <div class="d-flex flex-wrap gap-1 mt-3 align-items-center">
        <span class="small text-muted me-2"><i class="bi bi-funnel me-1"></i>Filter Cepat:</span>
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-3 group-chip active" data-group="all">Semua</button>
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-3 group-chip" data-group="5211">5211 Operasional</button>
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-3 group-chip" data-group="5212">5212 Non-Operasional</button>
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-3 group-chip" data-group="5218">5218 Persediaan</button>
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-3 group-chip" data-group="5221">5221 Jasa</button>
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-3 group-chip" data-group="5231">5231 Pemeliharaan</button>
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-3 group-chip" data-group="5241">5241 Perjalanan Dinas</button>
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-3 group-chip" data-group="53">53 Belanja Modal</button>
      </div>
    </div>
  </div>

  <!-- Focused Interactive Account Card (Auto Show when exact search or selected) -->
  <div id="focusedAccountCard" class="card-simran p-4 mb-4 border-primary border-opacity-25 shadow-sm" style="display: <?= $selectedAccount ? 'block' : 'none' ?>; border-radius: 16px; background: var(--bs-card-bg, #fff);">
    <?php if ($selectedAccount): ?>
      <?= view('account_codes/_card_detail', ['account' => $selectedAccount]) ?>
    <?php endif; ?>
  </div>

  <!-- Main Navigation Tabs -->
  <ul class="nav nav-pills mb-4 gap-2 flex-wrap border-bottom pb-3" id="akunMainTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active rounded-pill px-4 py-2" id="tab-direktori-btn" data-bs-toggle="pill" data-bs-target="#tab-direktori" type="button" role="tab">
        <i class="bi bi-grid me-2"></i> Direktori Kode Akun (<span id="countDisplay"><?= count($accounts) ?></span>)
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link rounded-pill px-4 py-2" id="tab-matriks-btn" data-bs-toggle="pill" data-bs-target="#tab-matriks" type="button" role="tab">
        <i class="bi bi-table me-2"></i> Matriks 46 Akun
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link rounded-pill px-4 py-2" id="tab-kesalahan-btn" data-bs-toggle="pill" data-bs-target="#tab-kesalahan" type="button" role="tab">
        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Kesalahan Sering Terjadi
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link rounded-pill px-4 py-2" id="tab-kasus-btn" data-bs-toggle="pill" data-bs-target="#tab-kasus" type="button" role="tab">
        <i class="bi bi-lightbulb-fill text-info me-2"></i> 15 Studi Kasus Divhumas
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link rounded-pill px-4 py-2" id="tab-kuis-btn" data-bs-toggle="pill" data-bs-target="#tab-kuis" type="button" role="tab">
        <i class="bi bi-question-circle-fill text-success me-2"></i> Latihan Soal &amp; Kuis
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link rounded-pill px-4 py-2" id="tab-panduan-btn" data-bs-toggle="pill" data-bs-target="#tab-panduan" type="button" role="tab">
        <i class="bi bi-info-circle me-2"></i> Cara Membaca &amp; Regulasi
      </button>
    </li>
  </ul>

  <!-- Tab Contents -->
  <div class="tab-content" id="akunTabContent">

    <!-- 1. Direktori Kode Akun (Card Grid) -->
    <div class="tab-pane fade show active" id="tab-direktori" role="tabpanel">
      <div id="accountsGrid" class="row g-3">
        <?php foreach ($accounts as $acc): ?>
          <div class="col-xl-4 col-lg-6 col-md-6 account-item" 
               data-kode="<?= esc($acc['kode']) ?>" 
               data-uraian="<?= esc(strtolower($acc['uraian'])) ?>" 
               data-kelompok="<?= esc($acc['kelompok']) ?>" 
               data-corpus="<?= esc(strtolower($acc['kode'] . ' ' . $acc['uraian'] . ' ' . $acc['kelompok'] . ' ' . $acc['penjelasan'] . ' ' . $acc['contoh_divhumas'])) ?>">
            <div class="card-simran p-3 h-100 d-flex flex-column justify-content-between border transition-all hover-shadow" 
                 style="border-radius: 14px; cursor: pointer;" 
                 onclick="selectAccount('<?= esc($acc['kode']) ?>')">
              <div>
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                  <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 font-monospace fw-bold" style="font-size: 1rem;">
                    <?= esc($acc['kode']) ?>
                  </span>
                  <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1" style="font-size: 0.72rem;">
                    <?= esc($acc['kelompok']) ?>
                  </span>
                </div>
                <h5 class="fw-bold mb-2 text-primary text-truncate-2" style="font-size: 1.05rem;">
                  <?= esc($acc['uraian']) ?>
                </h5>
                <p class="text-muted small mb-3 text-truncate-3" style="font-size: 0.84rem; line-height: 1.45;">
                  <?= esc($acc['penjelasan']) ?>
                </p>
              </div>

              <div class="pt-2 border-top border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                <span class="badge bg-light text-dark border small" style="font-size: 0.7rem;">
                  <i class="bi bi-shield-check me-1 text-success"></i><?= esc($acc['status']) ?>
                </span>
                <span class="text-primary small fw-semibold">
                  Lihat Detail <i class="bi bi-arrow-right-short"></i>
                </span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div id="noResultsAlert" class="card-simran text-center py-5 my-4" style="display: none; border-radius: 16px;">
        <i class="bi bi-search text-muted display-4 mb-3"></i>
        <h5 class="fw-bold">Tidak ada kode akun yang cocok</h5>
        <p class="text-muted small mb-3">Coba gunakan kata kunci lain seperti <em>bahan, honor, sewa, listrik, perjalanan</em>, atau pilih kelompok belanja di atas.</p>
        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-4" onclick="resetFilters()">Reset Pencarian</button>
      </div>
    </div>

    <!-- 2. Matriks 46 Akun Ringkas -->
    <div class="tab-pane fade" id="tab-matriks" role="tabpanel">
      <div class="card-simran p-0 border shadow-sm overflow-hidden" style="border-radius: 14px;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="matrixTable">
            <thead class="table-light">
              <tr style="font-size: 0.85rem; letter-spacing: 0.5px;" class="text-uppercase text-muted">
                <th class="ps-4" style="width: 60px;">No</th>
                <th style="width: 120px;">Kode</th>
                <th>Uraian Akun</th>
                <th>Kelompok</th>
                <th>Status &amp; Verifikasi</th>
                <th class="text-end pe-4" style="width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody style="font-size: 0.9rem;">
              <?php foreach ($accounts as $acc): ?>
                <tr class="matrix-row" data-kode="<?= esc($acc['kode']) ?>" data-corpus="<?= esc(strtolower($acc['kode'] . ' ' . $acc['uraian'] . ' ' . $acc['kelompok'])) ?>">
                  <td class="ps-4 text-muted fw-semibold"><?= esc($acc['no']) ?></td>
                  <td>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace fw-bold px-2 py-1">
                      <?= esc($acc['kode']) ?>
                    </span>
                  </td>
                  <td class="fw-semibold text-dark">
                    <?= esc($acc['uraian']) ?>
                  </td>
                  <td>
                    <span class="badge bg-light text-secondary border">
                      <?= esc($acc['kelompok']) ?>
                    </span>
                  </td>
                  <td>
                    <?php if (str_contains($acc['status'], 'Tidak digunakan')): ?>
                      <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><?= esc($acc['status']) ?></span>
                    <?php elseif (str_contains($acc['status'], '[Rev]')): ?>
                      <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-25"><?= esc($acc['status']) ?></span>
                    <?php elseif (str_contains($acc['status'], '[Verifikasi]')): ?>
                      <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25"><?= esc($acc['status']) ?></span>
                    <?php else: ?>
                      <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><?= esc($acc['status']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end pe-4">
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="selectAccount('<?= esc($acc['kode']) ?>')">
                      <i class="bi bi-eye me-1"></i> Rincian
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 3. Kesalahan yang Paling Sering Terjadi (Bagian D) -->
    <div class="tab-pane fade" id="tab-kesalahan" role="tabpanel">
      <div class="alert alert-warning border-0 rounded-4 d-flex align-items-center gap-3 p-3 mb-4 shadow-sm">
        <i class="bi bi-shield-exclamation text-warning fs-3"></i>
        <div>
          <div class="fw-bold">Peringatan Audit &amp; Kesalahan Penganggaran RKAKL</div>
          <div class="small text-dark text-opacity-75">Daftar ketidaksesuaian pemilihan kode akun belanja yang paling sering menjadi temuan pemeriksaan atau salah pembebanan.</div>
        </div>
      </div>

      <div class="card-simran p-0 border shadow-sm overflow-hidden" style="border-radius: 14px;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr style="font-size: 0.85rem;" class="text-uppercase text-muted">
                <th class="ps-4" style="width: 200px;">Topik Perbandingan</th>
                <th>Kesalahan Umum</th>
                <th style="width: 260px;">Seharusnya</th>
                <th class="pe-4" style="width: 220px;">Dasar / Catatan</th>
              </tr>
            </thead>
            <tbody style="font-size: 0.9rem;">
              <?php foreach ($frequentMistakes as $m): ?>
                <tr>
                  <td class="ps-4 fw-bold text-primary">
                    <?= esc($m['topik']) ?>
                  </td>
                  <td class="text-danger">
                    <i class="bi bi-x-circle me-1"></i> <?= esc($m['kesalahan_umum']) ?>
                  </td>
                  <td class="text-success fw-semibold">
                    <i class="bi bi-check-circle me-1"></i> <?= esc($m['seharusnya']) ?>
                  </td>
                  <td class="pe-4 small text-muted">
                    <i class="bi bi-info-circle me-1"></i> <?= esc($m['catatan']) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- 4. 15 Studi Kasus Riil Divhumas (Bagian E) -->
    <div class="tab-pane fade" id="tab-kasus" role="tabpanel">
      <div class="row g-3">
        <?php foreach ($divhumasCases as $c): ?>
          <div class="col-lg-6">
            <div class="card-simran p-4 h-100 border shadow-sm d-flex flex-column justify-content-between" style="border-radius: 14px;">
              <div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 font-monospace">
                    Kasus #<?= esc($c['no']) ?>
                  </span>
                  <span class="small text-muted"><i class="bi bi-building me-1"></i>Divhumas Polri</span>
                </div>
                <h6 class="fw-bold mb-3" style="line-height: 1.5; color: var(--bs-body-color);">
                  <?= esc($c['situasi']) ?>
                </h6>
              </div>

              <div class="mt-3 pt-3 border-top">
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill w-100 mb-2" onclick="toggleCaseReveal(this)">
                  <i class="bi bi-eye me-1"></i> Tampilkan Akun yang Tepat &amp; Alasan
                </button>
                <div class="case-reveal-box p-3 rounded-3" style="display: none; background: rgba(16, 185, 129, 0.08); border-left: 4px solid #10b981;">
                  <div class="fw-bold text-success mb-1" style="font-size: 0.92rem;">
                    <i class="bi bi-check-circle-fill me-1"></i> <?= esc($c['akun_tepat']) ?>
                  </div>
                  <div class="small text-muted" style="line-height: 1.45;">
                    <?= esc($c['alasan']) ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 5. Latihan Soal & Kuis (Bagian F) -->
    <div class="tab-pane fade" id="tab-kuis" role="tabpanel">
      <div class="card-simran p-4 mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(37, 99, 235, 0.04) 100%); border-radius: 16px;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-mortarboard-fill text-success me-2"></i>Uji Pemahaman Kode Akun Belanja</h4>
            <p class="text-muted small mb-0">Latihan 10 soal untuk staf dan operator perencana anggaran Divhumas Polri. Pilih jawaban yang menurut Anda tepat.</p>
          </div>
          <div class="text-end">
            <span class="badge bg-success px-3 py-2 rounded-pill font-monospace" id="quizScoreBadge" style="font-size: 0.9rem;">Skor: 0 / 10</span>
          </div>
        </div>
      </div>

      <div class="row g-4" id="quizContainer">
        <?php foreach ($quizQuestions as $q): ?>
          <div class="col-12">
            <div class="card-simran p-4 border shadow-sm quiz-card" data-qno="<?= esc($q['no']) ?>" data-kunci="<?= esc($q['kunci']) ?>" style="border-radius: 14px;">
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-dark rounded-pill px-3 py-1 font-monospace">Soal #<?= esc($q['no']) ?></span>
              </div>
              <h6 class="fw-bold mb-3" style="font-size: 1.05rem;"><?= esc($q['situasi']) ?></h6>

              <div class="row g-2 mb-3">
                <?php foreach ($q['pilihan'] as $opt): ?>
                  <div class="col-md-3 col-6">
                    <button type="button" class="btn btn-outline-secondary w-100 py-2 rounded-pill font-monospace fw-bold quiz-opt-btn" 
                            data-val="<?= esc($opt) ?>" 
                            onclick="checkQuizAnswer(this, '<?= esc($q['no']) ?>', '<?= esc($q['kunci']) ?>')">
                      <?= esc($opt) ?>
                    </button>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="quiz-feedback p-3 rounded-3 mt-2" style="display: none;">
                <div class="quiz-feedback-title fw-bold mb-1"></div>
                <div class="small quiz-feedback-text"><?= esc($q['pembahasan']) ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 6. Panduan & Regulasi (Bagian A & G) -->
    <div class="tab-pane fade" id="tab-panduan" role="tabpanel">
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="card-simran p-4 h-100 border shadow-sm" style="border-radius: 16px;">
            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-123 me-2"></i>A. Cara Membaca Struktur Kode Akun BAS (6 Digit)</h5>
            <p class="text-muted small mb-3">
              Kode akun Bagan Akun Standar (BAS) Kementerian Keuangan terdiri dari <strong>enam digit</strong> berurutan:
            </p>
            <div class="p-3 rounded-3 bg-light border mb-3 font-monospace" style="font-size: 0.95rem;">
              <div class="mb-2"><strong>Contoh: 521211 (Belanja Bahan)</strong></div>
              <ul class="mb-0 ps-3">
                <li><strong>Digit 1 (5)</strong> : Jenis Belanja (Belanja)</li>
                <li><strong>Digit 1-2 (52 / 53)</strong> : 52 = Belanja Barang, 53 = Belanja Modal</li>
                <li><strong>Digit 3-4 (11 / 12 / 21)</strong> : Kelompok (Operasional, Non-Operasional, Jasa, Pemeliharaan, Perjalanan)</li>
                <li><strong>Digit 5-6 (11)</strong> : Rincian Akun Spesifik</li>
              </ul>
            </div>
            <div class="alert alert-info py-2 px-3 small border-0 rounded-3">
              <i class="bi bi-info-circle me-1"></i> Catatan: Pemilihan akun pada dokumen resmi wajib berpedoman pada KEP-331/PB/2021 dan KEP-291/PB/2022 serta BAS resmi terbaru.
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card-simran p-4 h-100 border shadow-sm" style="border-radius: 16px;">
            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-journal-check me-2"></i>B. Batas Nilai Kapitalisasi Aset (PMK 181/PMK.06/2016)</h5>
            <p class="text-muted small mb-3">
              Batas minimum nilai pengadaan barang yang wajib dicatat sebagai Belanja Modal (Akun 53) dan menghasilkan Aset Tetap:
            </p>
            <div class="table-responsive mb-3">
              <table class="table table-bordered table-sm align-middle small mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Kelompok Aset</th>
                    <th>Batas Kapitalisasi</th>
                    <th>Akun Jika &lt; Batas</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="fw-semibold">Peralatan dan Mesin</td>
                    <td class="text-primary fw-bold">≥ Rp 1.000.000 / satuan</td>
                    <td>521119 atau 521211</td>
                  </tr>
                  <tr>
                    <td class="fw-semibold">Gedung dan Bangunan</td>
                    <td class="text-primary fw-bold">≥ Rp 25.000.000</td>
                    <td>523111 (Pemeliharaan)</td>
                  </tr>
                  <tr>
                    <td class="fw-semibold">Software &amp; Lisensi</td>
                    <td class="text-primary fw-bold">Masa manfaat &gt; 1 tahun</td>
                    <td>521111 (≤ 1 tahun)</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="alert alert-secondary py-2 px-3 small border-0 rounded-3 text-muted">
              <i class="bi bi-shield-check me-1"></i> Dasar Hukum: PMK 181/PMK.06/2016 Pasal 40 &amp; Penjelasan BAS DJPb Kemenkeu.
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

</div>

<!-- Modal Quick View -->
<div class="modal fade" id="accountDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow" style="border-radius: 16px;" id="modalAccountContent">
      <!-- Injected via JS -->
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('accountQuickSearch');
  const groupSelect = document.getElementById('groupFilterSelect');
  const chips = document.querySelectorAll('.group-chip');
  const clearBtn = document.getElementById('clearSearchBtn');
  const focusedCard = document.getElementById('focusedAccountCard');
  const countDisplay = document.getElementById('countDisplay');
  const noResultsAlert = document.getElementById('noResultsAlert');

  // Search & Filter Trigger
  function performFilter() {
    const query = (searchInput.value || '').trim().toLowerCase();
    const group = groupSelect.value;

    if (query.length > 0) {
      clearBtn.style.display = 'block';
    } else {
      clearBtn.style.display = 'none';
    }

    let matchCount = 0;
    let exactMatchAccount = null;

    // Filter cards
    document.querySelectorAll('.account-item').forEach(function (card) {
      const kode = card.getAttribute('data-kode').toLowerCase();
      const corpus = card.getAttribute('data-corpus');
      
      let matchesGroup = (group === 'all' || kode.startsWith(group) || corpus.includes(group.toLowerCase()));
      let matchesQuery = (query === '' || corpus.includes(query) || kode.includes(query));

      if (matchesGroup && matchesQuery) {
        card.style.display = 'block';
        matchCount++;

        if (query.length >= 5 && kode === query) {
          exactMatchAccount = card.getAttribute('data-kode');
        }
      } else {
        card.style.display = 'none';
      }
    });

    // Filter matrix table
    document.querySelectorAll('.matrix-row').forEach(function (row) {
      const kode = row.getAttribute('data-kode').toLowerCase();
      const corpus = row.getAttribute('data-corpus');
      
      let matchesGroup = (group === 'all' || kode.startsWith(group) || corpus.includes(group.toLowerCase()));
      let matchesQuery = (query === '' || corpus.includes(query) || kode.includes(query));

      if (matchesGroup && matchesQuery) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });

    countDisplay.textContent = matchCount;
    noResultsAlert.style.display = (matchCount === 0) ? 'block' : 'none';

    // If exact match found by code, load focused card automatically
    if (exactMatchAccount) {
      selectAccount(exactMatchAccount, false);
    }
  }

  searchInput.addEventListener('input', performFilter);
  groupSelect.addEventListener('change', function () {
    const val = this.value;
    chips.forEach(c => {
      c.classList.toggle('active', c.getAttribute('data-group') === val);
      c.classList.toggle('btn-primary', c.getAttribute('data-group') === val);
      c.classList.toggle('btn-outline-secondary', c.getAttribute('data-group') !== val);
    });
    performFilter();
  });

  chips.forEach(chip => {
    chip.addEventListener('click', function () {
      const g = this.getAttribute('data-group');
      groupSelect.value = g;
      chips.forEach(c => {
        c.classList.remove('active', 'btn-primary');
        c.classList.add('btn-outline-secondary');
      });
      this.classList.add('active', 'btn-primary');
      this.classList.remove('btn-outline-secondary');
      performFilter();
    });
  });

  clearBtn.addEventListener('click', function () {
    searchInput.value = '';
    performFilter();
    searchInput.focus();
  });

  // Initial trigger if query exists
  if (searchInput.value) {
    performFilter();
  }
});

// Switch Main Tab
function switchMainTab(tabId) {
  const btn = document.querySelector(`[data-bs-target="#${tabId}"]`);
  if (btn) {
    const tab = new bootstrap.Tab(btn);
    tab.show();
  }
}

// Reset Filters
function resetFilters() {
  document.getElementById('accountQuickSearch').value = '';
  document.getElementById('groupFilterSelect').value = 'all';
  document.querySelectorAll('.group-chip').forEach(c => {
    c.classList.remove('active', 'btn-primary');
    c.classList.add('btn-outline-secondary');
  });
  document.querySelector('.group-chip[data-group="all"]').classList.add('active', 'btn-primary');
  document.querySelector('.group-chip[data-group="all"]').classList.remove('btn-outline-secondary');
  
  document.querySelectorAll('.account-item').forEach(c => c.style.display = 'block');
  document.querySelectorAll('.matrix-row').forEach(r => r.style.display = '');
  document.getElementById('noResultsAlert').style.display = 'none';
  document.getElementById('countDisplay').textContent = document.querySelectorAll('.account-item').length;
}

// Select & Display Focused Account Card
function selectAccount(code, scrollTo = true) {
  fetch('<?= base_url('akun/api/search') ?>?q=' + encodeURIComponent(code))
    .then(r => r.json())
    .then(res => {
      if (res.success && res.data.length > 0) {
        const acc = res.data[0];
        renderAccountCard(acc);
        const card = document.getElementById('focusedAccountCard');
        card.style.display = 'block';
        if (scrollTo) {
          card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
}

function renderAccountCard(acc) {
  const card = document.getElementById('focusedAccountCard');
  let statusBadge = '<span class="badge bg-success px-3 py-1 rounded-pill">' + (acc.status || 'Aktif') + '</span>';
  if ((acc.status || '').includes('Tidak digunakan')) {
    statusBadge = '<span class="badge bg-danger px-3 py-1 rounded-pill">' + acc.status + '</span>';
  } else if ((acc.status || '').includes('[Rev]')) {
    statusBadge = '<span class="badge bg-warning text-dark px-3 py-1 rounded-pill">' + acc.status + '</span>';
  }

  card.innerHTML = `
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <span class="badge bg-primary px-3 py-2 font-monospace" style="font-size: 1.15rem; border-radius: 8px;">
            <i class="bi bi-tag-fill me-1"></i> ${acc.kode}
          </span>
          <span class="badge bg-secondary bg-opacity-25 text-dark border px-3 py-2" style="font-size: 0.85rem; border-radius: 8px;">
            ${acc.kelompok}
          </span>
          ${statusBadge}
        </div>
        <h3 class="fw-bold mb-1 text-primary mt-2">${acc.uraian}</h3>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= base_url('akun') ?>/${acc.kode}" class="btn btn-outline-primary btn-sm rounded-pill px-3" title="Buka tautan permanen">
          <i class="bi bi-box-arrow-up-right me-1"></i> Halaman Khusus
        </a>
        <button type="button" class="btn btn-light btn-sm rounded-pill px-3 border" onclick="document.getElementById('focusedAccountCard').style.display='none'">
          <i class="bi bi-x-lg"></i> Tutup
        </button>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-lg-6">
        <div class="p-3 rounded-3 h-100" style="background: rgba(37, 99, 235, 0.04); border-left: 4px solid #2563eb;">
          <div class="fw-bold text-primary mb-2" style="font-size: 0.95rem;">
            <i class="bi bi-card-text me-1"></i> Untuk Apa / Penjelasan Peruntukan
          </div>
          <p class="mb-0 text-muted" style="line-height: 1.6; font-size: 0.92rem;">
            ${acc.penjelasan}
          </p>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="p-3 rounded-3 h-100" style="background: rgba(14, 165, 233, 0.04); border-left: 4px solid #0ea5e9;">
          <div class="fw-bold text-info mb-2" style="font-size: 0.95rem;">
            <i class="bi bi-shield-check me-1"></i> Batasan / Pembeda Kunci
          </div>
          <p class="mb-0 text-muted" style="line-height: 1.6; font-size: 0.92rem;">
            ${acc.batasan}
          </p>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="p-3 rounded-3 h-100" style="background: rgba(239, 68, 68, 0.04); border-left: 4px solid #ef4444;">
          <div class="fw-bold text-danger mb-2" style="font-size: 0.95rem;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> Kesalahan Umum yang Sering Terjadi
          </div>
          <p class="mb-0 text-muted" style="line-height: 1.6; font-size: 0.92rem;">
            ${acc.kesalahan_umum}
          </p>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="p-3 rounded-3 h-100" style="background: rgba(16, 185, 129, 0.04); border-left: 4px solid #10b981;">
          <div class="fw-bold text-success mb-2" style="font-size: 0.95rem;">
            <i class="bi bi-building me-1"></i> Contoh Kasus Riil di Lingkungan Divhumas Polri
          </div>
          <p class="mb-0 text-muted" style="line-height: 1.6; font-size: 0.92rem;">
            ${acc.contoh_divhumas}
          </p>
        </div>
      </div>
    </div>

    <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2 text-muted small">
      <div>
        <i class="bi bi-journal-bookmark me-1 text-primary"></i> <strong>Dasar Rujukan:</strong> ${acc.sumber}
      </div>
      <div>
        <i class="bi bi-info-circle me-1 text-secondary"></i> Standar BAS KEP-331/PB/2021 &amp; KEP-291/PB/2022
      </div>
    </div>
  `;
}

// Reveal Divhumas Case Answer
function toggleCaseReveal(btn) {
  const box = btn.nextElementSibling;
  if (box.style.display === 'none') {
    box.style.display = 'block';
    btn.innerHTML = '<i class="bi bi-eye-slash me-1"></i> Sembunyikan Jawaban';
  } else {
    box.style.display = 'none';
    btn.innerHTML = '<i class="bi bi-eye me-1"></i> Tampilkan Akun yang Tepat &amp; Alasan';
  }
}

// Interactive Quiz Handler
let quizScores = {};
function checkQuizAnswer(btn, qno, kunci) {
  const card = btn.closest('.quiz-card');
  const allBtns = card.querySelectorAll('.quiz-opt-btn');
  const feedback = card.querySelector('.quiz-feedback');
  const feedbackTitle = card.querySelector('.quiz-feedback-title');
  const chosen = btn.getAttribute('data-val');

  allBtns.forEach(b => {
    b.classList.remove('btn-success', 'btn-danger', 'text-white');
    b.classList.add('btn-outline-secondary');
  });

  feedback.style.display = 'block';

  if (chosen === kunci) {
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-success', 'text-white');
    feedback.className = 'quiz-feedback p-3 rounded-3 mt-2 bg-success bg-opacity-10 text-success border border-success border-opacity-25';
    feedbackTitle.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Jawaban Benar!';
    quizScores[qno] = 1;
  } else {
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-danger', 'text-white');
    
    // Highlight correct button
    allBtns.forEach(b => {
      if (b.getAttribute('data-val') === kunci) {
        b.classList.remove('btn-outline-secondary');
        b.classList.add('btn-success', 'text-white');
      }
    });

    feedback.className = 'quiz-feedback p-3 rounded-3 mt-2 bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
    feedbackTitle.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i> Jawaban Kurang Tepat. Kunci yang Benar: ' + kunci;
    quizScores[qno] = 0;
  }

  // Update total score badge
  const totalScore = Object.values(quizScores).reduce((a, b) => a + b, 0);
  document.getElementById('quizScoreBadge').textContent = 'Skor: ' + totalScore + ' / 10';
}
</script>

<?= $this->endSection() ?>
