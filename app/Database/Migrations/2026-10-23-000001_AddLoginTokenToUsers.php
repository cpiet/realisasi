<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLoginTokenToUsers extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('login_token', 'users')) {
            $this->forge->addColumn('users', [
                'login_token' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                    'after'      => 'role',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('login_token', 'users')) {
            $this->forge->dropColumn('users', 'login_token');
        }
    }
}
