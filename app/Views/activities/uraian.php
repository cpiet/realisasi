<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
/* Styling khusus Halaman Uraian Kegiatan (macOS Cupertino HIG) */
.uraian-card-summary {
  background: var(--apple-card);
  border: 1px solid var(--apple-border);
  border-radius: 14px;
  padding: 12px 16px;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  cursor: pointer;
  position: relative;
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}
.uraian-card-summary:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-float);
  border-color: var(--apple-blue-border);
}
.uraian-card-summary.active {
  border-color: var(--apple-blue);
  background: var(--apple-blue-tint);
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.15);
}

.table-uraian {
  border-collapse: separate;
  border-spacing: 0;
  width: 100%;
  font-size: 12.5px;
}
.table-uraian thead th {
  background: var(--apple-card-subtle);
  color: var(--apple-secondary);
  font-weight: 600;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 10px 8px;
  border-bottom: 1px solid var(--apple-border);
  position: sticky;
  top: 0;
  z-index: 10;
}
.table-uraian tbody tr {
  cursor: pointer;
  transition: background-color 0.15s ease;
}
.table-uraian tbody tr:hover {
  background-color: rgba(0, 122, 255, 0.04) !important;
}
.table-uraian tbody td {
  padding: 10px 8px;
  border-bottom: 1px solid var(--apple-border-light);
  vertical-align: top;
}

.badge-biro-penmas { background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
.badge-biro-multimedia { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.badge-biro-pid { background-color: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; }
.badge-biro-dukyan { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.badge-biro-pemeliharaan { background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
.badge-biro-gaji { background-color: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }
.badge-biro-pelatihan { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

.text-uraian-preview {
  font-size: 12px;
  color: #475569;
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.modal-uraian-highlight {
  background: #f8fafc;
  border-left: 4px solid #2563eb;
  padding: 14px 18px;
  border-radius: 6px;
  font-size: 14px;
  line-height: 1.6;
  color: #1e293b;
}
</style>

<!-- Header & Navigasi -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <h2 class="h5 fw-bold mb-0 text-dark">TABEL URAIAN KEGIATAN PER BIRO / BAGIAN</h2>
      <span class="badge bg-primary px-2.5 py-1">T.A. <?= esc($selectedYear['year'] ?? 2027) ?></span>
      <span class="badge bg-success px-2.5 py-1"><?= count($items) ?> Kegiatan</span>
    </div>
    <div class="text-muted small mt-1">
      Sumber: RKAKL Satker T.A. <?= esc($selectedYear['year'] ?? 2027) ?> (Pagu Alokasi Rp <?= number_format($summary['total_ceiling'], 0, ',', '.') ?>) dan RAB per Biro Divhumas Polri
    </div>
  </div>

  <div class="d-flex align-items-center gap-2 flex-wrap">
    <!-- Switcher ke halaman data kegiatan akun -->
    <a href="<?= base_url('kegiatan?year=' . esc($selectedYear['year'] ?? 2027)) ?>" class="btn-simran btn-sm border d-flex align-items-center gap-1 bg-white" title="Lihat rincian akun belanja dan realisasi">
      <i class="bi bi-grid-3x3-gap-fill"></i>
      <span>Realisasi Akun</span>
    </a>

    <!-- Tautan ke RPA -->
    <a href="<?= base_url('admin/rpa?year=' . esc($selectedYear['year'] ?? 2027)) ?>" class="btn-simran btn-sm border d-flex align-items-center gap-1 bg-white text-primary fw-semibold" title="Buka Rencana Penarikan Anggaran">
      <i class="bi bi-table"></i>
      <span>Lembar RPA</span>
    </a>

    <!-- Tautan ke Pagu DIPA -->
    <a href="<?= base_url('admin/pagu?year=' . esc($selectedYear['year'] ?? 2027)) ?>" class="btn-simran btn-sm border d-flex align-items-center gap-1 bg-white text-secondary fw-semibold" title="Kelola Pagu Induk DIPA">
      <i class="bi bi-bank"></i>
      <span>Pagu DIPA</span>
    </a>

    <!-- Pemilih Tahun Anggaran -->
    <div class="d-flex align-items-center gap-1">
      <span class="text-muted small">T.A.:</span>
      <select class="form-select-simran" style="width: auto; font-weight: 600; padding: 4px 8px;" onchange="window.simranSwitchYear ? window.simranSwitchYear(this.value) : (location.href='<?= base_url('set-year') ?>/' + this.value)">
        <?php foreach ($allYears as $y): ?>
          <option value="<?= esc($y['year']) ?>" <?= ($selectedYear['year'] == $y['year']) ? 'selected' : '' ?>>
            <?= esc($y['name']) ?> <?= $y['is_active'] ? '(Aktif)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Tombol Cetak / PDF -->
    <button type="button" class="btn-simran btn-simran-primary d-flex align-items-center gap-1" onclick="window.print()">
      <i class="bi bi-printer"></i>
      <span>Cetak</span>
    </button>
  </div>
</div>

<!-- Kartu Ringkasan Pagu per Biro (Presisi Sesuai Dokumen) -->
<div class="row g-2 mb-3">
  <!-- Biro Penmas -->
  <div class="col-6 col-md-4 col-xl">
    <div class="uraian-card-summary <?= $activeBiro === 'penmas' ? 'active' : '' ?>" onclick="filterBiro('penmas')">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="badge badge-biro-penmas">Biro Penmas</span>
        <small class="text-muted fw-bold"><?= $summary['groups']['penmas']['count'] ?> Giat</small>
      </div>
      <div class="fw-bold text-dark" style="font-size: 13.5px;">Rp <?= number_format($summary['groups']['penmas']['ceiling'], 0, ',', '.') ?></div>
      <small class="text-muted" style="font-size: 11px;">Hubungan Masy. &amp; Info</small>
    </div>
  </div>

  <!-- Biro Multimedia -->
  <div class="col-6 col-md-4 col-xl">
    <div class="uraian-card-summary <?= $activeBiro === 'multimedia' ? 'active' : '' ?>" onclick="filterBiro('multimedia')">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="badge badge-biro-multimedia">Biro Multimedia</span>
        <small class="text-muted fw-bold"><?= $summary['groups']['multimedia']['count'] ?> Giat</small>
      </div>
      <div class="fw-bold text-dark" style="font-size: 13.5px;">Rp <?= number_format($summary['groups']['multimedia']['ceiling'], 0, ',', '.') ?></div>
      <small class="text-muted" style="font-size: 11px;">Pengelolaan Multimedia</small>
    </div>
  </div>

  <!-- Biro PID -->
  <div class="col-6 col-md-4 col-xl">
    <div class="uraian-card-summary <?= $activeBiro === 'pid' ? 'active' : '' ?>" onclick="filterBiro('pid')">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="badge badge-biro-pid">Biro PID</span>
        <small class="text-muted fw-bold"><?= $summary['groups']['pid']['count'] ?> Giat</small>
      </div>
      <div class="fw-bold text-dark" style="font-size: 13.5px;">Rp <?= number_format($summary['groups']['pid']['ceiling'], 0, ',', '.') ?></div>
      <small class="text-muted" style="font-size: 11px;">Info &amp; Dokumentasi</small>
    </div>
  </div>

  <!-- Dukyan Bagrenmin -->
  <div class="col-6 col-md-4 col-xl">
    <div class="uraian-card-summary <?= $activeBiro === 'dukyan' ? 'active' : '' ?>" onclick="filterBiro('dukyan')">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="badge badge-biro-dukyan">Dukyan Satker</span>
        <small class="text-muted fw-bold"><?= $summary['groups']['dukyan']['count'] ?> Giat</small>
      </div>
      <div class="fw-bold text-dark" style="font-size: 13.5px;">Rp <?= number_format($summary['groups']['dukyan']['ceiling'], 0, ',', '.') ?></div>
      <small class="text-muted" style="font-size: 11px;">Dukungan Manajemen</small>
    </div>
  </div>

  <!-- Pemeliharaan -->
  <div class="col-6 col-md-4 col-xl">
    <div class="uraian-card-summary <?= $activeBiro === 'pemeliharaan' ? 'active' : '' ?>" onclick="filterBiro('pemeliharaan')">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="badge badge-biro-pemeliharaan">Pemeliharaan</span>
        <small class="text-muted fw-bold"><?= $summary['groups']['pemeliharaan']['count'] ?> Giat</small>
      </div>
      <div class="fw-bold text-dark" style="font-size: 13.5px;">Rp <?= number_format($summary['groups']['pemeliharaan']['ceiling'], 0, ',', '.') ?></div>
      <small class="text-muted" style="font-size: 11px;">Kantor, Sarpras &amp; Web</small>
    </div>
  </div>

  <!-- Gaji & Tunjangan -->
  <div class="col-6 col-md-4 col-xl">
    <div class="uraian-card-summary <?= $activeBiro === 'gaji' ? 'active' : '' ?>" onclick="filterBiro('gaji')">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="badge badge-biro-gaji">Gaji &amp; Tunj.</span>
        <small class="text-muted fw-bold"><?= $summary['groups']['gaji']['count'] ?> Giat</small>
      </div>
      <div class="fw-bold text-dark" style="font-size: 13.5px;">Rp <?= number_format($summary['groups']['gaji']['ceiling'], 0, ',', '.') ?></div>
      <small class="text-muted" style="font-size: 11px;">Polri, PNS &amp; PPPK</small>
    </div>
  </div>

  <!-- Pelatihan SDM -->
  <div class="col-6 col-md-4 col-xl">
    <div class="uraian-card-summary <?= $activeBiro === 'pelatihan' ? 'active' : '' ?>" onclick="filterBiro('pelatihan')">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="badge badge-biro-pelatihan">Pelatihan SDM</span>
        <small class="text-muted fw-bold"><?= $summary['groups']['pelatihan']['count'] ?> Giat</small>
      </div>
      <div class="fw-bold text-dark" style="font-size: 13.5px;">Rp <?= number_format($summary['groups']['pelatihan']['ceiling'], 0, ',', '.') ?></div>
      <small class="text-muted" style="font-size: 11px;">Presenter &amp; Diklat</small>
    </div>
  </div>
</div>

<!-- Toolbar: Filter Tab & Live Search -->
<div class="card-simran p-2 px-3 mb-3">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-2">
    <!-- Filter Tabs -->
    <div class="d-flex align-items-center gap-1 flex-wrap">
      <span class="text-muted small fw-semibold text-uppercase me-1" style="font-size: 11px;">Filter Biro:</span>
      <button type="button" class="btn btn-sm <?= ($activeBiro === 'all' || empty($activeBiro)) ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>" onclick="filterBiro('all')">
        Semua (<?= $summary['total_items'] ?>)
      </button>
      <button type="button" class="btn btn-sm <?= $activeBiro === 'penmas' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>" onclick="filterBiro('penmas')">
        Biro Penmas (<?= $summary['groups']['penmas']['count'] ?>)
      </button>
      <button type="button" class="btn btn-sm <?= $activeBiro === 'multimedia' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>" onclick="filterBiro('multimedia')">
        Biro Multimedia (<?= $summary['groups']['multimedia']['count'] ?>)
      </button>
      <button type="button" class="btn btn-sm <?= $activeBiro === 'pid' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>" onclick="filterBiro('pid')">
        Biro PID (<?= $summary['groups']['pid']['count'] ?>)
      </button>
      <button type="button" class="btn btn-sm <?= $activeBiro === 'dukyan' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>" onclick="filterBiro('dukyan')">
        Dukyan (<?= $summary['groups']['dukyan']['count'] ?>)
      </button>
      <button type="button" class="btn btn-sm <?= $activeBiro === 'pemeliharaan' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>" onclick="filterBiro('pemeliharaan')">
        Pemeliharaan (<?= $summary['groups']['pemeliharaan']['count'] ?>)
      </button>
      <button type="button" class="btn btn-sm <?= $activeBiro === 'gaji' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>" onclick="filterBiro('gaji')">
        Gaji &amp; Tunj. (<?= $summary['groups']['gaji']['count'] ?>)
      </button>
      <button type="button" class="btn btn-sm <?= $activeBiro === 'pelatihan' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>" onclick="filterBiro('pelatihan')">
        Pelatihan (<?= $summary['groups']['pelatihan']['count'] ?>)
      </button>
    </div>

    <!-- Live Search -->
    <div class="input-group input-group-sm" style="max-width: 320px;">
      <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
      <input type="text" id="uraianSearchInput" class="form-control form-control-sm" placeholder="Cari kegiatan, narasumber, paket, RO..." value="<?= esc($keyword) ?>">
      <button type="button" class="btn btn-outline-secondary" onclick="clearUraianSearch()" title="Bersihkan pencarian"><i class="bi bi-x"></i></button>
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top text-muted small" style="font-size: 11.5px;">
    <div>
      <span class="badge bg-secondary" id="visibleItemCount"><?= count($items) ?> kegiatan ditampilkan</span>
      <span class="ms-1 text-primary fw-medium"><i class="bi bi-cursor-fill me-1"></i>Klik baris mana saja untuk melihat uraian operasional lengkap</span>
    </div>
    <div class="fw-semibold text-dark">
      Total Pagu Terpilih: <span class="text-primary font-monospace" id="visibleTotalPagu">Rp <?= number_format(array_sum(array_column($items, 'ceiling')), 0, ',', '.') ?></span>
    </div>
  </div>
</div>

<!-- Main Table: Uraian Kegiatan -->
<div class="card-simran p-0 overflow-hidden shadow-sm border">
  <div class="table-responsive" style="max-height: 75vh;">
    <table class="table-uraian mb-0" id="tableUraian">
      <thead>
        <tr>
          <th style="width: 45px; text-align: center;">NO</th>
          <th style="min-width: 220px; text-align: left;">NAMA KEGIATAN</th>
          <th style="width: 170px; text-align: left;">BIRO / BAGIAN</th>
          <th style="width: 80px; text-align: center;">RO</th>
          <th style="width: 135px; text-align: right;">PAGU (RP)</th>
          <th style="min-width: 380px; text-align: left;">URAIAN OPERASIONAL KEGIATAN (RAB / RKAKL)</th>
          <th style="width: 75px; text-align: center;">HLM</th>
          <th style="width: 70px; text-align: center;">AKSI</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
              <div class="fw-bold fs-6">Tidak ada kegiatan yang sesuai kriteria pencarian.</div>
              <small>Coba gunakan kata kunci lain atau pilih tab Biro lainnya.</small>
            </td>
          </tr>
        <?php else: ?>
          <?php
            $currentCategory = '';
            foreach ($items as $it):
              // Tampilkan separator header kategori grup jika berganti
              if ($it['category_group'] !== $currentCategory && ($activeBiro === 'all' || empty($activeBiro)) && empty($keyword)):
                $currentCategory = $it['category_group'];
          ?>
            <tr class="category-header-row bg-light" style="cursor: default;" data-is-header="true">
              <td colspan="8" class="fw-bold text-dark py-2 px-3 border-top border-bottom" style="font-size: 12px; background: #f8fafc;">
                <i class="bi bi-folder2-open text-primary me-1"></i> <?= esc($currentCategory) ?>
              </td>
            </tr>
          <?php endif; ?>

          <?php
            // Badge style per biro
            $biroBadgeClass = 'badge-biro-dukyan';
            $biroText = $it['biro'];
            if (str_contains($it['category_group'], 'BIRO PENMAS')) {
              $biroBadgeClass = 'badge-biro-penmas';
            } elseif (str_contains($it['category_group'], 'BIRO MULTIMEDIA')) {
              $biroBadgeClass = 'badge-biro-multimedia';
            } elseif (str_contains($it['category_group'], 'BIRO PID')) {
              $biroBadgeClass = 'badge-biro-pid';
            } elseif (str_contains($it['category_group'], 'PEMELIHARAAN')) {
              $biroBadgeClass = 'badge-biro-pemeliharaan';
            } elseif (str_contains($it['category_group'], 'GAJI')) {
              $biroBadgeClass = 'badge-biro-gaji';
            } elseif (str_contains($it['category_group'], 'PELATIHAN')) {
              $biroBadgeClass = 'badge-biro-pelatihan';
            }
          ?>
            <tr class="uraian-row"
                data-id="<?= $it['id'] ?>"
                data-biro="<?= strtolower(esc($it['biro'])) ?>"
                data-group="<?= strtolower(esc($it['category_group'])) ?>"
                data-pagu="<?= $it['ceiling'] ?>"
                data-search="<?= strtolower(esc($it['item_no'] . ' ' . $it['activity_name'] . ' ' . $it['biro'] . ' ' . $it['division'] . ' ' . $it['ro_code'] . ' ' . $it['description'] . ' ' . $it['rkakl_activity'])) ?>"
                onclick="showUraianModal(<?= $it['id'] ?>)">
              
              <!-- NO -->
              <td class="text-center text-muted fw-semibold" style="font-size: 11px;">
                <?= $it['item_no'] ?>
              </td>

              <!-- NAMA KEGIATAN -->
              <td>
                <div class="fw-bold text-dark" style="font-size: 13px; line-height: 1.35;">
                  <?= esc($it['activity_name']) ?>
                </div>
                <div class="text-muted" style="font-size: 11px;">
                  <?= esc($it['rkakl_activity']) ?>
                </div>
              </td>

              <!-- BIRO / BAGIAN -->
              <td>
                <span class="badge <?= $biroBadgeClass ?> px-2 py-1 mb-1 d-inline-block">
                  <?= esc($it['biro']) ?>
                </span>
                <div class="text-muted small" style="font-size: 11px;">
                  <?= esc($it['division']) ?>
                </div>
              </td>

              <!-- KODE RO -->
              <td class="text-center">
                <span class="badge bg-dark-subtle text-dark font-monospace px-2 py-1 fw-bold" style="font-size: 11.5px;">
                  <?= esc($it['ro_code']) ?>
                </span>
              </td>

              <!-- PAGU (RP) -->
              <td class="text-end font-monospace fw-bold text-dark nowrap" style="font-size: 12.5px;">
                Rp <?= number_format($it['ceiling'], 0, ',', '.') ?>
              </td>

              <!-- URAIAN OPERASIONAL KEGIATAN -->
              <td>
                <div class="text-uraian-preview" title="Klik baris untuk membaca seluruhnya">
                  <?= esc($it['description']) ?>
                </div>
              </td>

              <!-- SUMBER HLM -->
              <td class="text-center text-muted font-monospace" style="font-size: 11px;">
                <?= esc($it['rkakl_source_page'] ?: '—') ?>
              </td>

              <!-- AKSI -->
              <td class="text-center" onclick="event.stopPropagation();">
                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 11.5px; height: 26px;" onclick="showUraianModal(<?= $it['id'] ?>)" title="Buka Detail Uraian">
                  Detail
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Pop-Up Interaktif: Detail Uraian Kegiatan -->
<div class="modal fade" id="modalUraianDetail" tabindex="-1" aria-labelledby="modalUraianDetailLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content card-simran p-0 border-0 shadow-lg">
      <div class="modal-header border-bottom p-3 bg-light">
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary fs-6 px-2.5 py-1" id="modalUraianNo">#</span>
          <h5 class="modal-title h6 fw-bold mb-0 text-dark" id="modalUraianTitle">Detail Kegiatan</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3 p-md-4">
        <!-- Meta Details Grid -->
        <div class="row g-3 mb-3">
          <div class="col-12 col-md-6">
            <div class="p-2.5 px-3 rounded border bg-light h-100">
              <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Biro &amp; Bagian / Subbag</div>
              <div class="fw-bold text-dark fs-6" id="modalUraianBiro">—</div>
              <div class="text-secondary small" id="modalUraianDivision">—</div>
            </div>
          </div>
          <div class="col-12 col-md-3 col-6">
            <div class="p-2.5 px-3 rounded border bg-light h-100">
              <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Kode RO &amp; RKAKL</div>
              <div class="fw-bold text-primary fs-6 font-monospace" id="modalUraianRo">—</div>
              <div class="text-muted small text-truncate" id="modalUraianRkakl" style="font-size: 11px;">—</div>
            </div>
          </div>
          <div class="col-12 col-md-3 col-6">
            <div class="p-2.5 px-3 rounded border bg-light h-100">
              <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Pagu Alokasi</div>
              <div class="fw-bold text-success fs-6 font-monospace" id="modalUraianPagu">Rp 0</div>
              <div class="text-muted small" style="font-size: 11px;">T.A. <?= esc($selectedYear['year'] ?? 2027) ?></div>
            </div>
          </div>
        </div>

        <!-- Uraian Operasional Box (Highlight Utama) -->
        <div class="mb-3">
          <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 11.5px;">
            <i class="bi bi-card-text text-primary me-1"></i> Rincian Uraian Operasional Kegiatan (RAB &amp; RKAKL)
          </label>
          <div class="modal-uraian-highlight" id="modalUraianDescription">
            Memuat rincian...
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center p-2 px-3 rounded bg-light border text-muted small" style="font-size: 11.5px;">
          <div>
            <i class="bi bi-file-earmark-text text-secondary me-1"></i>
            Rujukan Dokumen: <strong class="text-dark">RKAKL Satker T.A. <?= esc($selectedYear['year'] ?? 2027) ?></strong>
          </div>
          <div>
            Halaman: <span class="badge bg-secondary font-monospace" id="modalUraianPage">—</span>
          </div>
        </div>
      </div>
      <div class="modal-footer border-top p-2 px-3 bg-light d-flex justify-content-between">
        <div class="small text-muted" style="font-size: 11px;">
          <i class="bi bi-shield-check text-success me-1"></i> Data resmi DIPA/RKAKL Satker 642321 Divhumas Polri
        </div>
        <button type="button" class="btn-simran" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
let activeBiroFilter = '<?= esc($activeBiro) ?>' || 'all';

function filterBiro(biro) {
  activeBiroFilter = biro;
  const url = new URL(window.location.href);
  url.searchParams.set('biro', biro);
  window.history.replaceState({}, '', url.toString());

  // Update active state in UI
  document.querySelectorAll('.uraian-card-summary').forEach(card => card.classList.remove('active'));
  const targetCard = document.querySelector(`.uraian-card-summary[onclick="filterBiro('${biro}')"]`);
  if (targetCard) targetCard.classList.add('active');

  applyUraianFilters();
}

function clearUraianSearch() {
  const searchInput = document.getElementById('uraianSearchInput');
  if (searchInput) {
    searchInput.value = '';
    applyUraianFilters();
  }
}

function applyUraianFilters() {
  const query = (document.getElementById('uraianSearchInput')?.value || '').toLowerCase().trim();
  const rows = document.querySelectorAll('.uraian-row');
  const catHeaders = document.querySelectorAll('.category-header-row');
  let visibleCount = 0;
  let visiblePagu = 0;

  // Sembunyikan category headers jika ada pencarian atau filter biro
  const isFiltering = (activeBiroFilter !== 'all' && activeBiroFilter !== '') || query !== '';
  catHeaders.forEach(ch => {
    ch.style.display = isFiltering ? 'none' : '';
  });

  rows.forEach(row => {
    const rowBiro = row.getAttribute('data-biro') || '';
    const rowGroup = row.getAttribute('data-group') || '';
    const rowSearch = row.getAttribute('data-search') || '';
    const pagu = parseFloat(row.getAttribute('data-pagu') || 0);

    let biroMatch = true;
    if (activeBiroFilter === 'penmas') {
      biroMatch = rowBiro.includes('penmas') && !rowGroup.includes('dukyan');
    } else if (activeBiroFilter === 'multimedia') {
      biroMatch = rowBiro.includes('multimedia') && !rowGroup.includes('dukyan');
    } else if (activeBiroFilter === 'pid') {
      biroMatch = rowBiro.includes('pid') && !rowGroup.includes('dukyan');
    } else if (activeBiroFilter === 'dukyan') {
      biroMatch = rowGroup.includes('dukyan');
    } else if (activeBiroFilter === 'pemeliharaan') {
      biroMatch = rowGroup.includes('pemeliharaan');
    } else if (activeBiroFilter === 'gaji') {
      biroMatch = rowGroup.includes('gaji');
    } else if (activeBiroFilter === 'pelatihan') {
      biroMatch = rowGroup.includes('pelatihan');
    }

    const searchMatch = (query === '' || rowSearch.includes(query));

    if (biroMatch && searchMatch) {
      row.style.display = '';
      visibleCount++;
      visiblePagu += pagu;
    } else {
      row.style.display = 'none';
    }
  });

  // Update counter badges
  const badge = document.getElementById('visibleItemCount');
  if (badge) {
    badge.innerText = visibleCount + ' kegiatan ditampilkan';
  }
  const paguBadge = document.getElementById('visibleTotalPagu');
  if (paguBadge) {
    paguBadge.innerText = 'Rp ' + visiblePagu.toLocaleString('id-ID');
  }
}

// Buka Modal Pop-up Detail
function showUraianModal(id) {
  const modalEl = document.getElementById('modalUraianDetail');
  if (!modalEl) return;
  const modal = new bootstrap.Modal(modalEl);

  // Set loading state
  document.getElementById('modalUraianNo').innerText = '...';
  document.getElementById('modalUraianTitle').innerText = 'Memuat data...';
  document.getElementById('modalUraianDescription').innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Memuat rincian...</div>';
  modal.show();

  fetch('<?= base_url('kegiatan/uraian/detail') ?>/' + id)
    .then(res => res.json())
    .then(res => {
      if (res.status === 'success') {
        const d = res.data;
        document.getElementById('modalUraianNo').innerText = '#' + d.item_no;
        document.getElementById('modalUraianTitle').innerText = d.activity_name;
        document.getElementById('modalUraianBiro').innerText = d.biro;
        document.getElementById('modalUraianDivision').innerText = d.division;
        document.getElementById('modalUraianRo').innerText = 'RO: ' + d.ro_code;
        document.getElementById('modalUraianRkakl').innerText = d.rkakl_activity;
        document.getElementById('modalUraianPagu').innerText = d.ceiling_formatted;
        document.getElementById('modalUraianDescription').innerText = d.description;
        document.getElementById('modalUraianPage').innerText = d.rkakl_source_page;
      } else {
        document.getElementById('modalUraianDescription').innerText = 'Gagal memuat rincian kegiatan.';
      }
    })
    .catch(() => {
      document.getElementById('modalUraianDescription').innerText = 'Terjadi kesalahan saat memuat data.';
    });
}

document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('uraianSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', applyUraianFilters);
  }
});
</script>

<?= $this->endSection() ?>
