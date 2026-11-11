<?php

namespace App\Controllers;

use App\Models\SbmRepository;

/**
 * Halaman publik Standar Biaya Masukan (SBM): hanya baca, tanpa login.
 */
class Sbm extends BaseController
{
    private const MAX_ROWS_PER_ITEM = 60;
    private const MAX_ROWS_DETAIL = 400;

    private SbmRepository $repo;

    public function __construct()
    {
        $this->repo = new SbmRepository();
    }

    public function index()
    {
        $yearParam = $this->request->getGet('year');
        $fyModel = new \App\Models\FiscalYearModel();
        $selectedYear = $fyModel->determineSelectedYear($yearParam ? (int)$yearParam : null);
        $targetYear = (int)($selectedYear['year'] ?? date('Y'));

        $default = $this->repo->defaultRegulasi($targetYear);

        return view('sbm/index', [
            'title'          => 'Standar Biaya SBM — SIMRAN',
            'regulasi_list'  => $this->repo->regulasiList(),
            'wilayah_list'   => $this->repo->wilayahList(),
            'default_reg'    => $default['id'] ?? null,
            'default_wil'    => $this->repo->defaultWilayahId(),
            'chips'          => ['fullboard', 'fullday', 'halfday', 'uang harian', 'snack', 'konsumsi rapat'],
            'selected_year'  => $selectedYear,
        ]);
    }

    public function cari()
    {
        $q    = (string)$this->request->getGet('q');
        $regId = (int)$this->request->getGet('reg');
        $wilId = (int)$this->request->getGet('prov');

        $regulasi = $this->repo->regulasi($regId) ?? $this->repo->defaultRegulasi();
        if (!$regulasi) {
            return $this->response->setJSON(['q' => $q, 'hasil' => [], 'regulasi' => null]);
        }

        $items = $this->repo->search((int)$regulasi['id'], $q, $wilId ?: null);

        $hasil = [];
        foreach ($items as $item) {
            $total = count($item['rows']);
            $p = $this->presentItem($item, $regulasi, array_slice($item['rows'], 0, self::MAX_ROWS_PER_ITEM));
            $p['total_baris'] = $total;
            $p['terpotong'] = max(0, $total - self::MAX_ROWS_PER_ITEM);
            $hasil[] = $p;
        }

        return $this->response->setJSON([
            'q'        => mb_substr(trim($q), 0, SbmRepository::MAX_QUERY_LENGTH, 'UTF-8'),
            'regulasi' => $this->presentRegulasi($regulasi),
            'hasil'    => $hasil,
        ]);
    }

    public function item(int $id)
    {
        $item = $this->repo->item($id);
        if (!$item) {
            return redirect()->to(base_url('sbm'))->with('error', 'Item SBM tidak ditemukan.');
        }
        $regulasi = $this->repo->regulasi((int)$item['regulasi_id']);
        $wilId = (int)$this->request->getGet('prov') ?: $this->repo->defaultWilayahId();
        $q = (string)$this->request->getGet('q');
        $res = $this->repo->hargaFiltered($id, $wilId ?: null, $q, self::MAX_ROWS_DETAIL);

        return view('sbm/item', [
            'title'       => $item['nama'] . ' — Standar Biaya SBM',
            'item'        => $this->presentItem($item, $regulasi, $res['rows']),
            'q'           => trim($q),
            'total_baris' => $res['total'],
            'max_baris'   => self::MAX_ROWS_DETAIL,
            'regulasi'    => $this->presentRegulasi($regulasi),
            'wilayah_list' => $this->repo->wilayahList(),
            'wil_id'      => $wilId,
        ]);
    }

    /** @param list<array<string,mixed>> $rows */
    private function presentItem(array $item, array $regulasi, array $rows): array
    {
        $groups = [];
        foreach ($rows as $r) {
            $groups[$r['kelompok']][] = [
                'id'           => (int)$r['id'],
                'kolom'        => $r['kolom'],
                'satuan'       => $r['satuan'],
                'besaran'      => $r['besaran'] === null ? null : (int)$r['besaran'],
                'mata_uang'    => $r['mata_uang'] ?? 'IDR',
                'keterangan'   => $r['keterangan'] ?? null,
                'needs_review' => (bool)$r['needs_review'],
            ];
        }
        $harga = [];
        foreach ($groups as $label => $list) {
            $harga[] = ['kelompok' => $label, 'baris' => $list];
        }

        $acuan = null;
        if (!(int)$regulasi['punya_nominal']) {
            foreach ($this->repo->padanan($item) as $p) {
                if ((int)$p['punya_nominal'] === 1) {
                    $acuan = [
                        'item_id'     => (int)$p['id'],
                        'regulasi_id' => (int)$p['regulasi_id'],
                        'label'       => $p['jenis'] . ' ' . $p['nomor'] . ' (TA ' . $p['tahun_anggaran'] . ')',
                    ];
                    break;
                }
            }
        }

        return [
            'id'         => (int)$item['id'],
            'kode'       => $item['kode'],
            'kanonik'    => $item['kode_kanonik'] ?? null,
            'lampiran'   => $item['lampiran'],
            'nama'       => $item['nama'],
            'sifat'      => $item['sifat'],
            'sifat_label' => SbmRepository::SIFAT[$item['sifat']] ?? $item['sifat'],
            'deskripsi'  => $item['deskripsi'],
            'ketentuan'  => $item['penjelasan'] ? preg_split('/\R/u', trim($item['penjelasan'])) : [],
            'harga'      => $harga,
            'sumber'     => SbmRepository::sumber($regulasi, $item),
            'acuan'      => $acuan,
        ];
    }

    private function presentRegulasi(array $r): array
    {
        return [
            'id'             => (int)$r['id'],
            'label'          => $r['jenis'] . ' ' . $r['nomor'],
            'tahun_anggaran' => $r['tahun_anggaran'] ? (int)$r['tahun_anggaran'] : null,
            'status'         => $r['status'],
            'punya_nominal'  => (bool)$r['punya_nominal'],
            'catatan'        => $r['catatan'],
        ];
    }
}
