<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Models\FiscalYearModel;
use App\Models\BudgetTypeModel;
use App\Models\ProgramModel;
use App\Models\ActivityModel;
use App\Models\BudgetDetailModel;
use App\Models\RealizationModel;
use App\Models\ImportBatchModel;
use App\Models\ImportRowModel;
use App\Models\SnapshotModel;

class LraImportService
{
    protected FiscalYearModel $fyModel;
    protected BudgetTypeModel $btModel;
    protected ProgramModel $progModel;
    protected ActivityModel $actModel;
    protected BudgetDetailModel $bdModel;
    protected RealizationModel $realModel;
    protected ImportBatchModel $batchModel;
    protected ImportRowModel $rowModel;
    protected SnapshotModel $snapshotModel;

    public function __construct()
    {
        $this->fyModel = new FiscalYearModel();
        $this->btModel = new BudgetTypeModel();
        $this->progModel = new ProgramModel();
        $this->actModel = new ActivityModel();
        $this->bdModel = new BudgetDetailModel();
        $this->realModel = new RealizationModel();
        $this->batchModel = new ImportBatchModel();
        $this->rowModel = new ImportRowModel();
        $this->snapshotModel = new SnapshotModel();
    }

    /**
     * Parsing dan validasi file Excel LRA sesuai LRA_IMPORT_SPEC.md
     */
    public function parseAndPreview(string $filePath, int $fiscalYearId): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File Excel tidak ditemukan pada server.");
        }

        $fy = $this->fyModel->find($fiscalYearId);
        if (!$fy) {
            throw new \Exception("Tahun anggaran tidak valid.");
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        // 1. Identifikasi Worksheets yang tersedia (dengan trim nama sheet)
        $sheetNames = $spreadsheet->getSheetNames();
        $targetSheet = null;
        $sheetType = 'generic';

        foreach ($sheetNames as $sName) {
            $trimmed = trim($sName);
            if (strcasecmp($trimmed, 'LRA') === 0) {
                $targetSheet = $spreadsheet->getSheetByName($sName);
                $sheetType = 'LRA';
                break;
            } elseif (strcasecmp($trimmed, 'PERPROGRAM') === 0) {
                $targetSheet = $spreadsheet->getSheetByName($sName);
                $sheetType = 'PERPROGRAM';
            }
        }

        if (!$targetSheet) {
            $targetSheet = $spreadsheet->getActiveSheet();
        }

        $highestRow = $targetSheet->getHighestRow();
        $highestCol = $targetSheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        // 2. Deteksi Judul & Tanggal Posisi Data LRA dari baris atas
        $detectedPositionDate = null;
        $detectedYear = (int)$fy['year'];

        for ($r = 1; $r <= min(5, $highestRow); $r++) {
            for ($c = 1; $c <= min(5, $highestColIndex); $c++) {
                $cellVal = (string)$targetSheet->getCell([$c, $r])->getValue();
                if ($cellVal !== '') {
                    $monthMap = [
                        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
                        'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
                        'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12
                    ];
                    if (preg_match('/(\d{1,2})\s+(JANUARI|FEBRUARI|MARET|APRIL|MEI|JUNI|JULI|AGUSTUS|SEPTEMBER|OKTOBER|NOVEMBER|DESEMBER)\s+(\d{4})/i', $cellVal, $m)) {
                        $d = (int)$m[1];
                        $mo = $monthMap[strtolower($m[2])] ?? 1;
                        $y = (int)$m[3];
                        $detectedPositionDate = sprintf('%04d-%02d-%02d', $y, $mo, $d);
                        $detectedYear = $y;
                        break 2;
                    }
                }
            }
        }

        // Workbook LRA operasional Divhumas memiliki sheet kontrol "Perbln".
        // Sheet ini adalah sumber paling aman untuk import aplikasi karena berisi
        // pagu per jenis belanja dan realisasi bulanan tanpa baris subtotal hierarkis.
        $monthlyControlSheet = $this->findSheetByTrimmedName($spreadsheet, 'Perbln');
        $programSheet = $this->findSheetByTrimmedName($spreadsheet, 'PERPROGRAM');
        if ($sheetType === 'LRA' && $programSheet !== null && $monthlyControlSheet !== null) {
            return $this->parseProgramDetailSheet(
                $programSheet,
                $monthlyControlSheet,
                $fiscalYearId,
                $detectedYear,
                $detectedPositionDate,
                basename($filePath)
            );
        }

        // 3. Deteksi Header Baris & Kolom Mapping secara Dinamis
        $headerRow = 0;
        $colMap = [];
        $monthlyCols = []; // Untuk sheet PERPROGRAM

        for ($r = 1; $r <= min(20, $highestRow); $r++) {
            $rowValues = [];
            for ($c = 1; $c <= $highestColIndex; $c++) {
                $val = trim((string)$targetSheet->getCell([$c, $r])->getValue());
                $rowValues[$c] = strtolower($val);
            }

            foreach ($rowValues as $c => $val) {
                if (str_contains($val, 'kode') || str_contains($val, 'akun') || str_contains($val, 'rekening')) {
                    $colMap['code'] = $c;
                }
                if (str_contains($val, 'uraian') || str_contains($val, 'kegiatan') || str_contains($val, 'nama')) {
                    $colMap['desc'] = $c;
                }
                if (str_contains($val, 'pagu') || str_contains($val, 'anggaran')) {
                    $colMap['pagu'] = $c;
                }
                if (str_contains($val, 'realisasi') || str_contains($val, 'sp2d') || str_contains($val, 's.d.')) {
                    $colMap['realisasi'] = $c;
                }

                // Kolom bulanan (Januari..Desember)
                $monthNames = ['januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12];
                foreach ($monthNames as $mName => $mNum) {
                    if (str_starts_with($val, $mName)) {
                        $monthlyCols[$mNum] = $c;
                    }
                }
            }

            if (isset($colMap['code']) && (isset($colMap['desc']) || isset($colMap['pagu']))) {
                $headerRow = $r;
                break;
            }
        }

        if ($headerRow === 0) {
            $headerRow = 1;
            $colMap = ['code' => 1, 'desc' => 2, 'pagu' => 3, 'realisasi' => 4];
        }

        $parsedRows = [];
        $totalPagu = 0.0;
        $totalReal = 0.0;
        $validCount = 0;
        $warningCount = 0;
        $errorCount = 0;

        // Subtotal keywords to exclude from transactional double-counting
        $subtotalKeywords = ['divhumas polri', 'biro penmas', 'biro mulmed', 'bagrenmin', 'total', 'jumlah', 'belanja modal', 'belanja barang', 'belanja pegawai'];

        $currentProgram = null;
        $currentActivity = null;

        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            $rawCode = trim((string)$targetSheet->getCell([$colMap['code'] ?? 1, $r])->getValue());
            $rawDesc = trim((string)$targetSheet->getCell([$colMap['desc'] ?? 2, $r])->getValue());
            $rawPagu = $this->calculatedCellValue($targetSheet, $colMap['pagu'] ?? 3, $r);
            $rawReal = $this->calculatedCellValue($targetSheet, $colMap['realisasi'] ?? 4, $r);

            // Lewati baris kosong total
            if ($rawCode === '' && $rawDesc === '' && empty($rawPagu) && empty($rawReal)) {
                continue;
            }

            // Bersihkan format nilai
            $cleanPagu = $this->cleanNumber($rawPagu);
            $cleanReal = $this->cleanNumber($rawReal);

            $descLower = strtolower($rawDesc);
            $isSubtotal = false;
            foreach ($subtotalKeywords as $kw) {
                if ($descLower === $kw || str_starts_with($descLower, 'total ') || str_starts_with($descLower, 'jumlah ')) {
                    $isSubtotal = true;
                    break;
                }
            }

            // Klasifikasi hierarki kode anggaran
            $level = 'detail';
            $budgetTypeCode = null;

            if (preg_match('/^\d{3}\.\d{2}\.[A-Z]{2}/', $rawCode)) {
                // Program (contoh: 060.01.WA, 060.01.BP)
                $level = 'program';
                $currentProgram = ['code' => $rawCode, 'name' => $rawDesc];
            } elseif (preg_match('/^\d{4}$/', $rawCode)) {
                // Kegiatan 4 digit (contoh: 3070, 3073, 3084)
                $level = 'activity';
                $currentActivity = ['code' => $rawCode, 'name' => $rawDesc];
            } elseif (preg_match('/^5[1-3]\d{4}/', $rawCode)) {
                // Akun belanja 6 digit (contoh: 511111, 521211, 532111)
                $level = 'account';
                $budgetTypeCode = substr($rawCode, 0, 2);
            } else {
                // Detail subkegiatan atau kode bantu
                if ($cleanPagu > 0 && preg_match('/^5[1-3]/', $rawCode)) {
                    $budgetTypeCode = substr($rawCode, 0, 2);
                }
            }

            // Parsing realisasi bulanan jika ada di sheet PERPROGRAM
            $rowMonthly = [];
            foreach ($monthlyCols as $mNum => $mCol) {
                $mVal = $this->calculatedCellValue($targetSheet, $mCol, $r);
                $cleanMVal = $this->cleanNumber($mVal);
                if ($cleanMVal > 0) {
                    $rowMonthly[$mNum] = $cleanMVal;
                }
            }

            // Validasi & Penentuan Status Row (BARU / BERUBAH / TIDAK BERUBAH / ERROR)
            $status = 'valid';
            $previewStatus = 'NEW';
            $errorMsg = '';

            if ($isSubtotal) {
                $status = 'warning';
                $previewStatus = 'SUBTOTAL';
                $errorMsg = 'Baris subtotal/ringkasan — dilewati dari transaksi untuk mencegah double counting.';
                $warningCount++;
            } elseif ($cleanPagu < 0 || $cleanReal < 0) {
                $status = 'error';
                $previewStatus = 'ERROR';
                $errorMsg = 'Nilai pagu atau realisasi tidak boleh negatif.';
                $errorCount++;
            } elseif ($cleanReal > $cleanPagu && $cleanPagu > 0) {
                $status = 'warning';
                $previewStatus = 'WARNING';
                $errorMsg = 'Realisasi melebihi pagu anggaran.';
                $warningCount++;
            } else {
                $validCount++;
                // Cek status ke database apakah sudah ada
                if ($rawCode !== '') {
                    $existing = $this->bdModel->where(['fiscal_year_id' => $fiscalYearId, 'account_code' => $rawCode])->first();
                    if ($existing) {
                        $previewStatus = ((float)$existing['final_ceiling'] !== $cleanPagu) ? 'CHANGED' : 'UNCHANGED';
                    }
                }
            }

            if (!$isSubtotal) {
                $totalPagu += $cleanPagu;
                $totalReal += $cleanReal;
            }

            $parsedRows[] = [
                'row_index'        => $r,
                'code'             => $rawCode,
                'description'      => $rawDesc,
                'level'            => $level,
                'budget_type_code' => $budgetTypeCode,
                'pagu'             => $cleanPagu,
                'realisasi'        => $cleanReal,
                'is_subtotal'      => $isSubtotal,
                'status'           => $status,
                'preview_status'   => $previewStatus,
                'error_message'    => $errorMsg,
                'monthly_breakdown'=> $rowMonthly,
                'program'          => $currentProgram,
                'activity'         => $currentActivity,
            ];
        }

        return [
            'file_name'        => basename($filePath),
            'sheet_name'       => $targetSheet->getTitle(),
            'sheet_type'       => $sheetType,
            'fiscal_year'      => $detectedYear,
            'position_date'    => $detectedPositionDate,
            'total_rows'       => count($parsedRows),
            'valid_rows'       => $validCount,
            'warning_rows'     => $warningCount,
            'error_rows'       => $errorCount,
            'total_pagu'       => $totalPagu,
            'total_realisasi'  => $totalReal,
            'rows_sample'      => array_slice($parsedRows, 0, 50),
            'all_rows'         => $parsedRows,
        ];
    }

    /**
     * Eksekusi commit import ke database dengan Database Transaction
     */
    public function commitImport(int $fiscalYearId, string $filename, string $fileHash, array $rows, string $importedBy, ?string $positionDate = null): int
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $validCount = count(array_filter($rows, fn($r) => $r['status'] === 'valid'));
        $errorCount = count(array_filter($rows, fn($r) => $r['status'] === 'error'));

        // 1. Simpan Batch History
        $this->batchModel->insert([
            'fiscal_year_id' => $fiscalYearId,
            'filename'       => $filename,
            'file_hash'      => $fileHash,
            'position_date'  => $positionDate ?: date('Y-m-d'),
            'imported_by'    => $importedBy,
            'total_rows'     => count($rows),
            'valid_rows'     => $validCount,
            'error_rows'     => $errorCount,
            'status'         => 'completed',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $batchId = $db->insertID();

        // 2. Simpan Snapshot Kontrol
        $this->snapshotModel->insert([
            'fiscal_year_id' => $fiscalYearId,
            'name'           => 'Snapshot LRA Import: ' . $filename . ' (' . ($positionDate ?: date('Y-m-d')) . ')',
            'snapshot_date'  => $positionDate ?: date('Y-m-d'),
            'summary_data'   => json_encode([
                'batch_id'   => $batchId,
                'imported_at'=> date('Y-m-d H:i:s'),
                'rows_count' => count($rows),
            ]),
            'created_by'     => $importedBy,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        // 3. Mapping budget types
        $budgetTypes = $this->btModel->findAll();
        $btMap = [];
        foreach ($budgetTypes as $bt) {
            $btMap[$bt['code']] = $bt['id'];
        }

        $month = (int)date('n', strtotime($positionDate ?: date('Y-m-d')));
        $year = (int)date('Y', strtotime($positionDate ?: date('Y-m-d')));

        // Import workbook LRA operasional adalah snapshot penuh satu tahun anggaran.
        // Ganti data aktif lama di dalam transaksi agar data seed/import sebelumnya
        // tidak membuat pagu dan realisasi terhitung dua kali.
        $rowCodes = array_values(array_unique(array_column($rows, 'code')));
        sort($rowCodes);
        if ($rowCodes === ['51-LRA', '52-LRA', '53-LRA']) {
            $this->realModel->where('fiscal_year_id', $fiscalYearId)->delete();
            $this->bdModel->where('fiscal_year_id', $fiscalYearId)->delete();
        }
        $isDetailedLraSnapshot = ($rows[0]['source_profile'] ?? '') === 'LRA_DIVHUMAS_PROGRAM_DETAIL';
        if ($isDetailedLraSnapshot) {
            $this->realModel->where('fiscal_year_id', $fiscalYearId)->delete();
            $this->bdModel->where('fiscal_year_id', $fiscalYearId)->delete();
            $this->actModel->where('fiscal_year_id', $fiscalYearId)->delete();
            $this->progModel->where('fiscal_year_id', $fiscalYearId)->delete();
        }

        // 4. Proses baris & Upsert (mencegah subtotal dan duplikasi)
        foreach ($rows as $row) {
            // Catat log setiap baris
            $this->rowModel->insert([
                'import_batch_id' => $batchId,
                'row_index'       => $row['row_index'] ?? 0,
                'raw_data'        => json_encode($row),
                'status'          => $row['status'] ?? 'valid',
                'error_message'   => $row['error_message'] ?? null,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            // Lewati baris error atau subtotal
            if (($row['status'] ?? '') === 'error' || !empty($row['is_subtotal'])) {
                continue;
            }

            // Jika baris adalah Akun/Detail Anggaran
            if (!empty($row['code']) || !empty($row['description'])) {
                $code = $row['code'] ?: '—';
                $btCode = !empty($row['budget_type_code']) ? substr($row['budget_type_code'], 0, 2) : '52';
                $btId = $btMap[$btCode] ?? 2;
                $programId = null;
                $activityId = null;

                if (!empty($row['program']['code'])) {
                    $program = $this->progModel
                        ->where('fiscal_year_id', $fiscalYearId)
                        ->where('code', $row['program']['code'])
                        ->first();
                    if (!$program) {
                        $this->progModel->insert([
                            'fiscal_year_id' => $fiscalYearId,
                            'code' => $row['program']['code'],
                            'name' => $row['program']['name'] ?: $row['program']['code'],
                        ]);
                        $programId = $db->insertID();
                    } else {
                        $programId = (int)$program['id'];
                    }
                }

                if ($programId && !empty($row['activity']['code'])) {
                    $activity = $this->actModel
                        ->where('fiscal_year_id', $fiscalYearId)
                        ->where('program_id', $programId)
                        ->where('code', $row['activity']['code'])
                        ->first();
                    if (!$activity) {
                        $this->actModel->insert([
                            'fiscal_year_id' => $fiscalYearId,
                            'program_id' => $programId,
                            'code' => $row['activity']['code'],
                            'name' => $row['activity']['name'] ?: $row['activity']['code'],
                            'unit_name' => $row['activity']['unit_name'] ?? null,
                        ]);
                        $activityId = $db->insertID();
                    } else {
                        $activityId = (int)$activity['id'];
                    }
                }

                $existingBd = $this->bdModel
                    ->where('fiscal_year_id', $fiscalYearId)
                    ->where('account_code', $code)
                    ->first();

                if ($existingBd) {
                    $this->bdModel->update($existingBd['id'], [
                        'final_ceiling' => $row['pagu'] > 0 ? $row['pagu'] : $existingBd['final_ceiling'],
                        'description'   => $row['description'] ?: $existingBd['description'],
                        'program_id'    => $programId ?: $existingBd['program_id'],
                        'activity_id'   => $activityId ?: $existingBd['activity_id'],
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);
                    $bdId = $existingBd['id'];
                } else {
                    $this->bdModel->insert([
                        'fiscal_year_id'  => $fiscalYearId,
                        'program_id'      => $programId,
                        'activity_id'     => $activityId,
                        'budget_type_id'  => $btId,
                        'account_code'    => $code,
                        'description'     => $row['description'],
                        'initial_ceiling' => $row['pagu'],
                        'final_ceiling'   => $row['pagu'],
                        'created_at'      => date('Y-m-d H:i:s'),
                        'updated_at'      => date('Y-m-d H:i:s'),
                    ]);
                    $bdId = $db->insertID();
                }

                // Proses Realisasi (Bila ada distribusi bulanan dari sheet PERPROGRAM)
                if (!empty($row['monthly_breakdown']) && is_array($row['monthly_breakdown'])) {
                    foreach ($row['monthly_breakdown'] as $mNum => $amt) {
                        $this->upsertRealization($fiscalYearId, $bdId, $btId, $year, (int)$mNum, (float)$amt, $filename, $batchId);
                    }
                } elseif ($row['realisasi'] > 0) {
                    // Masukkan ke bulan posisi berjalan
                    $this->upsertRealization($fiscalYearId, $bdId, $btId, $year, $month, (float)$row['realisasi'], $filename, $batchId);
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \Exception("Gagal melakukan transaksi database saat commit import.");
        }

        return $batchId;
    }

    private function upsertRealization(int $fyId, int $bdId, int $btId, int $year, int $month, float $amount, string $filename, int $batchId): void
    {
        $existing = $this->realModel
            ->where('fiscal_year_id', $fyId)
            ->where('budget_detail_id', $bdId)
            ->where('month', $month)
            ->first();

        if ($existing) {
            $this->realModel->update($existing['id'], [
                'amount'           => $amount,
                'source_type'      => 'excel',
                'source_reference' => $filename,
                'import_batch_id'  => $batchId,
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);
        } else {
            $this->realModel->insert([
                'fiscal_year_id'   => $fyId,
                'budget_detail_id' => $bdId,
                'budget_type_id'   => $btId,
                'year'             => $year,
                'month'            => $month,
                'amount'           => $amount,
                'source_type'      => 'excel',
                'source_reference' => $filename,
                'import_batch_id'  => $batchId,
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function cleanNumber($val): float
    {
        if (is_numeric($val)) {
            return (float)$val;
        }
        if (empty($val)) {
            return 0.0;
        }
        $str = preg_replace('/[^0-9,\.-]/', '', (string)$val);
        if (str_contains($str, ',') && str_contains($str, '.')) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        } elseif (str_contains($str, ',')) {
            $str = str_replace(',', '.', $str);
        }
        return (float)$str;
    }

    private function findSheetByTrimmedName(Spreadsheet $spreadsheet, string $wanted): ?\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            if (strcasecmp(trim($worksheet->getTitle()), $wanted) === 0) {
                return $worksheet;
            }
        }

        return null;
    }

    /**
     * Parse detail unit/kegiatan dari PERPROGRAM. Baris agregat yang menjumlahkan
     * baris di bawahnya dilewati sehingga total tidak terhitung ganda.
     */
    private function parseProgramDetailSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $controlSheet,
        int $fiscalYearId,
        int $detectedYear,
        ?string $detectedPositionDate,
        string $fileName
    ): array {
        $monthNames = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
            'september' => 9, 'setember' => 9, 'oktober' => 10,
            'november' => 11, 'desember' => 12,
        ];
        $monthColumns = [];
        for ($r = 1; $r <= min(10, $sheet->getHighestRow()); $r++) {
            for ($c = 1; $c <= min(20, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn())); $c++) {
                $label = $this->normalizeLabel((string)$sheet->getCell([$c, $r])->getValue());
                if (isset($monthNames[$label])) {
                    $monthColumns[$monthNames[$label]] = $c;
                }
            }
        }
        if ($monthColumns === []) {
            throw new \Exception('Sheet PERPROGRAM ditemukan, tetapi kolom bulan tidak dikenali.');
        }
        ksort($monthColumns);
        $positionMonth = $detectedPositionDate
            ? (int)date('n', strtotime($detectedPositionDate))
            : max(array_keys($monthColumns));

        $currentProgram = null;
        $currentActivity = null;
        $activityCounters = [];
        $parsedRows = [];
        $totalPagu = 0.0;
        $totalReal = 0.0;

        for ($r = 5; $r <= $sheet->getHighestRow(); $r++) {
            $rawCode = trim((string)$sheet->getCell([1, $r])->getValue());
            $columnB = trim((string)$sheet->getCell([2, $r])->getValue());
            $columnC = trim((string)$sheet->getCell([3, $r])->getValue());

            if (preg_match('/^\d{3}\.\d{2}\.[A-Z]{2}$/', $rawCode)) {
                $currentProgram = ['code' => $rawCode, 'name' => $columnB ?: $rawCode];
                $currentActivity = null;
                continue;
            }
            if (preg_match('/^\d{4}$/', $rawCode)) {
                $currentActivity = ['code' => $rawCode, 'name' => $columnB ?: $rawCode];
                continue;
            }
            if (!$currentProgram || !$currentActivity) {
                continue;
            }

            $description = $columnC !== '' ? $columnC : $columnB;
            $descriptionLower = strtolower($description);
            if ($description === '' || str_contains($descriptionLower, 'jumlah') || str_starts_with($descriptionLower, 'total')) {
                continue;
            }

            $paguCell = $sheet->getCell([4, $r]);
            $pagu = $this->cleanNumber($this->calculatedCellValue($sheet, 4, $r));
            if ($pagu <= 0) {
                continue;
            }

            // Formula seperti =SUM(D24:D38) adalah baris induk. Formula yang
            // merujuk sheet LRA justru merupakan baris detail dan tetap diambil.
            $paguFormula = (string)$paguCell->getValue();
            if (str_starts_with($paguFormula, '=') && !str_contains($paguFormula, '!')) {
                preg_match_all('/\$?[A-Z]{1,3}\$?(\d+)/i', $paguFormula, $matches);
                $referencesChildRow = false;
                foreach ($matches[1] ?? [] as $referencedRow) {
                    if ((int)$referencedRow > $r) {
                        $referencesChildRow = true;
                        break;
                    }
                }
                if ($referencesChildRow) {
                    continue;
                }
            }

            $monthly = [];
            foreach ($monthColumns as $month => $column) {
                if ($month > $positionMonth) {
                    continue;
                }
                $monthly[$month] = $this->cleanNumber($this->calculatedCellValue($sheet, $column, $r));
            }
            $realization = array_sum($monthly);

            $baseActivityCode = $currentActivity['code'];
            $activityCounters[$baseActivityCode] = ($activityCounters[$baseActivityCode] ?? 0) + 1;
            $sequence = $activityCounters[$baseActivityCode];
            $detailCode = sprintf('%s.%02d', $baseActivityCode, $sequence);
            $activityCode = sprintf('%s-%02d', $baseActivityCode, $sequence);

            $searchText = strtolower($description . ' ' . $currentActivity['name']);
            if ($currentProgram['code'] === '060.01.BP' && $baseActivityCode === '3084') {
                $budgetTypeCode = '53';
            } elseif (preg_match('/gaji|tunjangan|pegawai/', $searchText)) {
                $budgetTypeCode = '51';
            } else {
                $budgetTypeCode = '52';
            }

            $existing = $this->bdModel
                ->where(['fiscal_year_id' => $fiscalYearId, 'account_code' => $detailCode])
                ->first();
            $previewStatus = !$existing
                ? 'NEW'
                : (((float)$existing['final_ceiling'] !== $pagu) ? 'CHANGED' : 'UNCHANGED');

            $parsedRows[] = [
                'row_index' => $r,
                'code' => $detailCode,
                'description' => $description,
                'level' => 'detail',
                'budget_type_code' => $budgetTypeCode,
                'pagu' => $pagu,
                'realisasi' => $realization,
                'is_subtotal' => false,
                'status' => 'valid',
                'preview_status' => $previewStatus,
                'error_message' => '',
                'monthly_breakdown' => $monthly,
                'program' => $currentProgram,
                'activity' => [
                    'code' => $activityCode,
                    'name' => $currentActivity['name'],
                    'unit_name' => $description,
                ],
                'source_profile' => 'LRA_DIVHUMAS_PROGRAM_DETAIL',
            ];
            $totalPagu += $pagu;
            $totalReal += $realization;
        }

        if ($parsedRows === []) {
            throw new \Exception('Tidak ada rincian kegiatan yang dapat dibaca dari sheet PERPROGRAM.');
        }

        $control = $this->parseMonthlyControlSheet(
            $controlSheet,
            $fiscalYearId,
            $detectedYear,
            $detectedPositionDate,
            $fileName
        );
        $notes = [];
        if (abs($control['total_pagu'] - $totalPagu) >= 1) {
            $notes[] = 'Total pagu detail PERPROGRAM berbeda dari kontrol Perbln sebesar Rp '
                . number_format(abs($control['total_pagu'] - $totalPagu), 0, ',', '.') . '.';
        }
        if (abs($control['total_realisasi'] - $totalReal) >= 1) {
            $notes[] = 'Total realisasi detail PERPROGRAM berbeda dari kontrol Perbln sebesar Rp '
                . number_format(abs($control['total_realisasi'] - $totalReal), 0, ',', '.')
                . '; nilai detail PERPROGRAM akan digunakan.';
        }

        return [
            'file_name' => $fileName,
            'sheet_name' => $sheet->getTitle(),
            'sheet_type' => 'PERPROGRAM',
            'source_profile' => 'LRA_DIVHUMAS_PROGRAM_DETAIL',
            'fiscal_year' => $detectedYear,
            'position_date' => $detectedPositionDate,
            'total_rows' => count($parsedRows),
            'valid_rows' => count($parsedRows),
            'warning_rows' => count($notes),
            'error_rows' => 0,
            'total_pagu' => $totalPagu,
            'total_realisasi' => $totalReal,
            'control_total_pagu' => $control['total_pagu'],
            'control_total_realisasi' => $control['total_realisasi'],
            'import_notes' => $notes,
            'rows_sample' => array_slice($parsedRows, 0, 50),
            'all_rows' => $parsedRows,
        ];
    }

    /**
     * Parse sheet Perbln pada workbook LRA operasional menjadi tiga baris agregat
     * (Belanja Pegawai, Barang, dan Modal) lengkap dengan realisasi per bulan.
     */
    private function parseMonthlyControlSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $fiscalYearId,
        int $detectedYear,
        ?string $detectedPositionDate,
        string $fileName
    ): array {
        $monthMap = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
            'september' => 9, 'setember' => 9, 'oktober' => 10,
            'november' => 11, 'desember' => 12,
        ];

        $headerRow = 0;
        $typeCol = 0;
        $paguCol = 0;
        $monthlyCols = [];
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($r = 1; $r <= min(15, $sheet->getHighestRow()); $r++) {
            for ($c = 1; $c <= $highestColIndex; $c++) {
                $label = $this->normalizeLabel((string)$sheet->getCell([$c, $r])->getValue());
                if (str_contains($label, 'jenis belanja')) {
                    $typeCol = $c;
                    $headerRow = max($headerRow, $r);
                } elseif ($label === 'dipa' || str_contains($label, 'pagu')) {
                    $paguCol = $c;
                    $headerRow = max($headerRow, $r);
                }

                foreach ($monthMap as $monthName => $monthNumber) {
                    if ($label === $monthName) {
                        $monthlyCols[$monthNumber] = $c;
                        $headerRow = max($headerRow, $r);
                        break;
                    }
                }
            }
        }

        if ($typeCol === 0 || $paguCol === 0 || $monthlyCols === []) {
            throw new \Exception('Sheet Perbln ditemukan, tetapi header jenis belanja, DIPA/pagu, atau bulan tidak dikenali.');
        }

        ksort($monthlyCols);
        $positionMonth = $detectedPositionDate
            ? (int)date('n', strtotime($detectedPositionDate))
            : max(array_keys($monthlyCols));
        $parsedRows = [];
        $totalPagu = 0.0;
        $totalReal = 0.0;

        $budgetTypes = [
            'pegawai' => ['code' => '51', 'description' => 'Belanja Pegawai'],
            'barang'  => ['code' => '52', 'description' => 'Belanja Barang'],
            'modal'   => ['code' => '53', 'description' => 'Belanja Modal'],
        ];

        for ($r = $headerRow + 1; $r <= $sheet->getHighestRow(); $r++) {
            $rawType = trim((string)$sheet->getCell([$typeCol, $r])->getValue());
            $typeKey = strtolower($rawType);
            if (!isset($budgetTypes[$typeKey])) {
                continue;
            }

            $definition = $budgetTypes[$typeKey];
            $pagu = $this->cleanNumber($this->calculatedCellValue($sheet, $paguCol, $r));
            $monthly = [];
            foreach ($monthlyCols as $month => $column) {
                if ($month > $positionMonth) {
                    continue;
                }
                $monthly[$month] = $this->cleanNumber($this->calculatedCellValue($sheet, $column, $r));
            }
            $realization = array_sum($monthly);

            $accountCode = $definition['code'] . '-LRA';
            $previewStatus = 'NEW';
            $existing = $this->bdModel
                ->where(['fiscal_year_id' => $fiscalYearId, 'account_code' => $accountCode])
                ->first();
            if ($existing) {
                $previewStatus = ((float)$existing['final_ceiling'] !== $pagu) ? 'CHANGED' : 'UNCHANGED';
            }

            $parsedRows[] = [
                'row_index'         => $r,
                'code'              => $accountCode,
                'description'       => $definition['description'],
                'level'             => 'account',
                'budget_type_code'  => $definition['code'],
                'pagu'              => $pagu,
                'realisasi'         => $realization,
                'is_subtotal'       => false,
                'status'            => 'valid',
                'preview_status'    => $previewStatus,
                'error_message'     => '',
                'monthly_breakdown' => $monthly,
                'program'           => null,
                'activity'          => null,
            ];

            $totalPagu += $pagu;
            $totalReal += $realization;
        }

        if (count($parsedRows) !== 3) {
            throw new \Exception('Sheet Perbln tidak memuat tiga jenis belanja (Pegawai, Barang, dan Modal) secara lengkap.');
        }

        return [
            'file_name'       => $fileName,
            'sheet_name'      => $sheet->getTitle(),
            'sheet_type'      => 'PERBLN',
            'source_profile'  => 'LRA_DIVHUMAS_MONTHLY_CONTROL',
            'fiscal_year'     => $detectedYear,
            'position_date'   => $detectedPositionDate,
            'total_rows'      => count($parsedRows),
            'valid_rows'      => count($parsedRows),
            'warning_rows'    => 0,
            'error_rows'      => 0,
            'total_pagu'      => $totalPagu,
            'total_realisasi' => $totalReal,
            'rows_sample'     => $parsedRows,
            'all_rows'        => $parsedRows,
        ];
    }

    private function calculatedCellValue(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $column, int $row): mixed
    {
        $cell = $sheet->getCell([$column, $row]);
        try {
            return $cell->getCalculatedValue();
        } catch (\Throwable) {
            return $cell->getOldCalculatedValue() ?? $cell->getValue();
        }
    }

    private function normalizeLabel(string $value): string
    {
        return strtolower(trim((string)preg_replace('/\s+/u', ' ', $value)));
    }
}
