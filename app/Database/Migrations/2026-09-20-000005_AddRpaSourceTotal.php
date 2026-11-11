<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRpaSourceTotal extends Migration
{
    public function up() { $this->forge->addColumn('rpa_revisions', ['source_total' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'null' => true]]); }
    public function down() { $this->forge->dropColumn('rpa_revisions', 'source_total'); }
}
