<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PerformanceAppraisalMenu extends Seeder
{
    public function run()
    {
        // ------------------------------------------------------------------
        // 1. Fetch Existing Master User (ID 1) or First Active User
        // ------------------------------------------------------------------
        $sysUserId = 1;
        $user = $this->db->table('sys_user')->getWhere(['sys_user_id' => $sysUserId])->getRow();
        if ($user) {
            $sysUserId = (int) $user->sys_user_id;
        } else {
            $fallbackUser = $this->db->table('sys_user')->where('isactive', 'Y')->get()->getRow();
            if ($fallbackUser) {
                $sysUserId = (int) $fallbackUser->sys_user_id;
            }
        }

        // ------------------------------------------------------------------
        // 2. Insert or Reuse Parent Menu (sys_menu)
        // ------------------------------------------------------------------
        // Action: 'N' (None) - Folder/dropdown parent header with no direct view page
        $existingMenu = $this->db->table('sys_menu')
            ->where('url', 'performance-appraisal')
            ->get()
            ->getRow();

        if ($existingMenu) {
            $sysMenuId = (int) $existingMenu->sys_menu_id;
        } else {
            $menuData = [
                'name'        => 'Penilaian Kerja',
                'url'         => 'performance-appraisal',
                'sequence'    => 12,
                'icon'        => 'fas fa-clipboard-check',
                'action'      => 'N',
                'initialcode' => '',
                'status'      => 'Y',
                'isactive'    => 'Y',
                'created_by'  => $sysUserId,
                'updated_by'  => $sysUserId,
            ];

            $this->db->table('sys_menu')->insert($menuData);
            $sysMenuId = (int) $this->db->insertID();
        }

        // ------------------------------------------------------------------
        // 3. Insert Submenus (sys_submenu) Idempotently
        // ------------------------------------------------------------------
        $submenus = [
            [
                'sys_menu_id' => $sysMenuId,
                'name'        => 'Template Penilaian',
                'url'         => 'template-penilaian',
                'sequence'    => 1,
                'action'      => 'T', // Table Form: Master grid list with create/edit actions
                'status'      => 'Y',
                'isactive'    => 'Y',
                'created_by'  => $sysUserId,
                'updated_by'  => $sysUserId,
            ],
            [
                'sys_menu_id' => $sysMenuId,
                'name'        => 'Siklus Penilaian',
                'url'         => 'siklus-penilaian',
                'sequence'    => 2,
                'action'      => 'T', // Table Form: Cycle setup Datatable list
                'status'      => 'Y',
                'isactive'    => 'Y',
                'created_by'  => $sysUserId,
                'updated_by'  => $sysUserId,
            ],
            [
                'sys_menu_id' => $sysMenuId,
                'name'        => 'Penilaian Individu dan Tim',
                'url'         => 'penilaian',
                'sequence'    => 3,
                'action'      => 'T', // Table Form: List of evaluations to be filled out/reviewed
                'status'      => 'Y',
                'isactive'    => 'Y',
                'created_by'  => $sysUserId,
                'updated_by'  => $sysUserId,
            ],
            [
                'sys_menu_id' => $sysMenuId,
                'name'        => 'Monitoring Penilaian',
                'url'         => 'monitoring-penilaian',
                'sequence'    => 4,
                'action'      => 'N', // Dashboard/Analytics view (no generic New button)
                'status'      => 'Y',
                'isactive'    => 'Y',
                'created_by'  => $sysUserId,
                'updated_by'  => $sysUserId,
            ],
        ];

        foreach ($submenus as $submenu) {
            $existingSubmenu = $this->db->table('sys_submenu')
                ->where([
                    'sys_menu_id' => $sysMenuId,
                    'url'         => $submenu['url'],
                ])
                ->get()
                ->getRow();

            if (!$existingSubmenu) {
                $this->db->table('sys_submenu')->insert($submenu);
            }
        }
    }
}
