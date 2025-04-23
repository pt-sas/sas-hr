<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class NotificationTextProxySwitch extends Seeder
{
    public function run()
    {
        $notifText = [
            [
                'created_by' => 100000,
                'updated_by' => 100000,
                'name'       => 'Pengalihan Approval',
                'subject'    => 'Pengalihan Approval',
                'text'       => "<p>Salam Bapak/Ibu,</p><p>Approval dialihkan kepada pengguna : (Var1)</p><p>Pada tanggal : (Var2)</p>",
                'notiftype'  => 'E'
            ],
            [
                'created_by' => 100000,
                'updated_by' => 100000,
                'name'       => 'Tindakan Pengalihan Approval',
                'subject'    => 'Perlu Tindakan Pengalihan Approval',
                'text'       => "<p>Dear Bapak/Ibu,</p><p>Dikarenakan tidak ada aktifitas absensi dan pengajuan atas karyawan : (Var1) pada tanggal : (Var2), perlu tindakan untuk mengalihkan approval atas karyawan ybs.</p>",
                'notiftype'  => 'E'
            ],
            [
                'created_by' => 100000,
                'updated_by' => 100000,
                'name'       => 'Pengembalian Approval',
                'subject'    => 'Approval Dikembalikan Ke User',
                'text'       => "<p>Salam Bapak/Ibu,</p><p>Approval sudah dikembalikan ke pengguna : (Var1)</p><p>Pada tanggal : (Var2)</p>",
                'notiftype'  => 'E'
            ]
        ];

        $this->db->table('sys_notiftext')->insertBatch($notifText);
    }
}