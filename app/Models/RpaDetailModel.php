<?php

namespace App\Models;

use CodeIgniter\Model;

class RpaDetailModel extends Model
{
    protected $table = 'rpa_details';
    protected $returnType = 'array';
    protected $allowedFields = ['rpa_item_id', 'detail_name', 'unit_name', 'unit_price', 'quantity', 'total_amount', 'ceiling', 'month_1', 'month_2', 'month_3', 'month_4', 'month_5', 'month_6', 'month_7', 'month_8', 'month_9', 'month_10', 'month_11', 'month_12'];
    protected $useTimestamps = false;
}
