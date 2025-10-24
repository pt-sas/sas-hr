<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class NotificationTextApprovalBundling extends Seeder
{
    public function run()
    {
        $notifText = [
            [
                'created_by' => 100000,
                'updated_by' => 100000,
                'name'       => 'Email Approval Bundling',
                'subject'    => 'Approval Pengajuan',
                'text'       => "<p>Salam Bapak / Ibu,</p><p>Mohon approve document pengajuan (Var1),</p><p>Cabang (Var2) dan Divisi (Var3)</p><p>Terima Kasih.</p>",
                'notiftype'  => 'E'
            ],
        ];

        $this->db->table('sys_notiftext')->insertBatch($notifText);
    }
}
