<?php

namespace App\Models;

use CodeIgniter\Model;

class RealizationModel extends Model
{
    protected $table            = 'realizations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'fiscal_year_id',
        'budget_detail_id',
        'budget_type_id',
        'year',
        'month',
        'amount',
        'source_type',
        'source_reference',
        'import_batch_id',
        'notes',
    ];
    protected $useTimestamps    = true;
}
