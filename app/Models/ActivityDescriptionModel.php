<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityDescriptionModel extends Model
{
    protected $table            = 'activity_descriptions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'fiscal_year_id',
        'item_no',
        'category_group',
        'activity_name',
        'biro',
        'division',
        'rkakl_activity',
        'ro_code',
        'ceiling',
        'description',
        'rkakl_source_page',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Ambil daftar kegiatan berdasarkan tahun anggaran dan filter biro/keyword
     */
    public function getFiltered(int $fiscalYearId, string $biro = '', string $keyword = ''): array
    {
        $builder = $this->where('fiscal_year_id', $fiscalYearId);

        if ($biro !== '' && $biro !== 'all') {
            if ($biro === 'penmas') {
                $builder->where('biro', 'Biro Penmas');
            } elseif ($biro === 'multimedia') {
                $builder->where('biro', 'Biro Multimedia');
            } elseif ($biro === 'pid') {
                $builder->where('biro', 'Biro PID');
            } elseif ($biro === 'dukyan') {
                $builder->groupStart()
                    ->where('biro', 'Bagrenmin')
                    ->orWhere('biro', 'Kadivhumas (Dukops)')
                    ->groupEnd();
            } elseif ($biro === 'pemeliharaan') {
                $builder->like('category_group', 'PEMELIHARAAN');
            } elseif ($biro === 'gaji') {
                $builder->like('category_group', 'GAJI DAN TUNJANGAN');
            } elseif ($biro === 'pelatihan') {
                $builder->like('category_group', 'PELATIHAN');
            }
        }

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('activity_name', $keyword)
                ->orLike('description', $keyword)
                ->orLike('biro', $keyword)
                ->orLike('division', $keyword)
                ->orLike('ro_code', $keyword)
                ->orLike('rkakl_activity', $keyword)
                ->groupEnd();
        }

        return $builder->orderBy('item_no', 'ASC')->findAll();
    }

    /**
     * Hitung total pagu dan jumlah kegiatan per kategori Biro
     */
    public function getBiroSummary(int $fiscalYearId): array
    {
        $rows = $this->where('fiscal_year_id', $fiscalYearId)->findAll();
        
        $summary = [
            'total_items' => count($rows),
            'total_ceiling' => 0.0,
            'groups' => [
                'penmas' => ['label' => 'Biro Penmas', 'count' => 0, 'ceiling' => 0.0, 'color' => 'primary'],
                'multimedia' => ['label' => 'Biro Multimedia', 'count' => 0, 'ceiling' => 0.0, 'color' => 'success'],
                'pid' => ['label' => 'Biro PID', 'count' => 0, 'ceiling' => 0.0, 'color' => 'info'],
                'dukyan' => ['label' => 'Dukyan / Bagrenmin', 'count' => 0, 'ceiling' => 0.0, 'color' => 'warning'],
                'pemeliharaan' => ['label' => 'Pemeliharaan', 'count' => 0, 'ceiling' => 0.0, 'color' => 'secondary'],
                'gaji' => ['label' => 'Gaji & Tunjangan', 'count' => 0, 'ceiling' => 0.0, 'color' => 'dark'],
                'pelatihan' => ['label' => 'Pelatihan SDM', 'count' => 0, 'ceiling' => 0.0, 'color' => 'danger'],
            ]
        ];

        foreach ($rows as $r) {
            $val = (float)$r['ceiling'];
            $summary['total_ceiling'] += $val;
            $cat = $r['category_group'];
            $biro = $r['biro'];

            if (str_contains($cat, 'BIRO PENMAS') && !str_contains($cat, 'DUKYAN')) {
                $summary['groups']['penmas']['count']++;
                $summary['groups']['penmas']['ceiling'] += $val;
            } elseif (str_contains($cat, 'BIRO MULTIMEDIA') && !str_contains($cat, 'DUKYAN')) {
                $summary['groups']['multimedia']['count']++;
                $summary['groups']['multimedia']['ceiling'] += $val;
            } elseif (str_contains($cat, 'BIRO PID') && !str_contains($cat, 'DUKYAN')) {
                $summary['groups']['pid']['count']++;
                $summary['groups']['pid']['ceiling'] += $val;
            } elseif (str_contains($cat, 'DUKYAN')) {
                $summary['groups']['dukyan']['count']++;
                $summary['groups']['dukyan']['ceiling'] += $val;
            } elseif (str_contains($cat, 'PEMELIHARAAN')) {
                $summary['groups']['pemeliharaan']['count']++;
                $summary['groups']['pemeliharaan']['ceiling'] += $val;
            } elseif (str_contains($cat, 'GAJI')) {
                $summary['groups']['gaji']['count']++;
                $summary['groups']['gaji']['ceiling'] += $val;
            } elseif (str_contains($cat, 'PELATIHAN')) {
                $summary['groups']['pelatihan']['count']++;
                $summary['groups']['pelatihan']['ceiling'] += $val;
            }
        }

        return $summary;
    }
}
