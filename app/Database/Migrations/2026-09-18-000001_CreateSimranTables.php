<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSimranTables extends Migration
{
    public function up()
    {
        // 1. users
        $this->forge->addField([
            'id'            => ['type' => 'INTEGER', 'auto_increment' => true],
            'username'      => ['type' => 'VARCHAR', 'constraint' => 100, 'unique' => true],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'role'          => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'admin'],
            'is_active'     => ['type' => 'TINYINT', 'default' => 1],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('users', true);

        // 2. fiscal_years
        $this->forge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'year'       => ['type' => 'INT', 'constraint' => 4, 'unique' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'is_active'  => ['type' => 'TINYINT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('fiscal_years', true);

        // 3. budget_types (51 Pegawai, 52 Barang, 53 Modal)
        $this->forge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 20, 'unique' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'color'      => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => '#3d6fd6'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('budget_types', true);

        // 4. programs
        $this->forge->addField([
            'id'             => ['type' => 'INTEGER', 'auto_increment' => true],
            'fiscal_year_id' => ['type' => 'INTEGER'],
            'code'           => ['type' => 'VARCHAR', 'constraint' => 50],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['fiscal_year_id', 'code']);
        $this->forge->createTable('programs', true);

        // 5. activities
        $this->forge->addField([
            'id'             => ['type' => 'INTEGER', 'auto_increment' => true],
            'fiscal_year_id' => ['type' => 'INTEGER'],
            'program_id'     => ['type' => 'INTEGER'],
            'code'           => ['type' => 'VARCHAR', 'constraint' => 50],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'unit_name'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['fiscal_year_id', 'code']);
        $this->forge->addKey('program_id');
        $this->forge->createTable('activities', true);

        // 6. budget_details
        $this->forge->addField([
            'id'                 => ['type' => 'INTEGER', 'auto_increment' => true],
            'fiscal_year_id'     => ['type' => 'INTEGER'],
            'program_id'         => ['type' => 'INTEGER', 'null' => true],
            'activity_id'        => ['type' => 'INTEGER', 'null' => true],
            'budget_type_id'     => ['type' => 'INTEGER', 'null' => true],
            'kro_code'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'kro_name'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ro_code'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'ro_name'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'component_code'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'component_name'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'subcomponent_code'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'subcomponent_name'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'account_code'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'description'        => ['type' => 'TEXT', 'null' => true],
            'initial_ceiling'    => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'final_ceiling'      => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('fiscal_year_id');
        $this->forge->addKey('activity_id');
        $this->forge->addKey('budget_type_id');
        $this->forge->addKey('account_code');
        $this->forge->createTable('budget_details', true);

        // 7. realizations
        $this->forge->addField([
            'id'               => ['type' => 'INTEGER', 'auto_increment' => true],
            'fiscal_year_id'   => ['type' => 'INTEGER'],
            'budget_detail_id' => ['type' => 'INTEGER', 'null' => true],
            'budget_type_id'   => ['type' => 'INTEGER', 'null' => true],
            'year'             => ['type' => 'INT', 'constraint' => 4],
            'month'            => ['type' => 'INT', 'constraint' => 2],
            'amount'           => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'source_type'      => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'manual'], // manual, excel, migration
            'source_reference' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'import_batch_id'  => ['type' => 'INTEGER', 'null' => true],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['fiscal_year_id', 'year', 'month']);
        $this->forge->addKey('budget_type_id');
        $this->forge->addKey('budget_detail_id');
        $this->forge->addKey('import_batch_id');
        $this->forge->createTable('realizations', true);

        // 8. absorption_targets
        $this->forge->addField([
            'id'             => ['type' => 'INTEGER', 'auto_increment' => true],
            'fiscal_year_id' => ['type' => 'INTEGER'],
            'budget_type_id' => ['type' => 'INTEGER'],
            'q1'             => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'q2'             => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'q3'             => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'q4'             => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['fiscal_year_id', 'budget_type_id']);
        $this->forge->createTable('absorption_targets', true);

        // 9. ikpa_values
        $this->forge->addField([
            'id'             => ['type' => 'INTEGER', 'auto_increment' => true],
            'fiscal_year_id' => ['type' => 'INTEGER', 'unique' => true],
            'revisi'         => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'deviasi'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'penyerapan'     => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'kontraktual'    => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'tagihan'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'uptup'          => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'output'         => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'dispensasi'     => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'notes'          => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('ikpa_values', true);

        // 10. import_batches
        $this->forge->addField([
            'id'             => ['type' => 'INTEGER', 'auto_increment' => true],
            'fiscal_year_id' => ['type' => 'INTEGER'],
            'filename'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'file_hash'      => ['type' => 'VARCHAR', 'constraint' => 64],
            'position_date'  => ['type' => 'DATE', 'null' => true],
            'imported_by'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'total_rows'     => ['type' => 'INT', 'default' => 0],
            'valid_rows'     => ['type' => 'INT', 'default' => 0],
            'error_rows'     => ['type' => 'INT', 'default' => 0],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'completed'],
            'notes'          => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('fiscal_year_id');
        $this->forge->createTable('import_batches', true);

        // 11. import_rows
        $this->forge->addField([
            'id'              => ['type' => 'INTEGER', 'auto_increment' => true],
            'import_batch_id' => ['type' => 'INTEGER'],
            'row_index'       => ['type' => 'INT'],
            'raw_data'        => ['type' => 'TEXT'],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'valid'], // valid, error, warning
            'error_message'   => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('import_batch_id');
        $this->forge->createTable('import_rows', true);

        // 12. snapshots
        $this->forge->addField([
            'id'             => ['type' => 'INTEGER', 'auto_increment' => true],
            'fiscal_year_id' => ['type' => 'INTEGER'],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'snapshot_date'  => ['type' => 'DATE'],
            'summary_data'   => ['type' => 'TEXT'],
            'created_by'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('fiscal_year_id');
        $this->forge->createTable('snapshots', true);

        // 13. settings
        $this->forge->addField([
            'id'          => ['type' => 'INTEGER', 'auto_increment' => true],
            'key'         => ['type' => 'VARCHAR', 'constraint' => 100, 'unique' => true],
            'value'       => ['type' => 'TEXT', 'null' => true],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('settings', true);
    }

    public function down()
    {
        $tables = [
            'settings',
            'snapshots',
            'import_rows',
            'import_batches',
            'ikpa_values',
            'absorption_targets',
            'realizations',
            'budget_details',
            'activities',
            'programs',
            'budget_types',
            'fiscal_years',
            'users',
        ];

        foreach ($tables as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
