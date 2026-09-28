<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class HelpSubMenu extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();

        $now = date('Y-m-d H:i:s');

        // 1. Fetch parent 'Master Data' menu
        $parentMenu = $this->db->table('sys_menu')
            ->where('name', 'Master Data')
            ->get()
            ->getRow();

        if ($parentMenu) {
            // 2. Check if category-article already exists
            $existingSubmenu = $this->db->table('sys_submenu')
                ->where('url', 'article')
                ->get()
                ->getRow();

            if ($existingSubmenu) {
                $submenuId = $existingSubmenu->sys_submenu_id;
            } else {
                // Insert Submenu if it doesn't exist
                $submenu = [
                    'sys_menu_id' => $parentMenu->sys_menu_id,
                    'name'        => 'Artikel Bantuan',
                    'url'         => 'help-article',
                    'sequence'    => 1,
                    'action'      => 'T',
                    'isactive'    => 'Y',
                    'created_by'  => 1,
                    'updated_by'  => 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];

                $this->db->table('sys_submenu')->insert($submenu);
                $submenuId = $this->db->insertID();
            }

            // 3. Ensure sys_access_menu record exists for Role 1
            $existingAccess = $this->db->table('sys_access_menu')
                ->where([
                    'sys_role_id'    => 1,
                    'sys_submenu_id' => $submenuId
                ])->get()->getRow();

            if (!$existingAccess) {
                $access = [
                    'sys_role_id'    => 1,
                    'sys_menu_id'    => $parentMenu->sys_menu_id,
                    'sys_submenu_id' => $submenuId,
                    'isview'         => 'Y',
                    'iscreate'       => 'Y',
                    'isupdate'       => 'Y',
                    'isdelete'       => 'Y',
                    'isactive'       => 'Y',
                    'created_by'     => 1,
                    'updated_by'     => 1,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];

                $this->db->table('sys_access_menu')->insert($access);
            }
        }

        $this->db->enableForeignKeyChecks();
    }
}