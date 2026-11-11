<?php

namespace App\Models;

use CodeIgniter\Model;

class FiscalYearModel extends Model
{
    protected $table            = 'fiscal_years';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['year', 'name', 'is_active'];
    protected $useTimestamps    = true;

    public function getActiveYear(): ?array
    {
        $active = $this->where('is_active', 1)->first();
        if (!$active) {
            $active = $this->orderBy('year', 'DESC')->first();
        }
        return $active;
    }

    /**
     * Tentukan tahun anggaran yang sedang dipilih pengguna.
     * Prioritas:
     * 1. $requestedYear eksplisit (dari query string ?year=...)
     * 2. Session 'selected_fiscal_year'
     * 3. Cookie 'simran_fiscal_year'
     * 4. Active default year dari database
     */
    public function determineSelectedYear(?int $requestedYear = null): array
    {
        // 1. Jika ada requested year eksplisit dari parameter URL
        if ($requestedYear !== null && $requestedYear > 0) {
            $found = $this->where('year', $requestedYear)->first();
            if ($found) {
                $this->saveToSessionAndCookie((int)$found['year']);
                return $found;
            }
        }

        // 2. Cek session
        $sessionYear = session()->get('selected_fiscal_year');
        if (!empty($sessionYear)) {
            $found = $this->where('year', (int)$sessionYear)->first();
            if ($found) {
                return $found;
            }
        }

        // 3. Cek cookie jika session belum ada
        $cookieYear = $_COOKIE['simran_fiscal_year'] ?? null;
        if (!empty($cookieYear) && is_numeric($cookieYear)) {
            $found = $this->where('year', (int)$cookieYear)->first();
            if ($found) {
                $this->saveToSessionAndCookie((int)$found['year']);
                return $found;
            }
        }

        // 4. Default ke active year dari DB
        $default = $this->getActiveYear();
        if ($default) {
            $this->saveToSessionAndCookie((int)$default['year']);
            return $default;
        }

        // Fallback darurat jika tabel kosong
        return [
            'id'        => 0,
            'year'      => (int)date('Y'),
            'name'      => 'T.A. ' . date('Y'),
            'is_active' => 1,
        ];
    }

    /**
     * Simpan tahun yang dipilih ke session dan cookie
     */
    public function setSelectedYear(int $year): ?array
    {
        $found = $this->where('year', $year)->first();
        if ($found) {
            $this->saveToSessionAndCookie((int)$found['year']);
            return $found;
        }
        return null;
    }

    private function saveToSessionAndCookie(int $year): void
    {
        try {
            session()->set('selected_fiscal_year', $year);
        } catch (\Throwable $e) {
            // Abaikan jika session belum dimulai
        }

        if (!headers_sent()) {
            setcookie('simran_fiscal_year', (string)$year, [
                'expires'  => time() + (86400 * 30), // 30 hari
                'path'     => '/',
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        }
    }
}

