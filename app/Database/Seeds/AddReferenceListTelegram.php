<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AddReferenceListTelegram extends Seeder
{
    public function run()
    {
        $query = $this->db->query("SELECT * FROM sys_reference WHERE name = 'SYS_NotificationType'");
        $row = $query->getRow();

        $notifLine = [
            'created_by' => 100000,
            'updated_by' => 100000,
            'value' => 'T',
            'name'  => 'Telegram',
            'sys_reference_id' => $row->sys_reference_id
        ];

        $this->db->table('sys_ref_detail')->insert($notifLine);
    }
}