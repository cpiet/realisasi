<?php

namespace App\Models;

use CodeIgniter\Model;

class AbsorptionTargetModel extends Model
{
    protected $table            = 'absorption_targets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['fiscal_year_id', 'budget_type_id', 'pagu_ceiling', 'q1', 'q2', 'q3', 'q4'];
    protected $useTimestamps    = true;
}
