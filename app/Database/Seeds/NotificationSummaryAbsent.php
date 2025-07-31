<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class NotificationSummaryAbsent extends Seeder
{
    public function run()
    {
        $notifText = [
            [
                'created_by' => 100000,
                'updated_by' => 100000,
                'name'       => 'Summary Absent',
                'subject'    => 'Laporan Karyawan Tidak Absen Masuk',
                'text'       => `<p style="letter-spacing: 0.7px;"><span style="color: rgb(33, 37, 41); letter-spacing: 0.7px; white-space: pre; background-color: rgba(0, 0, 0, 0.075);">Salam Bapak/Ibu,&nbsp; </span></p><p style="letter-spacing: 0.7px;">Berikut dilampirkan Laporan Karyawan Tidak Absen Masuk pada tanggal :&nbsp;</p>`,
                'notiftype'  => 'E'
            ]
        ];

        $this->db->table('sys_notiftext')->insertBatch($notifText);
    }
}