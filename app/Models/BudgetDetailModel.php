<?php

namespace App\Models;

use CodeIgniter\Model;

class BudgetDetailModel extends Model
{
    protected $table            = 'budget_details';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'fiscal_year_id',
        'program_id',
        'activity_id',
        'budget_type_id',
        'kro_code',
        'kro_name',
        'ro_code',
        'ro_name',
        'component_code',
        'component_name',
        'subcomponent_code',
        'subcomponent_name',
        'account_code',
        'description',
        'initial_ceiling',
        'final_ceiling',
    ];
    protected $useTimestamps    = true;
}
