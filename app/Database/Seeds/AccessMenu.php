<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AccessMenu extends Seeder
{
    public function run()
    {
        $data = [
            [
                'created_by'        => 1,
                'updated_by'        => 1,
                'sys_role_id'       => 1,
                'sys_menu_id'       => 1,
                'sys_submenu_id'    => 0,
                'isview'            => 'Y',
                'iscreate'          => 'Y',
                'isupdate'          => 'Y',
                'isdelete'          => 'Y'
            ],
            [
                'created_by'        => 1,
                'updated_by'        => 1,
                'sys_role_id'       => 1,
                'sys_menu_id'       => 1,
                'sys_submenu_id'    => 1,
                'isview'            => 'Y',
                'iscreate'          => 'Y',
                'isupdate'          => 'Y',
                'isdelete'          => 'Y'
            ],
            [
                'created_by'        => 1,
                'updated_by'        => 1,
                'sys_role_id'       => 1,
                'sys_menu_id'       => 1,
                'sys_submenu_id'    => 2,
                'isview'            => 'Y',
                'iscreate'          => 'Y',
                'isupdate'          => 'Y',
                'isdelete'          => 'Y'
            ],
            [
                'created_by'        => 1,
                'updated_by'        => 1,
                'sys_role_id'       => 1,
                'sys_menu_id'       => 1,
                'sys_submenu_id'    => 3,
                'isview'            => 'Y',
                'iscreate'          => 'Y',
                'isupdate'          => 'Y',
                'isdelete'          => 'Y'
            ],
            [
                'created_by'        => 1,
                'updated_by'        => 1,
                'sys_role_id'       => 1,
                'sys_menu_id'       => 1,
                'sys_submenu_id'    => 4,
                'isview'            => 'Y',
                'iscreate'          => 'Y',
                'isupdate'          => 'Y',
                'isdelete'          => 'Y'
            ],
        ];

        $this->db->table('sys_access_menu')->insertBatch($data);
    }
}
