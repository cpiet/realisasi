<?php

namespace App\Models;

use CodeIgniter\Model;

class ProgramModel extends Model
{
    protected $table            = 'programs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['fiscal_year_id', 'code', 'name'];
    protected $useTimestamps    = true;
}
