<?php

namespace App\Models;

use CodeIgniter\Model;

class ImportBatchModel extends Model
{
    protected $table            = 'import_batches';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'fiscal_year_id',
        'filename',
        'file_hash',
        'position_date',
        'imported_by',
        'total_rows',
        'valid_rows',
        'error_rows',
        'status',
        'notes',
    ];
    protected $useTimestamps    = true;
}
