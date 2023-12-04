<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Menu extends Seeder
{
    public function run()
    {
        $menu = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'name'          => 'Configuration',
            'url'           => 'configuration',
            'sequence'      => '1',
            'icon'          => 'fas fa-cogs',
        ];

        $this->db->table('sys_menu')->insert($menu);

        $submenu = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'User',
                'url'           => 'user',
                'sequence'      => '1',
                // 'istable'       => 'Y',
                'sys_menu_id'   => 1
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Role',
                'url'           => 'role',
                'sequence'      => '2',
                // 'istable'       => 'Y',
                'sys_menu_id'   => 1
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Menu',
                'url'           => 'menu',
                'sequence'      => '3',
                // 'istable'       => 'Y',
                'sys_menu_id'   => 1
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'name'          => 'Submenu',
                'url'           => 'submenu',
                'sequence'      => '4',
                // 'istable'       => 'Y',
                'sys_menu_id'   => 1
            ]
        ];

        $this->db->table('sys_submenu')->insertBatch($submenu);
    }
}
