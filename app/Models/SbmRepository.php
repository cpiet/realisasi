<?php

namespace App\Models;

use App\Libraries\SbmDb;
use CodeIgniter\Database\BaseConnection;

/**
 * Akses data modul SBM (database terpisah: sbm.sqlite).
 * Semua query memakai query builder / binding.
 */
class SbmRepository
{
    public const SIFAT = [
        'batas_tertinggi' => 'Batas tertinggi',
        'dapat_dilampaui' => 'Dapat dilampaui',
    ];

    /** Sinonim kata kunci -> frasa yang dicari (semua kata frasa harus cocok). */
    public const SINONIM = [
        'ppk' => 'pembuat komitmen',
        'kpa' => 'kuasa pengguna anggaran',
        'narsum' => 'narasumber',
        'hotel' => 'penginapan',
        'snack' => 'kudapan',
        'honor' => 'honorarium',
        'sppd' => 'perjalanan dinas',
        'bendahara' => 'bendahara pengeluaran',
        'pokja' => 'kelompok kerja pemilihan',
        'pbj' => 'pengadaan barang',
        'bbm' => 'bahan bakar',
    ];

    public const MAX_QUERY_LENGTH = 100;
    public const MAX_RESULTS = 20;

    private BaseConnection $db;

    public function __construct()
    {
        $this->db = SbmDb::connect();
    }

    // ---------- Regulasi & wilayah ----------

    public function regulasiList(): array
    {
        return $this->db->table('sbm_regulasi')
            ->orderBy('tahun_anggaran', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
    }

    public function regulasi(int $id): ?array
    {
        return $this->db->table('sbm_regulasi')->where('id', $id)->get()->getRowArray() ?: null;
    }

    /** Regulasi terbaru yang memiliki nominal (default tampilan), atau regulasi tahun tertentu jika diminta. */
    public function defaultRegulasi(?int $year = null): ?array
    {
        if ($year !== null && $year > 0) {
            $byYear = $this->db->table('sbm_regulasi')
                ->where('tahun_anggaran', $year)
                ->where('status !=', 'dicabut')
                ->orderBy('punya_nominal', 'DESC')
                ->orderBy('id', 'DESC')
                ->get(1)->getRowArray();
            if ($byYear) {
                return $byYear;
            }
        }

        return $this->db->table('sbm_regulasi')
            ->where('punya_nominal', 1)->where('status !=', 'dicabut')
            ->orderBy('tahun_anggaran', 'DESC')->orderBy('id', 'DESC')
            ->get(1)->getRowArray() ?: null;
    }

    public function wilayahList(): array
    {
        return $this->db->table('sbm_wilayah')->orderBy('urutan')->get()->getResultArray();
    }

    public function defaultWilayahId(): ?int
    {
        $row = $this->db->table('sbm_wilayah')->where('nama', 'DKI Jakarta')->get()->getRowArray();
        return $row ? (int)$row['id'] : null;
    }

    public static function sumber(array $regulasi, array $item, ?string $halaman = null): string
    {
        $parts = [
            $regulasi['jenis'] . ' ' . $regulasi['nomor'],
            $regulasi['tahun_anggaran'] ? 'TA ' . $regulasi['tahun_anggaran'] : null,
            'Lampiran ' . $item['lampiran'] . ' No. ' . $item['kode'],
        ];
        $hal = array_filter([
            $item['halaman_tabel'] ? 'tabel hlm. ' . $item['halaman_tabel'] : null,
            $item['halaman_penjelasan'] ? 'ketentuan hlm. ' . $item['halaman_penjelasan'] : null,
        ]);
        if ($hal) {
            $parts[] = implode(', ', $hal);
        }
        return implode(' · ', array_filter($parts));
    }

    // ---------- Pencarian ----------

    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? '';
        $text = preg_replace('/\bfull\s+board\b/u', 'fullboard', $text) ?? $text;
        $text = preg_replace('/\bfull\s+day\b/u', 'fullday', $text) ?? $text;
        $text = preg_replace('/\bhalf\s+day\b/u', 'halfday', $text) ?? $text;
        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /**
     * @return list<array<string,mixed>> item beserta baris harga yang cocok, berurut relevansi.
     */
    public function search(int $regulasiId, string $query, ?int $wilayahId): array
    {
        $query = mb_substr(trim($query), 0, self::MAX_QUERY_LENGTH, 'UTF-8');
        $words = array_values(array_filter(explode(' ', self::normalize($query))));
        $expanded = [];
        foreach ($words as $w) {
            foreach (explode(' ', self::SINONIM[$w] ?? $w) as $x) {
                $expanded[] = $x;
            }
        }
        $words = $expanded;
        if ($words === [] || mb_strlen(implode('', $words)) < 2) {
            return [];
        }

        $items = $this->db->table('sbm_item')->where('regulasi_id', $regulasiId)
            ->orderBy('lampiran')->orderBy('CAST(kode AS INTEGER)', 'ASC', false)->get()->getResultArray();
        if ($items === []) {
            return [];
        }

        $aliasByKode = [];
        foreach ($this->db->table('sbm_alias')->get()->getResultArray() as $a) {
            $aliasByKode[$a['kode_kanonik']][] = $a['kata'];
        }

        $hargaByItem = $this->hargaByItems(array_column($items, 'id'), $wilayahId);

        $results = [];
        foreach ($items as $item) {
            $nameHay = self::normalize($item['nama']);
            $aliasHay = self::normalize(implode(' ', $aliasByKode[$item['kode_kanonik'] ?? ''] ?? []));
            $itemHay = $nameHay . ' ' . $aliasHay;
            $rows = $hargaByItem[(int)$item['id']] ?? [];

            if ($rows === []) {
                $all = true;
                foreach ($words as $w) {
                    if (!str_contains($itemHay, $w)) {
                        $all = false;
                        break;
                    }
                }
                if (!$all) {
                    continue;
                }
                $score = 10;
                $matched = [];
            } else {
                $matched = [];
                foreach ($rows as $row) {
                    $hay = $itemHay . ' ' . self::normalize($row['kelompok'] . ' ' . $row['kolom'] . ' ' . ($row['sub_uraian'] ?? ''));
                    $ok = true;
                    foreach ($words as $w) {
                        if (!str_contains($hay, $w)) {
                            $ok = false;
                            break;
                        }
                    }
                    if ($ok) {
                        $matched[] = $row;
                    }
                }
                if ($matched === []) {
                    continue;
                }
                $score = 10;
            }

            foreach ($words as $w) {
                if (str_contains($nameHay, $w)) {
                    $score += 5;
                } elseif (str_contains($aliasHay, $w)) {
                    $score += 3;
                }
            }
            if ($item['sifat'] === 'batas_tertinggi') {
                $score += 1;
            }

            $item['rows'] = $matched;
            $item['_score'] = $score;
            $results[] = $item;
        }

        usort($results, static fn ($a, $b) => $b['_score'] <=> $a['_score']);
        return array_slice($results, 0, self::MAX_RESULTS);
    }

    /** @param list<int|string> $itemIds @return array<int,list<array<string,mixed>>> */
    private function hargaByItems(array $itemIds, ?int $wilayahId): array
    {
        $b = $this->db->table('sbm_harga')->whereIn('item_id', $itemIds);
        if ($wilayahId !== null) {
            $b->groupStart()->where('wilayah_id', $wilayahId)->orWhere('wilayah_id', null)->groupEnd();
        }
        $out = [];
        foreach ($b->orderBy('urutan')->orderBy('id')->get()->getResultArray() as $row) {
            $out[(int)$row['item_id']][] = $row;
        }
        return $out;
    }

    // ---------- Item ----------

    public function item(int $id): ?array
    {
        return $this->db->table('sbm_item')->where('id', $id)->get()->getRowArray() ?: null;
    }

    public function itemsByRegulasi(int $regulasiId): array
    {
        return $this->db->table('sbm_item')->where('regulasi_id', $regulasiId)
            ->orderBy('lampiran')->orderBy('CAST(kode AS INTEGER)', 'ASC', false)->get()->getResultArray();
    }

    /** Baris harga item untuk satu wilayah (plus baris tanpa wilayah). */
    public function hargaItem(int $itemId, ?int $wilayahId): array
    {
        return $this->hargaByItems([$itemId], $wilayahId)[$itemId] ?? [];
    }

    /** Baris harga item untuk wilayah tertentu + filter teks (binding), dibatasi $limit. @return array{rows:list<array>,total:int} */
    public function hargaFiltered(int $itemId, ?int $wilayahId, string $q, int $limit): array
    {
        $b = $this->db->table('sbm_harga')->where('item_id', $itemId);
        if ($wilayahId !== null) {
            $b->groupStart()->where('wilayah_id', $wilayahId)->orWhere('wilayah_id', null)->groupEnd();
        }
        $q = mb_substr(trim($q), 0, self::MAX_QUERY_LENGTH, 'UTF-8');
        foreach (array_filter(explode(' ', $q)) as $w) {
            $b->groupStart()->like('kelompok', $w)->orLike('kolom', $w)->orLike('sub_uraian', $w)->groupEnd();
        }
        $total = (clone $b)->countAllResults(false);
        $rows = $b->orderBy('urutan')->orderBy('id')->get($limit)->getResultArray();
        return ['rows' => $rows, 'total' => $total];
    }

    public function hargaCount(int $itemId): int
    {
        return $this->db->table('sbm_harga')->where('item_id', $itemId)->countAllResults();
    }

    /** Padanan item di regulasi lain lewat kode_kanonik (untuk perbandingan/acuan). */
    public function padanan(array $item): array
    {
        if (empty($item['kode_kanonik'])) {
            return [];
        }
        return $this->db->table('sbm_item i')
            ->select('i.id, i.kode, i.lampiran, r.jenis, r.nomor, r.tahun_anggaran, r.punya_nominal, r.id AS regulasi_id')
            ->join('sbm_regulasi r', 'r.id = i.regulasi_id')
            ->where('i.kode_kanonik', $item['kode_kanonik'])->where('i.id !=', $item['id'])
            ->orderBy('r.tahun_anggaran', 'DESC')->get()->getResultArray();
    }

    // ---------- Admin: tulis data ----------

    public function db(): BaseConnection
    {
        return $this->db;
    }

    public function log(?int $hargaId, ?int $itemId, string $ket, ?string $lama, ?string $baru, string $oleh): void
    {
        $this->db->table('sbm_log')->insert([
            'harga_id' => $hargaId, 'item_id' => $itemId, 'keterangan' => $ket,
            'nilai_lama' => $lama, 'nilai_baru' => $baru, 'oleh' => $oleh,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Salin seluruh item + harga sebuah regulasi ke regulasi baru (untuk KMK/PMK tahun baru). */
    public function salinRegulasi(int $dariId, int $keId): void
    {
        $this->db->transStart();
        foreach ($this->itemsByRegulasi($dariId) as $item) {
            $oldId = (int)$item['id'];
            unset($item['id']);
            $item['regulasi_id'] = $keId;
            $this->db->table('sbm_item')->insert($item);
            $newId = (int)$this->db->insertID();
            foreach ($this->db->table('sbm_harga')->where('item_id', $oldId)->get()->getResultArray() as $h) {
                unset($h['id']);
                $h['item_id'] = $newId;
                $this->db->table('sbm_harga')->insert($h);
            }
        }
        $this->db->transComplete();
    }

    public function recentLog(int $limit = 30): array
    {
        return $this->db->table('sbm_log')->orderBy('id', 'DESC')->get($limit)->getResultArray();
    }
}
