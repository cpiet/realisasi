<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRpaTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'fiscal_year_id' => ['type' => 'INTEGER'],
            'revision_no' => ['type' => 'INTEGER'],
            'revision_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'revision_date' => ['type' => 'DATE'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['fiscal_year_id', 'status']);
        $this->forge->createTable('rpa_revisions', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'revision_id' => ['type' => 'INTEGER'],
            'activity_code' => ['type' => 'VARCHAR', 'constraint' => 80],
            'activity_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'pic_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'unit_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'budget_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'ceiling' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'planned_amount' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'realized_amount' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'planned_month' => ['type' => 'INTEGER'],
            'previous_month' => ['type' => 'INTEGER', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'not_started'],
            'readiness_note' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['revision_id', 'planned_month']);
        $this->forge->addKey(['pic_name', 'status']);
        $this->forge->createTable('rpa_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('rpa_items', true);
        $this->forge->dropTable('rpa_revisions', true);
    }
}
