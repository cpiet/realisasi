<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\LraImportService;
use App\Models\FiscalYearModel;
use App\Models\ImportBatchModel;
use App\Models\ImportRowModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Imports extends BaseController
{
    protected LraImportService $importService;
    protected FiscalYearModel $fyModel;
    protected ImportBatchModel $batchModel;
    protected ImportRowModel $rowModel;

    public function __construct()
    {
        $this->importService = new LraImportService();
        $this->fyModel = new FiscalYearModel();
        $this->batchModel = new ImportBatchModel();
        $this->rowModel = new ImportRowModel();
    }

    public function index()
    {
        $yearParam = $this->request->getGet('year');
        $selectedYear = $this->fyModel->determineSelectedYear($yearParam ? (int)$yearParam : null);
        $allYears = $this->fyModel->orderBy('year', 'DESC')->findAll();

        return view('admin/imports/index', [
            'active_year'   => $selectedYear,
            'selected_year' => $selectedYear,
            'all_years'     => $allYears,
        ]);
    }

    public function preview()
    {
        $file = $this->request->getFile('excel_file');
        $fiscalYearId = (int)$this->request->getPost('fiscal_year_id');
        $positionDate = $this->request->getPost('position_date') ?: date('Y-m-d');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File Excel wajib diunggah.');
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xlsx', 'xls'])) {
            return redirect()->back()->with('error', 'Format file harus berupa Excel (.xlsx atau .xls).');
        }
        if ($file->getSize() > 20 * 1024 * 1024) {
            return redirect()->back()->with('error', 'Ukuran file Excel maksimal 20 MB.');
        }
        $allowedMimeTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/zip',
            'application/octet-stream',
        ];
        if (!in_array(strtolower((string)$file->getMimeType()), $allowedMimeTypes, true)) {
            return redirect()->back()->with('error', 'Isi file tidak dikenali sebagai dokumen Excel yang valid.');
        }

        $tempName = $file->getRandomName();
        $tempPath = WRITEPATH . 'uploads';
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0777, true);
        }
        $file->move($tempPath, $tempName);
        $fullPath = $tempPath . DIRECTORY_SEPARATOR . $tempName;

        try {
            $previewData = $this->importService->parseAndPreview($fullPath, $fiscalYearId);
            $fileHash = hash_file('sha256', $fullPath);
            $effectivePositionDate = $previewData['position_date'] ?: $positionDate;

            // Simpan ke session untuk tahap commit
            session()->set('pending_import', [
                'file_name'      => $file->getClientName(),
                'file_hash'      => $fileHash,
                'temp_path'      => $fullPath,
                'fiscal_year_id' => $fiscalYearId,
                'position_date'  => $effectivePositionDate,
                'preview_data'   => $previewData,
            ]);

            return view('admin/imports/preview', [
                'preview'       => $previewData,
                'file_name'     => $file->getClientName(),
                'position_date' => $effectivePositionDate,
                'fiscal_year_id'=> $fiscalYearId,
            ]);
        } catch (\Exception $e) {
            @unlink($fullPath);
            return redirect()->back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }
    }

    public function commit()
    {
        $pending = session()->get('pending_import');
        if (!$pending || empty($pending['preview_data']['all_rows'])) {
            return redirect()->to(base_url('admin/import'))->with('error', 'Tidak ada data import yang tertunda.');
        }

        try {
            $batchId = $this->importService->commitImport(
                (int)$pending['fiscal_year_id'],
                $pending['file_name'],
                $pending['file_hash'],
                $pending['preview_data']['all_rows'],
                session()->get('username') ?: 'admin',
                $pending['position_date']
            );

            // Hapus file temp & session
            if (file_exists($pending['temp_path'])) {
                @unlink($pending['temp_path']);
            }
            session()->remove('pending_import');

            return redirect()->to(base_url('admin/import/history'))->with('success', 'Import data LRA berhasil disimpan dengan ID Batch #' . $batchId);
        } catch (\Exception $e) {
            return redirect()->to(base_url('admin/import'))->with('error', 'Terjadi kesalahan saat menyimpan ke database: ' . $e->getMessage());
        }
    }

    public function history()
    {
        $batches = $this->batchModel
            ->select('import_batches.*, fiscal_years.year as fiscal_year')
            ->join('fiscal_years', 'fiscal_years.id = import_batches.fiscal_year_id', 'left')
            ->orderBy('import_batches.id', 'DESC')
            ->findAll();

        return view('admin/imports/history', [
            'batches' => $batches,
        ]);
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $lraSheet = $spreadsheet->getActiveSheet();
        $lraSheet->setTitle('LRA');

        $monthNames = [
            1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
            5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
            9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
        ];
        $today = getdate();
        $title = sprintf(
            'REALISASI ANGGARAN DIVHUMAS POLRI S.D. TANGGAL %d %s %d',
            $today['mday'],
            $monthNames[$today['mon']],
            $today['year']
        );
        $lraSheet->setCellValue('A1', $title);
        $lraSheet->mergeCells('A1:F1');
        $lraSheet->setCellValue('A3', 'PETUNJUK');
        $lraSheet->setCellValue('A4', 'Isi sheet Perbln. Jika sudah memiliki file LRA operasional dengan sheet LRA dan Perbln, unggah file tersebut langsung tanpa memakai template ini.');
        $lraSheet->mergeCells('A4:F5');
        $lraSheet->getColumnDimension('A')->setWidth(110);
        $lraSheet->getStyle('A1:A3')->getFont()->setBold(true);
        $lraSheet->getStyle('A4')->getAlignment()->setWrapText(true);

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Perbln');
        $sheet->setCellValue('A1', 'REKAP REALISASI PER BULAN');
        $sheet->mergeCells('A1:Q1');
        $sheet->fromArray(['NO', 'JENIS BELANJA', 'DIPA / PAGU'], null, 'A3');
        $sheet->setCellValue('D3', 'BULAN');
        $sheet->mergeCells('D3:O3');
        $sheet->setCellValue('P3', 'JUMLAH REALISASI');
        $sheet->setCellValue('Q3', 'PERSEN');
        $sheet->fromArray(array_values($monthNames), null, 'D4');
        $sheet->fromArray([
            [1, 'PEGAWAI', 0],
            [2, 'BARANG', 0],
            [3, 'MODAL', 0],
        ], null, 'A5');

        foreach (range(5, 7) as $row) {
            $sheet->setCellValue("P{$row}", "=SUM(D{$row}:O{$row})");
            $sheet->setCellValue("Q{$row}", "=IFERROR(P{$row}/C{$row},0)");
        }
        $sheet->fromArray(['JUMLAH', null], null, 'A9');
        $sheet->setCellValue('C9', '=SUM(C5:C7)');
        foreach (range('D', 'P') as $column) {
            $sheet->setCellValue("{$column}9", "=SUM({$column}5:{$column}7)");
        }
        $sheet->setCellValue('Q9', '=IFERROR(P9/C9,0)');

        $sheet->getStyle('A1:Q4')->getFont()->setBold(true);
        $sheet->getStyle('A3:Q4')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('C5:P9')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('Q5:Q9')->getNumberFormat()->setFormatCode('0.00%');
        $sheet->freezePane('D5');
        foreach (range('A', 'Q') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Template_Rekap_Bulanan_LRA_SIMRAN.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
