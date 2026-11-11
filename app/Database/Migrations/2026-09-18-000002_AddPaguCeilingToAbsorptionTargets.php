<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaguCeilingToAbsorptionTargets extends Migration
{
    public function up()
    {
        $fields = [
            'pagu_ceiling' => [
                'type'       => 'DECIMAL',
                'constraint' => '18,2',
                'default'    => 0,
                'after'      => 'budget_type_id',
            ],
        ];
        $this->forge->addColumn('absorption_targets', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('absorption_targets', 'pagu_ceiling');
    }
}
