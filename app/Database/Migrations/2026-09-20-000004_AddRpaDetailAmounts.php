<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRpaDetailAmounts extends Migration
{
    public function up()
    {
        $this->forge->addColumn('rpa_details', [
            'unit_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'unit_price' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'quantity' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'total_amount' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
        ]);
    }

    public function down() { $this->forge->dropColumn('rpa_details', ['unit_name', 'unit_price', 'quantity', 'total_amount']); }
}
