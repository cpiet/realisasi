<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FiscalYearModel;
use App\Models\IkpaModel;
use App\Services\IkpaPdfParser;
use RuntimeException;
use Throwable;

class Ikpa extends BaseController
{
    protected FiscalYearModel $fyModel;
    protected IkpaModel $ikpaModel;

    public function __construct()
    {
        $this->fyModel = new FiscalYearModel();
        $this->ikpaModel = new IkpaModel();
    }

    public function index()
    {
        $allYears = $this->fyModel->orderBy('year', 'DESC')->findAll();
        $yearParam = $this->request->getGet('year');
        $selectedYear = $this->fyModel->determineSelectedYear($yearParam ? (int)$yearParam : null);
        $fyId = $selectedYear ? $selectedYear['id'] : 0;
        $periodParam = (int)($this->request->getGet('period') ?: 0);
        $periods = $this->ikpaModel->where('fiscal_year_id', $fyId)->orderBy('period_month', 'DESC')->findAll();
        if ($periodParam >= 1 && $periodParam <= 12) {
            $ikpa = $this->ikpaModel->where('fiscal_year_id', $fyId)->where('period_month', $periodParam)->first();
        } else {
            $ikpa = $periods[0] ?? null;
        }
        $selectedPeriod = (int)($ikpa['period_month'] ?? ($periodParam ?: min(12, max(1, (int)date('n') - 1))));
        $calc = IkpaModel::calculateScore($ikpa);

        return view('admin/ikpa/index', [
            'all_years'     => $allYears,
            'selected_year' => $selectedYear,
            'ikpa'          => $ikpa,
            'calc'          => $calc,
            'indicators'    => IkpaModel::$indicators,
            'periods'       => $periods,
            'selected_period' => $selectedPeriod,
        ]);
    }

    public function save()
    {
        $fiscalYearId = (int)$this->request->getPost('fiscal_year_id');
        $fy = $this->fyModel->find($fiscalYearId);
        if (!$fy) {
            return redirect()->back()->with('error', 'Tahun anggaran tidak valid.');
        }

        $cleanVal = function ($field) {
            $v = $this->request->getPost($field);
            return ($v === '' || $v === null) ? null : max(0, min(120, (float)$v));
        };

        $indicatorValues = [];
        foreach (array_keys(IkpaModel::$indicators) as $indicatorKey) {
            $indicatorValues[$indicatorKey] = $cleanVal($indicatorKey);
        }
        if (count(array_filter($indicatorValues, static fn($value) => $value !== null)) === 0) {
            return redirect()->back()->withInput()->with('error', 'Nilai IKPA masih kosong. Isi indikator secara manual atau gunakan tombol Impor & Verifikasi PDF.');
        }

        $data = $indicatorValues + [
            'fiscal_year_id' => $fiscalYearId,
            'period_month'   => max(1, min(12, (int)$this->request->getPost('period_month'))),
            'dispensasi'     => max(0, (float)$this->request->getPost('dispensasi')),
            'notes'          => trim((string)$this->request->getPost('notes')),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        $existing = $this->ikpaModel->where('fiscal_year_id', $fiscalYearId)->where('period_month', $data['period_month'])->first();
        if ($existing) {
            $this->ikpaModel->update($existing['id'], $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->ikpaModel->insert($data);
        }

        return redirect()->to(base_url('admin/ikpa') . '?year=' . $fy['year'] . '&period=' . $data['period_month'])->with('success', 'Nilai IKPA periode ' . IkpaModel::monthName($data['period_month']) . ' berhasil diperbarui.');
    }

    public function importPdf()
    {
        $file = $this->request->getFile('ikpa_pdf');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Pilih berkas PDF IKPA yang valid.');
        }
        if ($file->getSize() > 10 * 1024 * 1024 || strtolower($file->getExtension()) !== 'pdf') {
            return redirect()->back()->with('error', 'Berkas harus PDF dengan ukuran maksimal 10 MB.');
        }
        if (file_get_contents($file->getTempName(), false, null, 0, 5) !== '%PDF-') {
            return redirect()->back()->with('error', 'Isi berkas tidak dikenali sebagai dokumen PDF.');
        }

        try {
            $parsed = (new IkpaPdfParser())->parse($file->getTempName());
        } catch (Throwable $e) {
            log_message('warning', 'Gagal impor PDF IKPA: {message}', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', $e instanceof RuntimeException ? $e->getMessage() : 'PDF tidak dapat dibaca.');
        }

        $fy = $this->fyModel->find((int)$this->request->getPost('fiscal_year_id'));
        if (!$fy) {
            return redirect()->back()->with('error', 'Tahun anggaran tujuan tidak valid.');
        }

        $data = $parsed['values'] + [
            'fiscal_year_id' => (int)$fy['id'],
            'period_month' => $parsed['period_month'],
            'official_score' => $parsed['official_score'],
            'source_filename' => basename($file->getClientName()),
            'source_hash' => hash_file('sha256', $file->getTempName()),
            'notes' => 'Diimpor dari laporan IKPA Satker Kemenkeu.',
        ];
        $existing = $this->ikpaModel->where('fiscal_year_id', $fy['id'])->where('period_month', $parsed['period_month'])->first();
        if ($existing) {
            $this->ikpaModel->update($existing['id'], $data);
        } else {
            $this->ikpaModel->insert($data);
        }

        $label = IkpaModel::monthName($parsed['period_month']) . ' ' . $fy['year'];
        return redirect()->to(base_url('admin/ikpa') . '?year=' . $fy['year'] . '&period=' . $parsed['period_month'])
            ->with('success', 'PDF IKPA periode ' . $label . ' berhasil diimpor. Nilai akhir terverifikasi: ' . number_format($parsed['official_score'], 2, ',', '.'));
    }
}
