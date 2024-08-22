<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyTrxLeaveBalance extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $fields = [
            'md_employee_id'        => [
                'name'              => 'md_employee_id',
                'after'             => 'updated_by',
                'type'              => 'INT',
                'constraint'        => 11,
                'null'              => false,
            ],
            'record_id'             => [
                'name'              => 'year',
                'after'             => 'md_employee_id',
                'type'              => 'INT',
                'null'              => false,
            ],
            'amount'                => [
                'name'              => 'balance_amount',
                'after'             => 'year',
                'type'              => 'DECIMAL',
                'constraint'        => '10,2',
                'null'              => false,
            ],
            'table'   => [
                'name'              => 'carried_over_amount',
                'after'             => 'balance_amount',
                'type'              => 'DECIMAL',
                'constraint'        => '10,2',
                'default'           => 0.00
            ],
        ];
        $this->forge->modifyColumn('trx_leavebalance', $fields);

        $addFields = [
            'carry_over_expiry_date'    => [
                'after'                 => 'carried_over_amount',
                'type'                  => 'TIMESTAMP',
                'null'                  => true,
            ],
            'annual_allocation'         => [
                'after'                => 'submissiondate',
                'type'                  => 'DECIMAL',
                'constraint'            => '10,2',
                'null'                  => false,
            ],
            'startdate'                 => [
                'after'                 => 'submissiondate',
                'type'                  => 'TIMESTAMP',
                'null'                  => false,
            ],
        ];
        $this->forge->addColumn('trx_leavebalance', $addFields);
    }

    public function down() {}
}
