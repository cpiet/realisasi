<?php

namespace App\Models;

use CodeIgniter\Model;

class SnapshotModel extends Model
{
    protected $table            = 'snapshots';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'fiscal_year_id',
        'name',
        'snapshot_date',
        'summary_data',
        'created_by',
    ];
    protected $useTimestamps    = false;
}
