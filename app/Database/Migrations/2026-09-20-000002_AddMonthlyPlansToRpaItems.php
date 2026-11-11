<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMonthlyPlansToRpaItems extends Migration
{
    public function up()
    {
        $fields = [];
        for ($m = 1; $m <= 12; $m++) $fields['month_' . $m] = ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0];
        $this->forge->addColumn('rpa_items', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('rpa_items', array_map(fn($m) => 'month_' . $m, range(1, 12)));
    }
}
