<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SimranSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // 1. Admin User
        $userModel = $db->table('users');
        if ($userModel->countAllResults() === 0) {
            $userModel->insert([
                'username'      => 'admin',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'name'          => 'Administrator Divhumas',
                'role'          => 'admin',
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        // 2. Budget Types
        $btModel = $db->table('budget_types');
        if ($btModel->countAllResults() === 0) {
            $btModel->insertBatch([
                ['id' => 1, 'code' => '51', 'name' => 'Belanja Pegawai', 'color' => '#1d9e75', 'created_at' => date('Y-m-d H:i:s')],
                ['id' => 2, 'code' => '52', 'name' => 'Belanja Barang',  'color' => '#e0951f', 'created_at' => date('Y-m-d H:i:s')],
                ['id' => 3, 'code' => '53', 'name' => 'Belanja Modal',   'color' => '#d8514f', 'created_at' => date('Y-m-d H:i:s')],
            ]);
        }

        // 3. Fiscal Year 2026
        $fyModel = $db->table('fiscal_years');
        $fy2026 = $fyModel->where('year', 2026)->get()->getRowArray();
        if (!$fy2026) {
            $fyModel->insert([
                'year'       => 2026,
                'name'       => 'T.A. 2026',
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $fyId = $db->insertID();
        } else {
            $fyId = $fy2026['id'];
        }

        // 4. Absorption Targets 2026
        $targetModel = $db->table('absorption_targets');
        $targetModel->where('fiscal_year_id', $fyId)->delete();
        $targetModel->insertBatch([
            ['fiscal_year_id' => $fyId, 'budget_type_id' => 1, 'q1' => 20, 'q2' => 50, 'q3' => 75, 'q4' => 95, 'created_at' => date('Y-m-d H:i:s')],
            ['fiscal_year_id' => $fyId, 'budget_type_id' => 2, 'q1' => 15, 'q2' => 40, 'q3' => 70, 'q4' => 90, 'created_at' => date('Y-m-d H:i:s')],
            ['fiscal_year_id' => $fyId, 'budget_type_id' => 3, 'q1' => 10, 'q2' => 40, 'q3' => 70, 'q4' => 90, 'created_at' => date('Y-m-d H:i:s')],
        ]);

        // 5. IKPA Values 2026
        $ikpaModel = $db->table('ikpa_values');
        if ($ikpaModel->where('fiscal_year_id', $fyId)->countAllResults() === 0) {
            $ikpaModel->insert([
                'fiscal_year_id' => $fyId,
                'revisi'         => null,
                'deviasi'        => null,
                'penyerapan'     => null,
                'kontraktual'    => null,
                'tagihan'        => null,
                'uptup'          => null,
                'output'         => null,
                'dispensasi'     => 0,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
        }

        // 6. Programs & Activities based on authentic LRA_SAMPLE_DATA.md
        $progModel = $db->table('programs');
        $progModel->where('fiscal_year_id', $fyId)->delete();

        // Program 1: Dukungan Manajemen
        $progModel->insert([
            'fiscal_year_id' => $fyId,
            'code'           => '060.01.WA',
            'name'           => 'Program Dukungan Manajemen Polri',
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        $progWaId = $db->insertID();

        // Program 2: Modernisasi Almatsus & Sarpras
        $progModel->insert([
            'fiscal_year_id' => $fyId,
            'code'           => '060.01.BP',
            'name'           => 'Program Modernisasi Almatsus dan Sarana Prasarana Polri',
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        $progBpId = $db->insertID();

        // Activities
        $actModel = $db->table('activities');
        $actModel->where('fiscal_year_id', $fyId)->delete();

        $actList = [
            ['prog_id' => $progWaId, 'code' => '3070', 'name' => 'Penerangan Masyarakat', 'unit_name' => 'Biro Penmas'],
            ['prog_id' => $progWaId, 'code' => '3073', 'name' => 'Dukungan Pelayanan Internal Perkantoran Polri', 'unit_name' => 'Bagrenmin & Biro Penmas'],
            ['prog_id' => $progWaId, 'code' => '3100', 'name' => 'Pengelolaan Informasi, Dokumentasi & Multimedia', 'unit_name' => 'Biro Mulmed'],
            ['prog_id' => $progBpId, 'code' => '3084', 'name' => 'Sistem Aplikasi & Sarana Prasarana IT Humas', 'unit_name' => 'Biro Multimedia / SPIT'],
        ];

        $actMap = [];
        foreach ($actList as $a) {
            $actModel->insert([
                'fiscal_year_id' => $fyId,
                'program_id'     => $a['prog_id'],
                'code'           => $a['code'],
                'name'           => $a['name'],
                'unit_name'      => $a['unit_name'],
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
            $actMap[$a['code']] = $db->insertID();
        }

        // 7. Budget Details — Real Divhumas Structure from LRA_SAMPLE_DATA.md
        // Pegawai: Rp 20.990.835.000
        // Barang:  Rp 110.216.330.000
        // Modal:   Rp 115.000.000.000
        // Total:   Rp 246.207.165.000
        $bdModel = $db->table('budget_details');
        $bdModel->where('fiscal_year_id', $fyId)->delete();

        $bdItems = [
            // Belanja Pegawai (51)
            [
                'key' => 'pegawai_gaji',
                'fiscal_year_id' => $fyId, 'program_id' => $progWaId, 'activity_id' => $actMap['3073'],
                'budget_type_id' => 1, 'kro_code' => '3073.EAA', 'kro_name' => 'Layanan Perkantoran',
                'ro_code' => '3073.EAA.994', 'ro_name' => 'Gaji dan Tunjangan',
                'component_code' => '001', 'component_name' => 'Gaji dan Tunjangan Melekat',
                'subcomponent_code' => 'A', 'subcomponent_name' => 'Gaji Pokok & Tunjangan Personel Divhumas',
                'account_code' => '511111', 'description' => 'Belanja Gaji dan Tunjangan Personel Divhumas Polri',
                'initial_ceiling' => 20990835000, 'final_ceiling' => 20990835000,
                'created_at' => date('Y-m-d H:i:s'),
            ],

            // Belanja Barang (52) — Total Rp 110.216.330.000
            [
                'key' => 'penum',
                'fiscal_year_id' => $fyId, 'program_id' => $progWaId, 'activity_id' => $actMap['3070'],
                'budget_type_id' => 2, 'kro_code' => '3070.QIA', 'kro_name' => 'Layanan Penerangan Umum',
                'ro_code' => '3070.QIA.001', 'ro_name' => 'Diseminasi Penerangan Umum',
                'component_code' => '001', 'component_name' => 'Konferensi Pers & Liputan Media',
                'subcomponent_code' => 'A', 'subcomponent_name' => 'Dukungan Operasional Bag Penum',
                'account_code' => '522119', 'description' => 'Peliputan, Konferensi Pers & Diseminasi Penerangan Umum (Bag Penum)',
                'initial_ceiling' => 29959730000, 'final_ceiling' => 29959730000,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'key' => 'pensat',
                'fiscal_year_id' => $fyId, 'program_id' => $progWaId, 'activity_id' => $actMap['3070'],
                'budget_type_id' => 2, 'kro_code' => '3070.QIB', 'kro_name' => 'Layanan Penerangan Satuan',
                'ro_code' => '3070.QIB.002', 'ro_name' => 'Penyelenggaraan Pensat Polri',
                'component_code' => '001', 'component_name' => 'Materi Penerangan Satuan & Personel',
                'subcomponent_code' => 'A', 'subcomponent_name' => 'Dukungan Operasional Bag Pensat',
                'account_code' => '521219', 'description' => 'Pelayanan Informasi & Penerangan Satuan (Bag Pensat)',
                'initial_ceiling' => 12784417000, 'final_ceiling' => 12784417000,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'key' => 'mitra',
                'fiscal_year_id' => $fyId, 'program_id' => $progWaId, 'activity_id' => $actMap['3070'],
                'budget_type_id' => 2, 'kro_code' => '3070.QIC', 'kro_name' => 'Layanan Kemitraan Media',
                'ro_code' => '3070.QIC.003', 'ro_name' => 'Kemitraan Strategis & Hubungan Luar Negeri',
                'component_code' => '001', 'component_name' => 'Bakohumas & Kerjasama Lembaga',
                'subcomponent_code' => 'A', 'subcomponent_name' => 'Dukungan Operasional Bag Mitra',
                'account_code' => '521219', 'description' => 'Penguatan Jaringan Kerja & Kemitraan Strategis Pers (Bag Mitra)',
                'initial_ceiling' => 3476495000, 'final_ceiling' => 3476495000,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'key' => 'urkeu',
                'fiscal_year_id' => $fyId, 'program_id' => $progWaId, 'activity_id' => $actMap['3073'],
                'budget_type_id' => 2, 'kro_code' => '3073.EBA', 'kro_name' => 'Operasional Perkantoran',
                'ro_code' => '3073.EBA.994', 'ro_name' => 'Dukungan Operasional Perkantoran',
                'component_code' => '002', 'component_name' => 'Operasional Kantor & Keuangan',
                'subcomponent_code' => 'A', 'subcomponent_name' => 'Urkeu & Dukops Kadiv Humas',
                'account_code' => '521111', 'description' => 'Dukungan Operasional & Administrasi Perkantoran (Urkeu & Dukops Kadiv)',
                'initial_ceiling' => 11992661000, 'final_ceiling' => 11992661000,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'key' => 'urtu_penmas',
                'fiscal_year_id' => $fyId, 'program_id' => $progWaId, 'activity_id' => $actMap['3073'],
                'budget_type_id' => 2, 'kro_code' => '3073.EBA', 'kro_name' => 'Operasional Perkantoran',
                'ro_code' => '3073.EBA.994', 'ro_name' => 'Dukungan Operasional Perkantoran Penmas',
                'component_code' => '002', 'component_name' => 'Operasional Kantor Penmas',
                'subcomponent_code' => 'B', 'subcomponent_name' => 'Urtu Penmas',
                'account_code' => '521211', 'description' => 'Dukungan Operasional URTU Penmas',
                'initial_ceiling' => 134365000, 'final_ceiling' => 134365000,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'key' => 'internet',
                'fiscal_year_id' => $fyId, 'program_id' => $progWaId, 'activity_id' => $actMap['3073'],
                'budget_type_id' => 2, 'kro_code' => '3073.EBA', 'kro_name' => 'Operasional Perkantoran',
                'ro_code' => '3073.EBA.994', 'ro_name' => 'Daya dan Jasa IT',
                'component_code' => '002', 'component_name' => 'Langganan Internet & Bandwidth',
                'subcomponent_code' => 'C', 'subcomponent_name' => 'Bagrenmin (Internet)',
                'account_code' => '521111', 'description' => 'Langganan Jasa Internet & Komunikasi Divhumas Polri (Bagrenmin)',
                'initial_ceiling' => 1476000000, 'final_ceiling' => 1476000000,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'key' => 'mulmed',
                'fiscal_year_id' => $fyId, 'program_id' => $progWaId, 'activity_id' => $actMap['3100'],
                'budget_type_id' => 2, 'kro_code' => '3100.QIA', 'kro_name' => 'Layanan Informasi Digital & Multimedia',
                'ro_code' => '3100.QIA.001', 'ro_name' => 'Produksi Konten & Siber Analisis',
                'component_code' => '001', 'component_name' => 'Produksi Kreatif, Pemanalis & Diseminasi',
                'subcomponent_code' => 'A', 'subcomponent_name' => 'Biro Mulmed',
                'account_code' => '522131', 'description' => 'Produksi Konten Kreatif, Pemantauan Siber & Diseminasi Digital (Biro Mulmed)',
                'initial_ceiling' => 50392662000, 'final_ceiling' => 50392662000,
                'created_at' => date('Y-m-d H:i:s'),
            ],

            // Belanja Modal (53) — Total Rp 115.000.000.000 (Section 5 & 10 LRA_SAMPLE_DATA.md)
            [
                'key' => 'spit',
                'fiscal_year_id' => $fyId, 'program_id' => $progBpId, 'activity_id' => $actMap['3084'],
                'budget_type_id' => 3, 'kro_code' => '3084.QMC', 'kro_name' => 'SISTEM APLIKASI',
                'ro_code' => '3084.QMC.001', 'ro_name' => 'Pengembangan Sistem SPIT',
                'component_code' => '001', 'component_name' => 'Infrastruktur SPIT',
                'subcomponent_code' => 'A', 'subcomponent_name' => 'Pengembangan SPIT Tahap III',
                'account_code' => '532111', 'description' => 'Pengembangan SPIT Tahap III',
                'initial_ceiling' => 50000000000, 'final_ceiling' => 50000000000,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'key' => 'led_outdoor',
                'fiscal_year_id' => $fyId, 'program_id' => $progBpId, 'activity_id' => $actMap['3084'],
                'budget_type_id' => 3, 'kro_code' => '3084.QMC', 'kro_name' => 'SISTEM APLIKASI',
                'ro_code' => '3084.QMC.002', 'ro_name' => 'Pengadaan LED Outdoor',
                'component_code' => '001', 'component_name' => 'Perangkat Visual Outdoor',
                'subcomponent_code' => 'B', 'subcomponent_name' => 'LED Outdoor Divhumas',
                'account_code' => '532111', 'description' => 'Pengadaan LED Outdoor',
                'initial_ceiling' => 35000000000, 'final_ceiling' => 35000000000,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'key' => 'ai_portal',
                'fiscal_year_id' => $fyId, 'program_id' => $progBpId, 'activity_id' => $actMap['3084'],
                'budget_type_id' => 3, 'kro_code' => '3084.QMC', 'kro_name' => 'SISTEM APLIKASI',
                'ro_code' => '3084.QMC.003', 'ro_name' => 'Kecerdasan Buatan & Data Digital',
                'component_code' => '001', 'component_name' => 'AI Portal Humas',
                'subcomponent_code' => 'C', 'subcomponent_name' => 'Portal Humas AI',
                'account_code' => '532111', 'description' => 'Pengembangan Kecerdasan Buatan dan Data Digital Portal Humas',
                'initial_ceiling' => 20000000000, 'final_ceiling' => 20000000000,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'key' => 'erp',
                'fiscal_year_id' => $fyId, 'program_id' => $progBpId, 'activity_id' => $actMap['3084'],
                'budget_type_id' => 3, 'kro_code' => '3084.QMC', 'kro_name' => 'SISTEM APLIKASI',
                'ro_code' => '3084.QMC.004', 'ro_name' => 'Pengembangan Aplikasi ERP',
                'component_code' => '001', 'component_name' => 'Enterprise Resource Planning',
                'subcomponent_code' => 'D', 'subcomponent_name' => 'ERP Humas',
                'account_code' => '532111', 'description' => 'Pengembangan & Implementasi Aplikasi ERP',
                'initial_ceiling' => 10000000000, 'final_ceiling' => 10000000000,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $insertedDetailIds = [];
        foreach ($bdItems as $bdi) {
            $key = $bdi['key'];
            unset($bdi['key']);
            $bdModel->insert($bdi);
            $insertedDetailIds[$key] = $db->insertID();
        }

        // 8. Realizations for 2026 (Jan–Agt)
        $realModel = $db->table('realizations');
        $realModel->where('fiscal_year_id', $fyId)->delete();

        $pegawaiReal = [1430191475, 1574973083, 2706866025, 1556938881, 1536441101, 2705570034, 1545260351, 1544721836, 0, 0, 0, 0];
        $barangReal  = [592225150, 3155444600, 5681759834, 8743922980, 12153091834, 5688376180, 9715691000, 10801354855, 0, 0, 0, 0];

        $batchRows = [];

        // Belanja Pegawai (Ditautkan ke akun Gaji dan Tunjangan)
        $gajiId = $insertedDetailIds['pegawai_gaji'];
        for ($m = 1; $m <= 12; $m++) {
            $batchRows[] = [
                'fiscal_year_id'   => $fyId,
                'budget_detail_id' => $gajiId,
                'budget_type_id'   => 1,
                'year'             => 2026,
                'month'            => $m,
                'amount'           => $pegawaiReal[$m - 1],
                'source_type'      => 'migration',
                'notes'            => 'Gaji & Tunjangan Pegawai T.A. 2026',
                'created_at'       => date('Y-m-d H:i:s'),
            ];
        }

        // Belanja Modal (Ditautkan ke SPIT Tahap III sesuai Section 6 & 10 LRA_SAMPLE_DATA.md)
        // Juli: 9.984.042.600, Agustus: 14.976.063.900 (Total: 24.960.106.500)
        $spitId = $insertedDetailIds['spit'];
        for ($m = 1; $m <= 12; $m++) {
            $amt = 0;
            if ($m === 7) $amt = 9984042600;
            if ($m === 8) $amt = 14976063900;
            $batchRows[] = [
                'fiscal_year_id'   => $fyId,
                'budget_detail_id' => $spitId,
                'budget_type_id'   => 3,
                'year'             => 2026,
                'month'            => $m,
                'amount'           => $amt,
                'source_type'      => 'migration',
                'notes'            => 'Pengembangan SPIT Tahap III',
                'created_at'       => date('Y-m-d H:i:s'),
            ];
        }

        // Belanja Barang (Ditautkan secara proporsional ke rincian Penum & Mulmed)
        $penumId = $insertedDetailIds['penum'];
        $mulmedId = $insertedDetailIds['mulmed'];
        for ($m = 1; $m <= 12; $m++) {
            $totB = $barangReal[$m - 1];
            // 40% Penum, 60% Mulmed
            $penumAmt = round($totB * 0.4);
            $mulmedAmt = $totB - $penumAmt;

            $batchRows[] = [
                'fiscal_year_id'   => $fyId,
                'budget_detail_id' => $penumId,
                'budget_type_id'   => 2,
                'year'             => 2026,
                'month'            => $m,
                'amount'           => $penumAmt,
                'source_type'      => 'migration',
                'notes'            => 'Realisasi Bag Penum',
                'created_at'       => date('Y-m-d H:i:s'),
            ];
            $batchRows[] = [
                'fiscal_year_id'   => $fyId,
                'budget_detail_id' => $mulmedId,
                'budget_type_id'   => 2,
                'year'             => 2026,
                'month'            => $m,
                'amount'           => $mulmedAmt,
                'source_type'      => 'migration',
                'notes'            => 'Realisasi Biro Multimedia',
                'created_at'       => date('Y-m-d H:i:s'),
            ];
        }

        $realModel->insertBatch($batchRows);

        // 9. Settings
        $settingModel = $db->table('settings');
        $defaultSettings = [
            ['key' => 'app_name', 'value' => 'SIMRAN', 'description' => 'Nama Aplikasi'],
            ['key' => 'institution', 'value' => 'Divisi Hubungan Masyarakat Polri', 'description' => 'Nama Satker/Instansi'],
            ['key' => 'active_year', 'value' => '2026', 'description' => 'Tahun Anggaran Aktif Default'],
        ];
        foreach ($defaultSettings as $st) {
            if ($settingModel->where('key', $st['key'])->countAllResults() === 0) {
                $settingModel->insert(array_merge($st, ['created_at' => date('Y-m-d H:i:s')]));
            }
        }
    }
}
