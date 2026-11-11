<?php

namespace App\Libraries;

/**
 * Daftar baku 38 provinsi untuk modul SBM. Data nominal ada di app/Libraries/sbm_data/*.json
 * (hasil ETL dari PDF, lihat tools/sbm_etl); ubah harga lewat halaman admin, bukan di sini.
 */
class SbmSeedData
{
    /** Urutan baku 38 provinsi sesuai tabel PMK 39/2024. [nama baku, ejaan di PDF] */
    public const PROVINSI = [
        ['Aceh', 'ACEH'], ['Sumatra Utara', 'SUMATRA UTARA'], ['Riau', 'RIAU'],
        ['Kepulauan Riau', 'KEPULAUAN RIAU'], ['Jambi', 'JAMBI'], ['Sumatra Barat', 'SUMATRA BARAT'],
        ['Sumatra Selatan', 'SUMATRA SELATAN'], ['Lampung', 'LAMPUNG'], ['Bengkulu', 'BENGKULU'],
        ['Bangka Belitung', 'BANGKA BELITUNG'], ['Banten', 'BANTEN'], ['Jawa Barat', 'JAWA BARAT'],
        ['DKI Jakarta', 'D.K.I. JAKARTA'], ['Jawa Tengah', 'JAWA TENGAH'], ['DI Yogyakarta', 'D.I. YOGYAKARTA'],
        ['Jawa Timur', 'JAWA TIMUR'], ['Bali', 'BALI'], ['Nusa Tenggara Barat', 'NUSA TENGGARA BARAT'],
        ['Nusa Tenggara Timur', 'NUSA TENGGARA TIMUR'], ['Kalimantan Barat', 'KALIMANTAN BARAT'],
        ['Kalimantan Tengah', 'KALIMANTAN TENGAH'], ['Kalimantan Selatan', 'KALIMANTAN SELATAN'],
        ['Kalimantan Timur', 'KALIMANTAN TIMUR'], ['Kalimantan Utara', 'KALIMANTAN UTARA'],
        ['Sulawesi Utara', 'SULAWESI UTARA'], ['Gorontalo', 'GORONTALO'], ['Sulawesi Barat', 'SULAWESI BARAT'],
        ['Sulawesi Selatan', 'SULAWESI SELATAN'], ['Sulawesi Tengah', 'SULAWESI TENGAH'],
        ['Sulawesi Tenggara', 'SULAWESI TENGGARA'], ['Maluku', 'MALUKU'], ['Maluku Utara', 'MALUKU UTARA'],
        ['Papua', 'PAPUA'], ['Papua Barat', 'PAPUA BARAT'], ['Papua Barat Daya', 'PAPUA BARAT DAYA'],
        ['Papua Tengah', 'PAPUA TENGAH'], ['Papua Selatan', 'PAPUA SELATAN'], ['Papua Pegunungan', 'PAPUA PEGUNUNGAN'],
    ];
}
