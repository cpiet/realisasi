<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FiscalYearModel;
use App\Models\BudgetTypeModel;
use App\Models\BudgetDetailModel;
use App\Models\AbsorptionTargetModel;
use App\Models\SettingModel;
use App\Models\UserModel;
use App\Services\DashboardService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Settings extends BaseController
{
    protected FiscalYearModel $fyModel;
    protected BudgetTypeModel $btModel;
    protected BudgetDetailModel $bdModel;
    protected AbsorptionTargetModel $targetModel;

    public function __construct()
    {
        $this->fyModel = new FiscalYearModel();
        $this->btModel = new BudgetTypeModel();
        $this->bdModel = new BudgetDetailModel();
        $this->targetModel = new AbsorptionTargetModel();
    }

    public function index()
    {
        $allYears = $this->fyModel->orderBy('year', 'DESC')->findAll();
        $yearParam = $this->request->getGet('year');
        $activeYear = $this->fyModel->determineSelectedYear($yearParam ? (int)$yearParam : null);
        $budgetTypes = $this->btModel->findAll();

        $fyId = $activeYear ? $activeYear['id'] : 0;

        // Targets for active year
        $targets = [];
        foreach ($budgetTypes as $bt) {
            $t = $this->targetModel->where(['fiscal_year_id' => $fyId, 'budget_type_id' => $bt['id']])->first();
            $targets[$bt['id']] = $t ?: ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
        }

        // Pagu summary for active year (from absorption_targets or budget_details)
        $pagus = [];
        foreach ($budgetTypes as $bt) {
            $t = $this->targetModel->where(['fiscal_year_id' => $fyId, 'budget_type_id' => $bt['id']])->first();
            if ($t && (float)($t['pagu_ceiling'] ?? 0) > 0) {
                $pagus[$bt['id']] = (float)$t['pagu_ceiling'];
            } else {
                $sum = $this->bdModel
                    ->where('fiscal_year_id', $fyId)
                    ->where('budget_type_id', $bt['id'])
                    ->selectSum('final_ceiling')
                    ->first()['final_ceiling'] ?? 0;
                $pagus[$bt['id']] = (float)$sum;
            }
        }

        return view('admin/settings/index', [
            'all_years'    => $allYears,
            'active_year'  => $activeYear,
            'budget_types' => $budgetTypes,
            'targets'      => $targets,
            'pagus'        => $pagus,
            'app_name'     => SettingModel::getItem('app_name', 'SIMRAN'),
            'institution'  => SettingModel::getItem('institution', 'Divisi Hubungan Masyarakat Polri'),
        ]);
    }

    public function paguDipa()
    {
        $allYears = $this->fyModel->orderBy('year', 'DESC')->findAll();
        $yearParam = $this->request->getGet('year');
        $activeYear = $this->fyModel->determineSelectedYear($yearParam ? (int)$yearParam : null);
        $budgetTypes = $this->btModel->findAll();

        $fyId = $activeYear ? $activeYear['id'] : 0;

        // Targets for selected year
        $targets = [];
        foreach ($budgetTypes as $bt) {
            $t = $this->targetModel->where(['fiscal_year_id' => $fyId, 'budget_type_id' => $bt['id']])->first();
            $targets[$bt['id']] = $t ?: ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
        }

        // Pagu summary for selected year
        $pagus = [];
        foreach ($budgetTypes as $bt) {
            $t = $this->targetModel->where(['fiscal_year_id' => $fyId, 'budget_type_id' => $bt['id']])->first();
            if ($t && (float)($t['pagu_ceiling'] ?? 0) > 0) {
                $pagus[$bt['id']] = (float)$t['pagu_ceiling'];
            } else {
                $sum = $this->bdModel
                    ->where('fiscal_year_id', $fyId)
                    ->where('budget_type_id', $bt['id'])
                    ->selectSum('final_ceiling')
                    ->first()['final_ceiling'] ?? 0;
                $pagus[$bt['id']] = (float)$sum;
            }
        }

        return view('admin/settings/pagu', [
            'all_years'    => $allYears,
            'active_year'  => $activeYear,
            'budget_types' => $budgetTypes,
            'targets'      => $targets,
            'pagus'        => $pagus,
        ]);
    }

    public function addYear()
    {
        $year = (int)$this->request->getPost('year');
        $name = trim((string)$this->request->getPost('name')) ?: 'T.A. ' . $year;

        if ($year < 2020 || $year > 2050) {
            return redirect()->back()->with('error', 'Tahun anggaran tidak valid.');
        }

        $existing = $this->fyModel->where('year', $year)->first();
        if ($existing) {
            return redirect()->back()->with('error', 'Tahun anggaran ' . $year . ' sudah ada.');
        }

        $this->fyModel->insert([
            'year'       => $year,
            'name'       => $name,
            'is_active'  => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $newFyId = $this->fyModel->getInsertID();

        // Salin target default untuk jenis belanja
        $budgetTypes = $this->btModel->findAll();
        $defaultTargets = [
            '51' => [20, 50, 75, 95],
            '52' => [15, 40, 70, 90],
            '53' => [10, 40, 70, 90],
        ];
        foreach ($budgetTypes as $bt) {
            $dt = $defaultTargets[$bt['code']] ?? [10, 40, 70, 90];
            $this->targetModel->insert([
                'fiscal_year_id' => $newFyId,
                'budget_type_id' => $bt['id'],
                'q1'             => $dt[0],
                'q2'             => $dt[1],
                'q3'             => $dt[2],
                'q4'             => $dt[3],
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->to(base_url('admin/settings'))->with('success', 'Tahun Anggaran ' . $year . ' berhasil ditambahkan.');
    }

    public function setActiveYear()
    {
        $yearId = (int)$this->request->getPost('fiscal_year_id');
        $fy = $this->fyModel->find($yearId);
        if (!$fy) {
            return redirect()->back()->with('error', 'Tahun anggaran tidak ditemukan.');
        }

        $this->fyModel->where('id >', 0)->set(['is_active' => 0])->update();
        $this->fyModel->update($yearId, ['is_active' => 1]);

        SettingModel::setItem('active_year', (string)$fy['year'], 'Tahun Anggaran Aktif Default');

        return redirect()->to(base_url('admin/settings'))->with('success', 'Tahun Anggaran ' . $fy['year'] . ' kini aktif sebagai default.');
    }

    public function saveTargets()
    {
        $fiscalYearId = (int)$this->request->getPost('fiscal_year_id');
        $targets = $this->request->getPost('targets') ?? [];

        foreach ($targets as $btId => $t) {
            $existing = $this->targetModel->where(['fiscal_year_id' => $fiscalYearId, 'budget_type_id' => (int)$btId])->first();
            $data = [
                'fiscal_year_id' => $fiscalYearId,
                'budget_type_id' => (int)$btId,
                'q1'             => max(0, min(100, (float)($t['q1'] ?? 0))),
                'q2'             => max(0, min(100, (float)($t['q2'] ?? 0))),
                'q3'             => max(0, min(100, (float)($t['q3'] ?? 0))),
                'q4'             => max(0, min(100, (float)($t['q4'] ?? 0))),
                'updated_at'     => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $this->targetModel->update($existing['id'], $data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->targetModel->insert($data);
            }
        }

        return redirect()->back()->with('success', 'Target penyerapan triwulanan berhasil disimpan.');
    }

    public function savePaguDipa()
    {
        $fiscalYearId = (int)$this->request->getPost('fiscal_year_id');
        $paguList = $this->request->getPost('pagu') ?? [];

        foreach ($paguList as $btId => $rawAmount) {
            // Bersihkan format (jika diketik dengan titik ribuan atau tanda Rp)
            $amt = (float)preg_replace('/[^0-9\.]/', '', str_replace(',', '.', (string)$rawAmount));
            $existing = $this->targetModel->where(['fiscal_year_id' => $fiscalYearId, 'budget_type_id' => (int)$btId])->first();
            if ($existing) {
                $this->targetModel->update($existing['id'], [
                    'pagu_ceiling' => $amt,
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
            } else {
                $this->targetModel->insert([
                    'fiscal_year_id' => $fiscalYearId,
                    'budget_type_id' => (int)$btId,
                    'pagu_ceiling'   => $amt,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Pagu DIPA per jenis belanja berhasil diperbarui.');
    }

    public function saveGeneral()
    {
        $appName = trim((string)$this->request->getPost('app_name'));
        $institution = trim((string)$this->request->getPost('institution'));

        if ($appName !== '') SettingModel::setItem('app_name', $appName);
        if ($institution !== '') SettingModel::setItem('institution', $institution);

        return redirect()->to(base_url('admin/settings'))->with('success', 'Pengaturan umum berhasil disimpan.');
    }

    public function changePassword()
    {
        $currentPassword = (string)$this->request->getPost('current_password');
        $newPassword = (string)$this->request->getPost('new_password');
        $confirmation = (string)$this->request->getPost('new_password_confirmation');
        $userId = (int)session()->get('user_id');

        $userModel = new UserModel();
        $user = $userModel->find($userId);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Password saat ini tidak sesuai.');
        }
        if (strlen($newPassword) < 10) {
            return redirect()->back()->with('error', 'Password baru minimal 10 karakter.');
        }
        if ($newPassword !== $confirmation) {
            return redirect()->back()->with('error', 'Konfirmasi password baru tidak sama.');
        }

        $userModel->update($userId, ['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)]);
        return redirect()->to(base_url('admin/settings'))->with('success', 'Password administrator berhasil diperbarui.');
    }

    public function backupDatabase()
    {
        $dbPath = WRITEPATH . 'database' . DIRECTORY_SEPARATOR . 'simran.sqlite';
        if (!file_exists($dbPath)) {
            return redirect()->back()->with('error', 'File database SQLite tidak ditemukan.');
        }

        $backupFileName = 'simran-backup-' . date('Y-m-d-Hi') . '.sqlite';
        return $this->response->download($dbPath, null)->setFileName($backupFileName);
    }

    /** Unduh database Standar Biaya SBM (sbm.sqlite). */
    public function backupSbm()
    {
        \App\Libraries\SbmDb::connect();   // pastikan berkas sudah dibuat
        $path = \App\Libraries\SbmDb::path();
        if (!is_file($path)) {
            return redirect()->back()->with('error', 'File database SBM tidak ditemukan.');
        }
        return $this->response->download($path, null)->setFileName('sbm-backup-' . date('Y-m-d-Hi') . '.sqlite');
    }

    /** Unduh ZIP berisi simran.sqlite dan sbm.sqlite sekaligus. */
    public function backupAll()
    {
        if (!class_exists(\ZipArchive::class)) {
            return redirect()->back()->with('error', 'Ekstensi ZIP tidak tersedia di server; unduh kedua backup satu per satu.');
        }
        \App\Libraries\SbmDb::connect();
        $files = [
            'simran.sqlite' => WRITEPATH . 'database' . DIRECTORY_SEPARATOR . 'simran.sqlite',
            'sbm.sqlite'    => \App\Libraries\SbmDb::path(),
        ];
        $tmp = tempnam(sys_get_temp_dir(), 'simranbk');
        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Gagal membuat berkas ZIP.');
        }
        foreach ($files as $name => $path) {
            if (is_file($path)) {
                $zip->addFile($path, $name);
            }
        }
        $zip->close();
        $data = (string)file_get_contents($tmp);
        @unlink($tmp);
        return $this->response->download('simran-backup-' . date('Y-m-d-Hi') . '.zip', $data);
    }

    public function exportExcel()
    {
        $dashboardService = new DashboardService();
        $yearParam = $this->request->getGet('year');
        $data = $dashboardService->getDashboardData($yearParam ? (int)$yearParam : null);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan SIMRAN ' . $data['selected_year']['year']);

        // Judul
        $sheet->setCellValue('A1', 'SISTEM MONITORING REALISASI ANGGARAN & IKPA (SIMRAN)');
        $sheet->setCellValue('A2', 'DIVISI HUBUNGAN MASYARAKAT POLRI — TAHUN ANGGARAN ' . $data['selected_year']['year']);
        $sheet->setCellValue('A3', 'Posisi Data: ' . $data['posisi_text'] . ' | Tanggal Unduh: ' . date('d/m/Y H:i'));

        // KPI Ringkasan
        $sheet->setCellValue('A5', 'RINGKASAN EKSEKUTIF');
        $sheet->setCellValue('A6', 'Total Pagu');
        $sheet->setCellValue('B6', $data['kpi']['pagu']);
        $sheet->setCellValue('A7', 'Total Realisasi');
        $sheet->setCellValue('B7', $data['kpi']['realisasi']);
        $sheet->setCellValue('A8', 'Penyerapan (%)');
        $sheet->setCellValue('B8', $data['kpi']['serap_pct'] . '%');
        $sheet->setCellValue('A9', 'Sisa Anggaran');
        $sheet->setCellValue('B9', $data['kpi']['sisa']);
        $sheet->setCellValue('A10', 'Target s.d Posisi');
        $sheet->setCellValue('B10', $data['kpi']['target_pct'] . '%');
        $sheet->setCellValue('A11', 'Deviasi');
        $sheet->setCellValue('B11', $data['kpi']['deviasi']);

        // Rekap Jenis Belanja
        $sheet->setCellValue('A13', 'REALISASI PER JENIS BELANJA');
        $sheet->fromArray([['Jenis Belanja', 'Pagu (Rp)', 'Realisasi (Rp)', 'Sisa (Rp)', 'Serap (%)', 'Target (%)', 'Status']], null, 'A14');

        $r = 15;
        foreach ($data['belanja_summary'] as $bs) {
            $sheet->setCellValue('A' . $r, $bs['type']['name']);
            $sheet->setCellValue('B' . $r, $bs['pagu']);
            $sheet->setCellValue('C' . $r, $bs['realisasi']);
            $sheet->setCellValue('D' . $r, $bs['sisa']);
            $sheet->setCellValue('E' . $r, $bs['serap_pct'] . '%');
            $sheet->setCellValue('F' . $r, $bs['target_pct'] . '%');
            $sheet->setCellValue('G' . $r, $bs['status_label']);
            $r++;
        }

        // Auto width
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'SIMRAN_Realisasi_Divhumas_' . $data['selected_year']['year'] . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
