<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SbmRepository;

/**
 * Admin SBM: tambah regulasi/KMK, kelola item, ubah harga. Hanya role admin.
 */
class Sbm extends BaseController
{
    private SbmRepository $repo;

    public function __construct()
    {
        $this->repo = new SbmRepository();
    }

    private function user(): string
    {
        return (string)(session()->get('name') ?: 'admin');
    }

    // ---------- Daftar regulasi ----------

    public function index()
    {
        return view('admin/sbm/index', [
            'title'    => 'Kelola Standar Biaya SBM',
            'regulasi' => $this->repo->regulasiList(),
            'log'      => $this->repo->recentLog(15),
        ]);
    }

    public function createRegulasi()
    {
        $rules = [
            'jenis'          => 'required|in_list[PMK,KMK]',
            'nomor'          => 'required|max_length[60]',
            'judul'          => 'required|max_length[200]',
            'tahun_anggaran' => 'required|integer|greater_than[2000]|less_than[2100]',
            'status'         => 'required|in_list[berlaku,belum_berlaku,dicabut]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $db = $this->repo->db();
        $now = date('Y-m-d H:i:s');
        $db->table('sbm_regulasi')->insert([
            'jenis'          => $this->request->getPost('jenis'),
            'nomor'          => trim((string)$this->request->getPost('nomor')),
            'judul'          => trim((string)$this->request->getPost('judul')),
            'tahun_anggaran' => (int)$this->request->getPost('tahun_anggaran'),
            'status'         => $this->request->getPost('status'),
            'punya_nominal'  => $this->request->getPost('punya_nominal') ? 1 : 0,
            'catatan'        => trim((string)$this->request->getPost('catatan')) ?: null,
            'created_at'     => $now, 'updated_at' => $now,
        ]);
        $newId = (int)$db->insertID();

        $salin = (int)$this->request->getPost('salin_dari');
        if ($salin > 0 && $this->repo->regulasi($salin)) {
            $this->repo->salinRegulasi($salin, $newId);
        }
        $this->repo->log(null, null, 'Regulasi baru #' . $newId, null, $this->request->getPost('nomor'), $this->user());

        return redirect()->to(base_url('admin/sbm/regulasi/' . $newId))->with('success', 'Regulasi ditambahkan.');
    }

    // ---------- Satu regulasi ----------

    public function regulasi(int $id)
    {
        $reg = $this->repo->regulasi($id);
        if (!$reg) {
            return redirect()->to(base_url('admin/sbm'))->with('error', 'Regulasi tidak ditemukan.');
        }
        return view('admin/sbm/regulasi', [
            'title' => 'Regulasi ' . $reg['jenis'] . ' ' . $reg['nomor'],
            'reg'   => $reg,
            'items' => $this->repo->itemsByRegulasi($id),
        ]);
    }

    public function updateRegulasi(int $id)
    {
        if (!$this->repo->regulasi($id)) {
            return redirect()->to(base_url('admin/sbm'))->with('error', 'Regulasi tidak ditemukan.');
        }
        $rules = [
            'nomor'          => 'required|max_length[60]',
            'judul'          => 'required|max_length[200]',
            'tahun_anggaran' => 'required|integer|greater_than[2000]|less_than[2100]',
            'status'         => 'required|in_list[berlaku,belum_berlaku,dicabut]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        $this->repo->db()->table('sbm_regulasi')->where('id', $id)->update([
            'nomor'          => trim((string)$this->request->getPost('nomor')),
            'judul'          => trim((string)$this->request->getPost('judul')),
            'tahun_anggaran' => (int)$this->request->getPost('tahun_anggaran'),
            'status'         => $this->request->getPost('status'),
            'punya_nominal'  => $this->request->getPost('punya_nominal') ? 1 : 0,
            'catatan'        => trim((string)$this->request->getPost('catatan')) ?: null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $this->repo->log(null, null, 'Ubah regulasi #' . $id, null, null, $this->user());
        return redirect()->back()->with('success', 'Regulasi diperbarui.');
    }

    public function deleteRegulasi(int $id)
    {
        if ($this->repo->regulasi($id)) {
            $this->repo->db()->table('sbm_regulasi')->where('id', $id)->delete();
            $this->repo->log(null, null, 'Hapus regulasi #' . $id, null, null, $this->user());
        }
        return redirect()->to(base_url('admin/sbm'))->with('success', 'Regulasi dihapus.');
    }

    // ---------- Item ----------

    public function createItem(int $regId)
    {
        $rules = [
            'lampiran' => 'required|in_list[I,II]',
            'kode'     => 'required|max_length[10]',
            'nama'     => 'required|max_length[250]',
            'sifat'    => 'required|in_list[batas_tertinggi,dapat_dilampaui]',
        ];
        if (!$this->repo->regulasi($regId) || !$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Data item tidak valid.');
        }
        $exists = $this->repo->db()->table('sbm_item')->where([
            'regulasi_id' => $regId, 'lampiran' => $this->request->getPost('lampiran'),
            'kode' => trim((string)$this->request->getPost('kode')),
        ])->countAllResults();
        if ($exists) {
            return redirect()->back()->withInput()->with('error', 'Nomor item itu sudah ada di lampiran tersebut.');
        }
        $this->repo->db()->table('sbm_item')->insert([
            'regulasi_id'  => $regId,
            'lampiran'     => $this->request->getPost('lampiran'),
            'kode'         => trim((string)$this->request->getPost('kode')),
            'kode_kanonik' => trim((string)$this->request->getPost('kode_kanonik')) ?: null,
            'nama'         => trim((string)$this->request->getPost('nama')),
            'sifat'        => $this->request->getPost('sifat'),
        ]);
        $id = (int)$this->repo->db()->insertID();
        $this->repo->log(null, $id, 'Item baru', null, (string)$this->request->getPost('nama'), $this->user());
        return redirect()->to(base_url('admin/sbm/item/' . $id))->with('success', 'Item ditambahkan.');
    }

    public function item(int $id)
    {
        $item = $this->repo->item($id);
        if (!$item) {
            return redirect()->to(base_url('admin/sbm'))->with('error', 'Item tidak ditemukan.');
        }
        $wilId = $this->request->getGet('prov');
        $wilId = $wilId === null || $wilId === '' ? $this->repo->defaultWilayahId() : (int)$wilId;

        $q = (string)$this->request->getGet('q');
        $res = $this->repo->hargaFiltered($id, $wilId ?: null, $q, 300);

        return view('admin/sbm/item', [
            'title'   => 'Edit ' . $item['nama'],
            'item'    => $item,
            'reg'     => $this->repo->regulasi((int)$item['regulasi_id']),
            'wilayah' => $this->repo->wilayahList(),
            'wil_id'  => $wilId,
            'harga'   => $res['rows'],
            'shown_total' => $res['total'],
            'q'       => trim($q),
            'total'   => $this->repo->hargaCount($id),
        ]);
    }

    public function updateItem(int $id)
    {
        $rules = [
            'nama'  => 'required|max_length[250]',
            'sifat' => 'required|in_list[batas_tertinggi,dapat_dilampaui]',
        ];
        if (!$this->repo->item($id) || !$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Data item tidak valid.');
        }
        $this->repo->db()->table('sbm_item')->where('id', $id)->update([
            'nama'               => trim((string)$this->request->getPost('nama')),
            'sifat'              => $this->request->getPost('sifat'),
            'kode_kanonik'       => trim((string)$this->request->getPost('kode_kanonik')) ?: null,
            'deskripsi'          => trim((string)$this->request->getPost('deskripsi')) ?: null,
            'penjelasan'         => trim((string)$this->request->getPost('penjelasan')) ?: null,
            'halaman_tabel'      => trim((string)$this->request->getPost('halaman_tabel')) ?: null,
            'halaman_penjelasan' => trim((string)$this->request->getPost('halaman_penjelasan')) ?: null,
        ]);
        $this->repo->log(null, $id, 'Ubah teks item', null, null, $this->user());
        return redirect()->back()->with('success', 'Item diperbarui.');
    }

    public function deleteItem(int $id)
    {
        $item = $this->repo->item($id);
        if ($item) {
            $this->repo->db()->table('sbm_item')->where('id', $id)->delete();
            $this->repo->log(null, $id, 'Hapus item', $item['nama'], null, $this->user());
            return redirect()->to(base_url('admin/sbm/regulasi/' . $item['regulasi_id']))->with('success', 'Item dihapus.');
        }
        return redirect()->to(base_url('admin/sbm'));
    }

    // ---------- Harga ----------

    /** Ubah besaran satu baris (AJAX). Contoh: snack 24.000 → 25.000. */
    public function updateHarga(int $id)
    {
        $row = $this->repo->db()->table('sbm_harga')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Baris tidak ditemukan.']);
        }
        $raw = preg_replace('/[^\d]/', '', (string)$this->request->getPost('besaran'));
        if ($raw === '' || strlen($raw) > 13) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'message' => 'Besaran harus berupa angka rupiah.']);
        }
        $baru = (int)$raw;
        $this->repo->db()->table('sbm_harga')->where('id', $id)->update(['besaran' => $baru, 'needs_review' => 0]);
        $this->repo->log($id, (int)$row['item_id'], $row['kelompok'] . ' - ' . $row['kolom'],
            (string)$row['besaran'], (string)$baru, $this->user());

        return $this->response->setJSON([
            'status'    => 'success',
            'besaran'   => $baru,
            'formatted' => 'Rp' . number_format($baru, 0, ',', '.'),
        ]);
    }

    public function createHarga(int $itemId)
    {
        $rules = ['kelompok' => 'required|max_length[200]', 'kolom' => 'required|max_length[100]'];
        if (!$this->repo->item($itemId) || !$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Uraian dan kolom wajib diisi.');
        }
        $raw = preg_replace('/[^\d]/', '', (string)$this->request->getPost('besaran'));
        if ($raw === '') {
            return redirect()->back()->withInput()->with('error', 'Besaran harus berupa angka.');
        }
        $wil = (int)$this->request->getPost('wilayah_id');
        $kelompok = trim((string)$this->request->getPost('kelompok'));
        $kolom = trim((string)$this->request->getPost('kolom'));
        $this->repo->db()->table('sbm_harga')->insert([
            'item_id'    => $itemId, 'kelompok' => $kelompok, 'kolom' => $kolom,
            'sub_uraian' => $kelompok . ' - ' . $kolom,
            'wilayah_id' => $wil ?: null,
            'satuan'     => trim((string)$this->request->getPost('satuan')) ?: null,
            'besaran'    => (int)$raw,
            'urutan'     => 9999,
        ]);
        $this->repo->log((int)$this->repo->db()->insertID(), $itemId, 'Baris baru: ' . $kelompok . ' - ' . $kolom, null, $raw, $this->user());
        return redirect()->back()->with('success', 'Baris harga ditambahkan.');
    }

    public function deleteHarga(int $id)
    {
        $row = $this->repo->db()->table('sbm_harga')->where('id', $id)->get()->getRowArray();
        if ($row) {
            $this->repo->db()->table('sbm_harga')->where('id', $id)->delete();
            $this->repo->log($id, (int)$row['item_id'], 'Hapus baris: ' . $row['kelompok'] . ' - ' . $row['kolom'],
                (string)$row['besaran'], null, $this->user());
        }
        return redirect()->back()->with('success', 'Baris harga dihapus.');
    }
}
