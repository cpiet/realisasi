<?php
/**
 * Pustaka bersama ETL SBM: normalisasi teks, pola angka/satuan, kunci provinsi.
 * ETL dijalankan di luar aplikasi (tidak ikut produksi).
 */

const AMT_RP  = 'Rp\s?\d{1,3}(?:\.\d{3})*';
const AMT_USD = '\$\s?\d{1,3}(?:[,.]\d{3})*|\d{1,3}(?:[,.]\d{3})*';
const AMT_TXT = 'Sesuai tarif berlaku';

/** Daftar satuan yang dikenal, terpanjang dulu. */
function satuanRegex(): string
{
    static $re = null;
    if ($re) {
        return $re;
    }
    $list = [
        'Orang/Mahasiswa/Semester', 'Orang/Mahasiswa/Bulan', 'Mahasiswa/Semester', 'Orang/Mahasiswa', 'Orang/Rotasi',
        'Orang/Semester', 'Orang/Mata Uji', 'Orang/Kali', 'Orang Per Proposal', 'Per Mahasiswa Lulus',
        'Per Mahasiswa/Semester', 'Per Mahasiswa', 'Per Mata Kuliah', 'Per Peserta', 'Per Naskah', 'Per Laporan',
        'Per Judul', 'Per Halaman', 'Per Butir Soal', 'Per Modul', 'Per Tahun', 'Per hari', 'Per bulan',
        'Naskah/Pelajaran', 'Siswa/Mata Ujian', 'Mahasiswa/Mata Ujian', 'Penelitian/ ?Perekayasaan', 'SKS/Hadir',
        'Jam/Hadir', 'Pasien/Jam', 'Halaman Jadi', 'Pegawai/Tahun', 'Unit/Tahun', 'Unit/Bulan', 'Satker/Tahun',
        'km/m ?3', 'm ?2/tahun', 'm ?3', 'Judul', 'Unit', 'Setel', 'Orang', 'OJP', 'Oter', 'OB', 'OP', 'OH', 'OJ',
        'OK', 'OR', 'OT',
    ];
    usort($list, static fn ($a, $b) => strlen($b) <=> strlen($a));
    return $re = str_replace('/', '\\/', '(?:' . implode('|', $list) . ')');
}

function norm(string $s): string
{
    $s = str_replace(["\t", "\xC2\xA0", "\r"], ' ', $s);
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    return trim($s);
}

function isNoise(string $t): bool
{
    return $t === '' || preg_match('/^- \d+ -$/', $t) || preg_match('/^NO\.?(\s|$)/', $t)
        || preg_match('/^\(\d+\)(\s+\(\d+\))*\s*\)?$/', $t) || preg_match('/^\(dalam US\$\)$/i', $t)
        || preg_match('/^\(\d+\s*$/', $t) || $t === ')' || $t === '(3';
}

/** Kunci pembanding nama: huruf/angka saja, huruf kecil; "sumatera"→"sumatra". */
function nkey(string $s): string
{
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/[^a-z0-9]+/', '', $s) ?? '';
    return str_replace('sumatera', 'sumatra', $s);
}

function provinsiKeyMap(): array
{
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach (\App\Libraries\SbmSeedData::PROVINSI as $i => [$nama, $alias]) {
            $map[nkey($nama)] = $i;
            $map[nkey($alias)] = $i;
        }
        $map[nkey('DKI Jakarta')] = 12;
    }
    return $map;
}

function amountToInt(string $a, ?string &$cur, bool &$odd): ?int
{
    $odd = false;
    if (stripos($a, 'Sesuai') === 0) {
        return null;
    }
    $cur = str_contains($a, '$') ? 'USD' : ($cur ?? 'IDR');
    if (str_starts_with($a, 'Rp')) {
        return (int)preg_replace('/\D/', '', $a);
    }
    // angka USD: pemisah ribuan "," (di beberapa baris PDF memakai "." — ditandai).
    if (preg_match('/^\$?\s?\d{1,3}\.\d{3}$/', $a) && $cur !== 'IDR') {
        $odd = true;
    }
    return (int)preg_replace('/\D/', '', $a);
}
