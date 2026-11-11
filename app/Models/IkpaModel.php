<?php

namespace App\Models;

use CodeIgniter\Model;

class IkpaModel extends Model
{
    protected $table            = 'ikpa_periods';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'fiscal_year_id',
        'period_month',
        'revisi',
        'deviasi',
        'penyerapan',
        'kontraktual',
        'tagihan',
        'uptup',
        'output',
        'dispensasi',
        'notes',
        'source_filename',
        'source_hash',
        'official_score',
    ];
    protected $useTimestamps    = true;

    protected function initialize()
    {
        $this->ensureStorage();
    }

    /** Menyiapkan tabel periode otomatis agar patch cukup di-overwrite pada shared hosting. */
    private function ensureStorage(): void
    {
        if ($this->db->tableExists($this->table)) {
            return;
        }

        $forge = \Config\Database::forge();
        $forge->addField([
            'id'              => ['type' => 'INTEGER', 'auto_increment' => true],
            'fiscal_year_id'  => ['type' => 'INTEGER'],
            'period_month'    => ['type' => 'INTEGER'],
            'revisi'          => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'deviasi'         => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'penyerapan'      => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'kontraktual'     => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'tagihan'         => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'uptup'           => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'output'          => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'dispensasi'      => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'official_score'  => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'source_filename' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'source_hash'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'notes'           => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addPrimaryKey('id');
        $forge->addUniqueKey(['fiscal_year_id', 'period_month']);
        $forge->createTable($this->table, true);

        // Pertahankan nilai lama sebagai periode Desember sampai pengguna memilih periode yang tepat.
        if ($this->db->tableExists('ikpa_values')) {
            foreach ($this->db->table('ikpa_values')->get()->getResultArray() as $legacy) {
                unset($legacy['id']);
                $legacy['period_month'] = 12;
                $this->db->table($this->table)->ignore(true)->insert($legacy);
            }
        }
    }

    public static function monthName(int $month): string
    {
        return [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$month] ?? '-';
    }

    public static array $indicators = [
        'revisi'      => ['label' => 'Revisi DIPA', 'weight' => 10],
        'deviasi'     => ['label' => 'Deviasi Hal. III DIPA', 'weight' => 15],
        'penyerapan'  => ['label' => 'Penyerapan Anggaran', 'weight' => 20],
        'kontraktual' => ['label' => 'Belanja Kontraktual', 'weight' => 10],
        'tagihan'     => ['label' => 'Penyelesaian Tagihan', 'weight' => 10],
        'uptup'       => ['label' => 'Pengelolaan UP & TUP', 'weight' => 10],
        'output'      => ['label' => 'Capaian Output', 'weight' => 25],
    ];

    public static function calculateScore(?array $row): ?array
    {
        if (!$row) return null;

        $vsum = 0;
        $wsum = 0;
        $filledCount = 0;

        foreach (self::$indicators as $key => $ind) {
            $val = $row[$key] ?? null;
            if ($val !== null && $val !== '') {
                $v = (float)$val;
                $vsum += $v * ($ind['weight'] / 100);
                $wsum += $ind['weight'];
                $filledCount++;
            }
        }

        if ($wsum === 0) return null;

        // Normalisasi bila belum terisi semua
        $composite = ($vsum / ($wsum / 100));
        $dispensasi = isset($row['dispensasi']) ? (float)$row['dispensasi'] : 0;
        $finalScore = max(0, $composite - $dispensasi);

        $category = 'Kurang';
        $class = 'bad';
        if ($finalScore >= 95) {
            $category = 'Sangat Baik';
            $class = 'ok';
        } elseif ($finalScore >= 89) {
            $category = 'Baik';
            $class = 'warn';
        } elseif ($finalScore >= 70) {
            $category = 'Cukup';
            $class = 'warn';
        }

        return [
            'score'       => round($finalScore, 2),
            'category'    => $category,
            'class'       => $class,
            'filledCount' => $filledCount,
            'totalCount'  => 7,
            'dispensasi'  => $dispensasi,
        ];
    }
}
