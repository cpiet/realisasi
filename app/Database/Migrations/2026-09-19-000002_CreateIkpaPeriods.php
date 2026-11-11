<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIkpaPeriods extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('ikpa_periods')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'fiscal_year_id' => ['type' => 'INTEGER'],
            'period_month' => ['type' => 'INTEGER'],
            'revisi' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'deviasi' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'penyerapan' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'kontraktual' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'tagihan' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'uptup' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'output' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'dispensasi' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'official_score' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'source_filename' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'source_hash' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['fiscal_year_id', 'period_month']);
        $this->forge->createTable('ikpa_periods', true);
    }

    public function down()
    {
        $this->forge->dropTable('ikpa_periods', true);
    }
}
