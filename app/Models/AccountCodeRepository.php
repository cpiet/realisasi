<?php

namespace App\Models;

/**
 * AccountCodeRepository
 * 
 * Mengelola data Modul Belajar Kode Akun Belanja RKAKL TA 2027
 * Divhumas Polri (Kode Satker 642321)
 */
class AccountCodeRepository
{
    protected ?array $data = null;
    protected string $filePath;

    public function __construct()
    {
        $this->filePath = APPPATH . 'Libraries/account_codes_data/modul_akun_2027.json';
        $this->loadData();
    }

    protected function loadData(): void
    {
        if (file_exists($this->filePath)) {
            $raw = file_get_contents($this->filePath);
            $this->data = json_decode($raw, true) ?: [];
        } else {
            $this->data = [
                'meta' => [],
                'groups' => [],
                'accounts' => [],
                'frequent_mistakes' => [],
                'divhumas_cases' => [],
                'quiz_questions' => []
            ];
        }
    }

    public function getMeta(): array
    {
        return $this->data['meta'] ?? [];
    }

    public function getGroups(): array
    {
        return $this->data['groups'] ?? [];
    }

    public function getAllAccounts(): array
    {
        return $this->data['accounts'] ?? [];
    }

    public function findByCode(string $code): ?array
    {
        $code = trim($code);
        $accounts = $this->getAllAccounts();

        foreach ($accounts as $item) {
            if ($item['kode'] === $code) {
                return $item;
            }
        }

        foreach ($accounts as $item) {
            if (str_contains($item['kode'], $code)) {
                return $item;
            }
        }

        return null;
    }

    public function search(string $keyword = '', ?string $group = null): array
    {
        $accounts = $this->getAllAccounts();
        $keyword = trim(strtolower($keyword));

        $filtered = array_filter($accounts, function ($item) use ($keyword, $group) {
            // Filter group jika ditentukan
            if (!empty($group) && $group !== 'all') {
                if (!str_starts_with($item['kode'], $group) && !str_contains(strtolower($item['kelompok']), strtolower($group))) {
                    return false;
                }
            }

            if ($keyword === '') {
                return true;
            }

            $searchCorpus = strtolower(implode(' ', [
                $item['kode'],
                $item['uraian'],
                $item['kelompok'],
                $item['penjelasan'],
                $item['batasan'],
                $item['kesalahan_umum'],
                $item['contoh_divhumas'],
                $item['sumber']
            ]));

            return str_contains($searchCorpus, $keyword);
        });

        // Urutkan relevansi: exact match code paling atas
        if ($keyword !== '') {
            usort($filtered, function ($a, $b) use ($keyword) {
                $aCode = strtolower($a['kode']);
                $bCode = strtolower($b['kode']);
                $aTitle = strtolower($a['uraian']);
                $bTitle = strtolower($b['uraian']);

                $aScore = 10;
                $bScore = 10;

                if ($aCode === $keyword) $aScore = 1;
                elseif (str_starts_with($aCode, $keyword)) $aScore = 2;
                elseif (str_starts_with($aTitle, $keyword)) $aScore = 3;
                elseif (str_contains($aTitle, $keyword)) $aScore = 4;

                if ($bCode === $keyword) $bScore = 1;
                elseif (str_starts_with($bCode, $keyword)) $bScore = 2;
                elseif (str_starts_with($bTitle, $keyword)) $bScore = 3;
                elseif (str_contains($bTitle, $keyword)) $bScore = 4;

                if ($aScore !== $bScore) {
                    return $aScore <=> $bScore;
                }

                return $a['no'] <=> $b['no'];
            });
        }

        return array_values($filtered);
    }

    public function getFrequentMistakes(): array
    {
        return $this->data['frequent_mistakes'] ?? [];
    }

    public function getDivhumasCases(): array
    {
        return $this->data['divhumas_cases'] ?? [];
    }

    public function getQuizQuestions(): array
    {
        return $this->data['quiz_questions'] ?? [];
    }
}
