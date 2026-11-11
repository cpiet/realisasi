<?php

namespace App\Models;

use CodeIgniter\Model;

class BudgetTypeModel extends Model
{
    protected $table            = 'budget_types';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['code', 'name', 'color'];
    protected $useTimestamps    = true;
}
