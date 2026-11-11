<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class RpaExcelParser
{
    /**
     * Parse an RPA Excel file (.xlsx, .xls) and extract metadata and line items.
     *
     * @param string $filePath
     * @return array
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File Excel tidak ditemukan: {$filePath}");
        }

        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
        } catch (\Throwable $e) {
            throw new RuntimeException("Gagal membaca file Excel: " . $e->getMessage());
        }

        $highestRow = $sheet->getHighestRow();
        if ($highestRow < 5) {
            throw new RuntimeException("File Excel kosong atau tidak memiliki data RPA yang valid.");
        }

        // Cari baris header bulan (Januari..Desember)
        $monthCols = [
            1 => 'J', 2 => 'K', 3 => 'L', 4 => 'M', 5 => 'N', 6 => 'O',
            7 => 'P', 8 => 'Q', 9 => 'R', 10 => 'S', 11 => 'T', 12 => 'U'
        ];
        $ceilingCol = 'I';
        $totalCol = 'V';
        $startRow = 6;

        // Grand total dari baris 6 jika ada, atau dihitung dari akumulasi
        $grandTotal = $this->getCellValue($sheet->getCell($ceilingCol . '6'));
        if ($grandTotal <= 0) {
            $grandTotal = $this->getCellValue($sheet->getCell($totalCol . '6'));
        }

        $parsedRows = [];

        for ($r = $startRow; $r <= $highestRow; $r++) {
            $rawA = trim((string)$sheet->getCell('A' . $r)->getValue());
            $rawB = trim((string)$sheet->getCell('B' . $r)->getValue());
            $code = ($rawA !== '') ? $rawA : $rawB;

            $rawD = trim((string)$sheet->getCell('D' . $r)->getValue());
            $rawE = trim((string)$sheet->getCell('E' . $r)->getValue());
            $rawC = trim((string)$sheet->getCell('C' . $r)->getValue());

            // Tentukan nama rincian/kegiatan secara akurat
            $cleanD = trim($rawD);
            if (in_array($cleanD, ['-', '—', '>', '>>', '>>>', '*', ''], true)) {
                $prefix = $cleanD !== '' ? $cleanD . ' ' : '';
                $name = $prefix . ($rawE !== '' ? $rawE : $rawC);
            } else {
                $name = $rawD;
                if ($rawE !== '' && in_array(substr($cleanD, 0, 1), ['>', '-', '—'], true)) {
                    $name = $cleanD . ' ' . $rawE;
                }
            }
            $name = trim($name);

            // Lewati baris header penomoran (1, 2, 3...)
            if ($code === '1' && ($name === '2' || $name === '')) {
                // Jika baris grand total (pagu besar), simpan total resminya
                $cVal = $this->getCellValue($sheet->getCell($ceilingCol . $r));
                if ($cVal > 0 && $grandTotal <= 0) {
                    $grandTotal = $cVal;
                }
                if ($name === '2') {
                    continue;
                }
            }

            // Extract 12 bulan
            $monthly = array_fill(1, 12, 0.0);
            $hasMonthlyValue = false;
            foreach ($monthCols as $m => $col) {
                $mVal = $this->getCellValue($sheet->getCell($col . $r));
                $monthly[$m] = $mVal;
                if ($mVal > 0) {
                    $hasMonthlyValue = true;
                }
            }
            $totalMonthly = array_sum($monthly);

            // Extract ceiling / pagu
            $ceiling = $this->getCellValue($sheet->getCell($ceilingCol . $r));
            if ($ceiling <= 0 && $totalMonthly > 0) {
                $ceiling = $totalMonthly;
            }

            // Skip row if completely empty
            if ($code === '' && $name === '' && $ceiling <= 0 && !$hasMonthlyValue) {
                continue;
            }

            $totVal = $this->getCellValue($sheet->getCell($totalCol . $r));
            if ($totVal <= 0) {
                $totVal = $totalMonthly ?: $ceiling;
            }

            $parsedRows[] = [
                'code'    => $code,
                'name'    => $name,
                'ceiling' => $ceiling,
                'months'  => $monthly,
                'total'   => $totVal,
            ];
        }

        if (empty($parsedRows)) {
            throw new RuntimeException("Tidak ditemukan baris kegiatan yang valid di dalam file Excel.");
        }

        // Jika grand total belum didapat, cari dari program 060.01. atau baris pertama
        if ($grandTotal <= 0) {
            $progSum = 0.0;
            foreach ($parsedRows as $pr) {
                if (str_starts_with($pr['code'], '060.01.')) {
                    $progSum += $pr['ceiling'];
                }
            }
            $grandTotal = $progSum > 0 ? $progSum : ($parsedRows[0]['ceiling'] ?? 0);
        }

        return [
            'grand_total' => $grandTotal,
            'rows'        => $parsedRows,
        ];
    }

    /**
     * Ambil nilai numerik dari sel Excel (termasuk menangani formula & nilai kalkulasi).
     */
    private function getCellValue($cell): float
    {
        if ($cell === null) {
            return 0.0;
        }

        $raw = $cell->getValue();
        if ($raw === null || $raw === '' || $raw === '-') {
            return 0.0;
        }

        if (is_int($raw) || is_float($raw)) {
            return (float)$raw;
        }

        $str = trim((string)$raw);
        if (str_starts_with($str, '=')) {
            $old = $cell->getOldCalculatedValue();
            if ($old !== null && $old !== '' && is_numeric($old)) {
                return (float)$old;
            }
            try {
                $calc = $cell->getCalculatedValue();
                if ($calc !== null && is_numeric($calc)) {
                    return (float)$calc;
                }
            } catch (\Throwable $e) {
                // abaikan error kalkulasi rumus kompleks
            }
        }

        $clean = str_replace([' ', "\xc2\xa0"], '', $str);
        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (substr_count($clean, ',') > 0) {
            $clean = str_replace(',', '.', $clean);
        }
        $clean = preg_replace('/[^0-9.-]/', '', $clean);

        return is_numeric($clean) ? (float)$clean : 0.0;
    }
}
