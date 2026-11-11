<?php

namespace App\Models;

use CodeIgniter\Model;

class ImportRowModel extends Model
{
    protected $table            = 'import_rows';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'import_batch_id',
        'row_index',
        'raw_data',
        'status',
        'error_message',
    ];
    protected $useTimestamps    = false;
}
