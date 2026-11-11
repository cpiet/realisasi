<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityModel extends Model
{
    protected $table            = 'activities';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['fiscal_year_id', 'program_id', 'code', 'name', 'unit_name'];
    protected $useTimestamps    = true;
}
