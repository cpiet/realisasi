<?php

namespace App\Models;

use CodeIgniter\Model;

class RpaRevisionModel extends Model
{
    protected $table = 'rpa_revisions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['fiscal_year_id', 'revision_no', 'revision_name', 'revision_date', 'status', 'notes', 'created_by', 'source_total'];
    protected $useTimestamps = true;

    protected function initialize()
    {
        $this->ensureStorage();
    }

    /** Menyiapkan tabel rpa_revisions otomatis agar patch aman saat di-overwrite pada shared hosting. */
    private function ensureStorage(): void
    {
        if ($this->db->tableExists($this->table)) {
            return;
        }

        $forge = \Config\Database::forge();
        $forge->addField([
            'id'             => ['type' => 'INTEGER', 'auto_increment' => true],
            'fiscal_year_id' => ['type' => 'INTEGER', 'default' => 1],
            'revision_no'    => ['type' => 'INTEGER', 'default' => 1],
            'revision_name'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'revision_date'  => ['type' => 'DATE', 'null' => true],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'active'],
            'notes'          => ['type' => 'TEXT', 'null' => true],
            'created_by'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'source_total'   => ['type' => 'DECIMAL', 'constraint' => '18,2', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addPrimaryKey('id');
        $forge->addKey(['fiscal_year_id', 'status']);
        $forge->createTable($this->table, true);
    }
}
