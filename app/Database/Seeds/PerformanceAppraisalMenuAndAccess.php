<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PerformanceAppraisalMenuAndAccess extends Seeder
{
    public function run()
    {
        // ------------------------------------------------------------------
        // 1. Fetch Existing Master User & Role (ID 1)
        // ------------------------------------------------------------------
        $sysRoleId = 1;
        $sysUserId = 1;

        // Ensure user 1 exists, fallback to first active user if 1 isn't found
        $user = $this->db->table('sys_user')->getWhere(['sys_user_id' => $sysUserId])->getRow();
        if ($user) {
            $sysUserId = $user->sys_user_id;
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
                'isactive'    => 'Y',
                'created_by'  => $sysUserId,
                'updated_by'  => $sysUserId,
            ];

            $this->db->table('sys_menu')->insert($menuData);
            $sysMenuId = (int) $this->db->insertID();
        }

        // Grant access to Parent Menu in sys_access_menu if not already present
        $existingParentAccess = $this->db->table('sys_access_menu')
            ->where([
                'sys_role_id'    => $sysRoleId,
                'sys_menu_id'    => $sysMenuId,
                'sys_submenu_id' => 0,
            ])
            ->get()
            ->getRow();

        if (!$existingParentAccess) {
            $this->db->table('sys_access_menu')->insert([
                'sys_role_id'    => $sysRoleId,
                'sys_menu_id'    => $sysMenuId,
                'sys_submenu_id' => 0,
                'isview'         => 'Y',
                'iscreate'       => 'Y',
                'isupdate'       => 'Y',
                'isdelete'       => 'Y',
                'isactive'       => 'Y',
                'created_by'     => $sysUserId,
                'updated_by'     => $sysUserId,
            ]);
        }

        // ------------------------------------------------------------------
        // 3. Insert Submenus (sys_submenu) & Access Permissions
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
                'name'        => 'Monitoring penilaian',
                'url'         => 'monitoring-penilaian',
                'sequence'    => 4,
                'action'      => 'N', // Report: Analytics, status dashboards, summary reports
                'status'      => 'Y',
                'isactive'    => 'Y',
                'created_by'  => $sysUserId,
                'updated_by'  => $sysUserId,
            ],
        ];

        // Insert submenus & their respective access permissions idempotently
        foreach ($submenus as $submenu) {
            $existingSubmenu = $this->db->table('sys_submenu')
                ->where([
                    'sys_menu_id' => $sysMenuId,
                    'url'         => $submenu['url'],
                ])
                ->get()
                ->getRow();

            if ($existingSubmenu) {
                $sysSubmenuId = (int) $existingSubmenu->sys_submenu_id;
            } else {
                $this->db->table('sys_submenu')->insert($submenu);
                $sysSubmenuId = (int) $this->db->insertID();
            }

            $existingSubAccess = $this->db->table('sys_access_menu')
                ->where([
                    'sys_role_id'    => $sysRoleId,
                    'sys_menu_id'    => $sysMenuId,
                    'sys_submenu_id' => $sysSubmenuId,
                ])
                ->get()
                ->getRow();

            if (!$existingSubAccess) {
                $this->db->table('sys_access_menu')->insert([
                    'sys_role_id'    => $sysRoleId,
                    'sys_menu_id'    => $sysMenuId,
                    'sys_submenu_id' => $sysSubmenuId,
                    'isview'         => 'Y',
                    'iscreate'       => 'Y',
                    'isupdate'       => 'Y',
                    'isdelete'       => 'Y',
                    'isactive'       => 'Y',
                    'created_by'     => $sysUserId,
                    'updated_by'     => $sysUserId,
                ]);
            }
        }
    }
}