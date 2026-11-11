<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRpaDetails extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'rpa_item_id' => ['type' => 'INTEGER'],
            'detail_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'ceiling' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
        ]);
        for ($m = 1; $m <= 12; $m++) $this->forge->addField(['month_' . $m => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0]]);
        $this->forge->addField(['created_at' => ['type' => 'DATETIME', 'null' => true]]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('rpa_item_id');
        $this->forge->createTable('rpa_details', true);
    }

    public function down() { $this->forge->dropTable('rpa_details', true); }
}
