<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class NotificationOffDutyAmbassador extends Seeder
{
    public function run()
    {
        $notifText = [
            [
                'created_by' => 100000,
                'updated_by' => 100000,
                'name'       => 'Duta Sedang Tidak Bertugas',
                'subject'    => 'Duta Sedang Tidak Bertugas',
                'text'       => "<p>Salam Bapak/Ibu,</p><p>Menginformasikan bahwa duta (Var1) tidak ada kehadiran</p><p>Membutuhkan tindakan pengalihan duta atas karyawan : (Var2)</p>",
                'notiftype'  => 'E'
            ]
        ];

        $this->db->table('sys_notiftext')->insertBatch($notifText);
    }
}
