<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class NotificationDelegationAbsent extends Seeder
{
    public function run()
    {
        $notifText = [
            [
                'created_by' => 100000,
                'updated_by' => 100000,
                'name'       => 'Duta Tidak Hadir',
                'subject'    => 'Duta Tidak Hadir',
                'text'       => "<p>Salam Bapak/Ibu,</p><p>Menginformasikan bahwa duta (Var1) tidak ada kehadiran</p><p>Pada tanggal : (Var2)</p><p>Perlu adanya tindakan pengalihan duta</p>",
                'notiftype'  => 'E'
            ]
        ];

        $this->db->table('sys_notiftext')->insertBatch($notifText);
    }
}
