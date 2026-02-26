<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EditTableEmpAlloc extends Migration
{
    public function up()
    {
     $fields = [
            'branch_to' => [
                'type' => 'INT',
                'null' => true,
            ],
            'division_to' => [
                'type' => 'INT',
                'null' => true,
            ],
            'levelling_to' => [
                'type' => 'INT',
                'null' => true,
            ],
            'position_to' => [
                'type' => 'INT',
                'null' => true,
            ],
        ];

        $this->forge->modifyColumn('trx_employee_allocation', $fields);
    }

    public function down()
    {
      $fields = [
            'branch_to' => [
                'type' => 'INT',
                'null' => false,
            ],
            'division_to' => [
                'type' => 'INT',
                'null' => false,
            ],
            'levelling_to' => [
                'type' => 'INT',
                'null' => false,
            ],
            'position_to' => [
                'type' => 'INT',
                'null' => false,
            ],
        ];

        $this->forge->modifyColumn('trx_employee_allocation', $fields);
    }
}
