<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

// TODO : Accesses still wont checked out [ ]
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
        // 2. Insert Parent Menu (sys_menu)
        // ------------------------------------------------------------------
        // Action: 'N' (None) - Folder/dropdown parent header with no direct view page
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
        $sysMenuId = $this->db->insertID();

        // Grant access to Parent Menu in sys_access_menu
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

        // TODO : So there are also accesses for the menu. Need to know how this work [x]
        // 1. Insert access permission for the parent MENU itself (sys_submenu_id = 0)
        $this->db->table('sys_access_menu')->insert([
            'sys_role_id'    => $sysRoleId,
            'sys_menu_id'    => $sysMenuId,
            'sys_submenu_id' => 0, // Parent menu access
            'isview'         => 'Y',
            'iscreate'       => 'Y',
            'isupdate'       => 'Y',
            'isdelete'       => 'Y',
            'isactive'       => 'Y',
            'created_by'     => $sysUserId,
            'updated_by'     => $sysUserId,
        ]);

        // 2. Insert submenus & their respective access permissions
        foreach ($submenus as $submenu) {
            $this->db->table('sys_submenu')->insert($submenu);
            $sysSubmenuId = $this->db->insertID();

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