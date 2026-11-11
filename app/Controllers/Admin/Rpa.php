<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FiscalYearModel;
use App\Models\RpaItemModel;
use App\Models\RpaRevisionModel;
use App\Models\RpaDetailModel;
use App\Services\RpaPdfParser;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Rpa extends BaseController
{
    public function index()
    {
        $fyModel = new FiscalYearModel();
        $allYears = $fyModel->orderBy('year', 'DESC')->findAll();
        $yearParam = $this->request->getGet('year');
        $fy = $fyModel->determineSelectedYear($yearParam ? (int)$yearParam : null);

        $revision = null;
        $items = [];
        $officialTotals = [
            'ceiling' => 0.0,
            'months' => array_fill(1, 12, 0.0),
            'period_total' => 0.0,
        ];

        $period = (string)($this->request->getGet('period') ?? 'end4');
        $keyword = trim((string)$this->request->getGet('q'));

        $allMonthNames = [
            1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
            5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
            9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'
        ];

        // Tampilan 4 bulan di akhir tahun (September s.d. Desember) secara default
        switch ($period) {
            case 'all':
                $selectedMonths = range(1, 12);
                $periodLabel = 'Semua Bulan (12 Bulan)';
                break;
            case 'q1':
                $selectedMonths = [1, 2, 3];
                $periodLabel = 'Triwulan I (Jan - Mar)';
                break;
            case 'q2':
                $selectedMonths = [4, 5, 6];
                $periodLabel = 'Triwulan II (Apr - Jun)';
                break;
            case 'q3':
                $selectedMonths = [7, 8, 9];
                $periodLabel = 'Triwulan III (Jul - Sep)';
                break;
            case 'q4':
                $selectedMonths = [10, 11, 12];
                $periodLabel = 'Triwulan IV (Okt - Des)';
                break;
            case 'end4':
            default:
                $period = 'end4';
                $selectedMonths = [9, 10, 11, 12];
                $periodLabel = '4 Bulan Terakhir (September - Desember)';
                break;
        }

        $shortMonthNames = [
            1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR',
            5 => 'MEI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGU',
            9 => 'SEP', 10 => 'OKT', 11 => 'NOV', 12 => 'DES'
        ];

        try {
            $revModel = new RpaRevisionModel();
            $itemModel = new RpaItemModel();

            $revision = $revModel
                ->where(['fiscal_year_id' => $fy['id'] ?? 0, 'status' => 'active'])
                ->orderBy('revision_no', 'DESC')
                ->first();

            if ($revision) {
                $itemQuery = $itemModel->where('revision_id', $revision['id']);
                if ($keyword !== '') {
                    $itemQuery->groupStart()
                        ->like('activity_code', $keyword)
                        ->orLike('activity_name', $keyword)
                        ->groupEnd();
                }
                // Ordered by id ASC to preserve document visual hierarchy
                $items = $itemQuery->orderBy('id', 'ASC')->findAll();

                $pdfTotalRow = null;
                $items = array_values(array_filter($items, static function ($it) use (&$pdfTotalRow) {
                    $code = trim((string)$it['activity_code']);
                    $name = trim((string)$it['activity_name']);
                    if ($code === '1' && $name === '2') {
                        return false;
                    }
                    // Baris grand total DIPA satker dari sumber import
                    if (($code === '1' || $code === '') && $name === '' && (float)$it['ceiling'] > 0) {
                        $pdfTotalRow = $it;
                        return false;
                    }
                    // Baris kosong sampah tanpa kode dan nama
                    if ($code === '' && ($name === '' || $name === '-') && (float)$it['ceiling'] <= 0) {
                        return false;
                    }
                    return true;
                }));

                $officialTotals['ceiling'] = (float)($revision['source_total'] ?? 0);
                if ($pdfTotalRow) {
                    $officialTotals['ceiling'] = (float)$pdfTotalRow['ceiling'];
                }

                $programItems = $itemModel
                    ->where('revision_id', $revision['id'])
                    ->like('activity_code', '060.01.', 'after')
                    ->findAll();

                if ($pdfTotalRow) {
                    for ($m = 1; $m <= 12; $m++) {
                        $officialTotals['months'][$m] = (float)$pdfTotalRow['month_' . $m];
                    }
                } elseif (!empty($programItems)) {
                    $calcCeiling = 0.0;
                    foreach ($programItems as $prog) {
                        $calcCeiling += (float)$prog['ceiling'];
                        for ($m = 1; $m <= 12; $m++) {
                            $officialTotals['months'][$m] += (float)$prog['month_' . $m];
                        }
                    }
                    if ($officialTotals['ceiling'] <= 0) {
                        $officialTotals['ceiling'] = $calcCeiling;
                    }
                } else {
                    for ($m = 1; $m <= 12; $m++) {
                        $officialTotals['months'][$m] = array_sum(array_column($items, 'month_' . $m));
                    }
                }

                foreach ($selectedMonths as $m) {
                    $officialTotals['period_total'] += $officialTotals['months'][$m];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Admin RPA load error: ' . $e->getMessage());
        }

        $currentMonth = (int)date('n');
        $isFluid = true;
        $selectedYear = $fy;

        return view('admin/rpa/index', compact(
            'isFluid',
            'fy',
            'allYears',
            'selectedYear',
            'revision',
            'items',
            'currentMonth',
            'period',
            'periodLabel',
            'selectedMonths',
            'allMonthNames',
            'shortMonthNames',
            'officialTotals',
            'keyword'
        ));
    }

    public function import()
    {
        $file = $this->request->getFile('rpa_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File RPA wajib diunggah.');
        }

        $fiscalYearId = (int)$this->request->getPost('fiscal_year_id');
        $fyModel = new FiscalYearModel();
        if ($fiscalYearId > 0) {
            $fy = $fyModel->find($fiscalYearId);
        } else {
            $fy = $fyModel->getActiveYear();
        }

        if (!$fy) {
            return redirect()->back()->with('error', 'Tahun anggaran tidak valid.');
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['pdf', 'xlsx', 'xls'], true)) {
            return redirect()->back()->with('error', 'Format file tidak didukung. Harap unggah file PDF (.pdf) atau Excel (.xlsx, .xls).');
        }

        if ($ext === 'pdf') {
            return $this->importPdf($file, $fy);
        }

        return $this->importExcel($file, $fy);
    }

    private function importPdf($file, array $fy)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        try {
            $parser = new RpaPdfParser();
            $result = $parser->parse($file->getTempName());

            $fyId = (int)$fy['id'];

            $db = db_connect();
            $db->transStart();

            // Arsipkan revisi lama
            (new RpaRevisionModel())
                ->where(['fiscal_year_id' => $fyId, 'status' => 'active'])
                ->set(['status' => 'archived'])
                ->update();

            $revModel = new RpaRevisionModel();
            $last = $revModel->where('fiscal_year_id', $fyId)->orderBy('revision_no', 'DESC')->first();
            $nextNo = ((int)($last['revision_no'] ?? 0)) + 1;

            $revId = $revModel->insert([
                'fiscal_year_id' => $fyId,
                'revision_no'    => $nextNo,
                'revision_name'  => 'Import RPA PDF ' . date('d-m-Y H:i'),
                'revision_date'  => date('Y-m-d'),
                'status'         => 'active',
                'created_by'     => session()->get('username') ?: 'admin',
                'source_total'   => $result['grand_total'],
            ]);

            $itemModel = new RpaItemModel();
            $currentCode = '';

            foreach ($result['rows'] as $row) {
                $code = trim((string)$row['code']);
                if ($code !== '') {
                    $currentCode = $code;
                }

                $name = trim((string)$row['name']);
                $ceiling = (float)$row['ceiling'];
                $total = (float)$row['total'];

                $plannedMonth = 1;
                for ($m = 1; $m <= 12; $m++) {
                    if ((float)$row['months'][$m] > 0) {
                        $plannedMonth = $m;
                        break;
                    }
                }

                $itemData = [
                    'revision_id'     => $revId,
                    'activity_code'   => $code,
                    'activity_name'   => $name,
                    'pic_name'        => 'Divhumas Polri',
                    'unit_name'       => null,
                    'budget_type'     => null,
                    'ceiling'         => $ceiling,
                    'planned_amount'  => $total,
                    'realized_amount' => 0,
                    'planned_month'   => $plannedMonth,
                    'status'          => 'not_started',
                ];

                for ($m = 1; $m <= 12; $m++) {
                    $itemData['month_' . $m] = (float)$row['months'][$m];
                }

                $itemModel->insert($itemData);
            }

            $count = count($result['rows']);
            $db->transComplete();

            $grandTotalFormatted = number_format($result['grand_total'], 0, ',', '.');
            return redirect()->to(base_url('admin/rpa?year=' . $fy['year']))->with(
                'success',
                "Dokumen PDF RPA T.A. {$fy['year']} berhasil diimpor: {$count} baris rincian (Revisi {$nextNo}). Total Pagu: Rp {$grandTotalFormatted}."
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal memproses file PDF RPA: ' . $e->getMessage());
        }
    }

    private function importExcel($file, array $fy)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        try {
            $parser = new \App\Services\RpaExcelParser();
            $result = $parser->parse($file->getTempName());

            $fyId = (int)$fy['id'];

            $db = db_connect();
            $db->transStart();

            // Arsipkan revisi lama
            (new RpaRevisionModel())
                ->where(['fiscal_year_id' => $fyId, 'status' => 'active'])
                ->set(['status' => 'archived'])
                ->update();

            $revModel = new RpaRevisionModel();
            $last = $revModel->where('fiscal_year_id', $fyId)->orderBy('revision_no', 'DESC')->first();
            $nextNo = ((int)($last['revision_no'] ?? 0)) + 1;

            $revId = $revModel->insert([
                'fiscal_year_id' => $fyId,
                'revision_no'    => $nextNo,
                'revision_name'  => 'Import RPA Excel ' . date('d-m-Y H:i'),
                'revision_date'  => date('Y-m-d'),
                'status'         => 'active',
                'created_by'     => session()->get('username') ?: 'admin',
                'source_total'   => $result['grand_total'],
            ]);

            $itemModel = new RpaItemModel();
            foreach ($result['rows'] as $row) {
                $plannedMonth = 1;
                for ($m = 1; $m <= 12; $m++) {
                    if ((float)$row['months'][$m] > 0) {
                        $plannedMonth = $m;
                        break;
                    }
                }

                $itemData = [
                    'revision_id'     => $revId,
                    'activity_code'   => $row['code'],
                    'activity_name'   => $row['name'],
                    'pic_name'        => 'Divhumas Polri',
                    'unit_name'       => null,
                    'budget_type'     => null,
                    'ceiling'         => (float)$row['ceiling'],
                    'planned_amount'  => (float)$row['total'],
                    'realized_amount' => 0,
                    'planned_month'   => $plannedMonth,
                    'status'          => 'not_started',
                ];

                for ($m = 1; $m <= 12; $m++) {
                    $itemData['month_' . $m] = (float)$row['months'][$m];
                }

                $itemModel->insert($itemData);
            }

            $count = count($result['rows']);
            $db->transComplete();

            $grandTotalFormatted = number_format($result['grand_total'], 0, ',', '.');
            return redirect()->to(base_url('admin/rpa?year=' . $fy['year']))->with(
                'success',
                "RPA T.A. {$fy['year']} berhasil diimpor dari Excel: {$count} baris kegiatan (Revisi {$nextNo}). Total Pagu: Rp {$grandTotalFormatted}."
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Import RPA Excel gagal: ' . $e->getMessage());
        }
    }

    private function number($value): float
    {
        if (is_int($value) || is_float($value)) return (float)$value;
        $value = trim((string)$value);
        if ($value === '' || str_starts_with($value, '=')) return 0.0;
        $value = preg_replace('/[^0-9,.-]/', '', $value);
        if ($value === '' || $value === '-') return 0.0;
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (substr_count($value, ',') > 0) {
            $value = str_replace(',', '.', $value);
        }
        return (float)$value;
    }

    public function detail(int $id)
    {
        $item = (new RpaItemModel())->find($id);
        if (!$item) return redirect()->to(base_url('admin/rpa'))->with('error', 'Kegiatan RPA tidak ditemukan.');
        $revision = (new RpaRevisionModel())->find($item['revision_id']);
        $details = (new RpaDetailModel())->where('rpa_item_id', $id)->orderBy('detail_name')->findAll();
        $month = (int)date('n');

        $fyModel = new FiscalYearModel();
        $allYears = $fyModel->orderBy('year', 'DESC')->findAll();
        $selectedYear = $revision ? $fyModel->find($revision['fiscal_year_id']) : $fyModel->getActiveYear();

        return view('admin/rpa/detail', compact('item', 'revision', 'details', 'month', 'selectedYear', 'allYears'));
    }
}
