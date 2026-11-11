<?php

namespace App\Models;

use CodeIgniter\Model;

class RpaItemModel extends Model
{
    protected $table = 'rpa_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'revision_id', 'activity_code', 'activity_name', 'pic_name', 'unit_name', 
        'budget_type', 'ceiling', 'planned_amount', 'realized_amount', 'planned_month', 
        'previous_month', 'status', 'readiness_note', 
        'month_1', 'month_2', 'month_3', 'month_4', 'month_5', 'month_6', 
        'month_7', 'month_8', 'month_9', 'month_10', 'month_11', 'month_12'
    ];
    protected $useTimestamps = true;

    protected function initialize()
    {
        $this->ensureStorage();
    }

    /** Menyiapkan tabel rpa_items otomatis agar patch aman saat di-overwrite pada shared hosting. */
    private function ensureStorage(): void
    {
        if ($this->db->tableExists($this->table)) {
            return;
        }

        $forge = \Config\Database::forge();
        $forge->addField([
            'id'              => ['type' => 'INTEGER', 'auto_increment' => true],
            'revision_id'     => ['type' => 'INTEGER'],
            'activity_code'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'activity_name'   => ['type' => 'TEXT'],
            'pic_name'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'unit_name'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'budget_type'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'ceiling'         => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'planned_amount'  => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'realized_amount' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'planned_month'   => ['type' => 'INTEGER', 'default' => 1],
            'previous_month'  => ['type' => 'INTEGER', 'null' => true],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'not_started'],
            'readiness_note'  => ['type' => 'TEXT', 'null' => true],
            'month_1'         => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'month_2'         => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'month_3'         => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'month_4'         => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'month_5'         => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'month_6'         => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'month_7'         => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'month_8'         => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'month_9'         => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'month_10'        => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'month_11'        => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'month_12'        => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addPrimaryKey('id');
        $forge->addKey('revision_id');
        $forge->addKey('activity_code');
        $forge->createTable($this->table, true);
    }
}
