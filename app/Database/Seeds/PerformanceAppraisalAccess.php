<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PerformanceAppraisalAccess extends Seeder
{
    public function run()
    {
        // ------------------------------------------------------------------
        // 1. Resolve Actor User ID (ID 1 or first active user)
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
        // 2. Resolve Parent Menu & Submenus
        // ------------------------------------------------------------------
        $menu = $this->db->table('sys_menu')
            ->where('url', 'performance-appraisal')
            ->get()
            ->getRow();

        if (!$menu) {
            throw new \RuntimeException('Parent menu "performance-appraisal" not found. Run PerformanceAppraisalMenu seeder first.');
        }

        $sysMenuId = (int) $menu->sys_menu_id;

        $submenuRows = $this->db->table('sys_submenu')
            ->where('sys_menu_id', $sysMenuId)
            ->get()
            ->getResult();

        $submenuMap = [];
        foreach ($submenuRows as $row) {
            $submenuMap[$row->url] = (int) $row->sys_submenu_id;
        }

        $requiredSubmenus = [
            'template-penilaian',
            'siklus-penilaian',
            'penilaian',
            'monitoring-penilaian',
        ];

        foreach ($requiredSubmenus as $url) {
            if (!isset($submenuMap[$url])) {
                throw new \RuntimeException("Submenu \"{$url}\" not found under performance-appraisal. Run PerformanceAppraisalMenu seeder first.");
            }
        }

        // ------------------------------------------------------------------
        // 3. Role Tier Definitions (ignoring W_% workflow/approval roles)
        // ------------------------------------------------------------------
        // Tier 1: Full access to all 4 submenus (Admin & Super HRD)
        $fullAccessRoles = [
            'awn Admin',
            'Role_Super_HRD',
        ];

        // Tier 2: Penilaian + Monitoring Penilaian (Direktur, GM, Assistant GM, OM, KACAB, Managers)
        $monitoringRoles = [
            'Role Direktur',
            'Role GM BO',
            'Role GM Sales',
            'Role GM BS',
            'Role Assistant GM BO',
            'Role Assistant GM Sales',
            'Role OM SUPOW',
            'Role OM TR All Territory',
            'Role KACAB GLD',
            'Role KACAB KLM',
            'Role KACAB',
            'Role IT Manager',
            'Role HRD Mgr',
            'Role Accounting Mgr',
            'Role Controller Mgr',
            'Role Logistik Mgr',
            'Role E-Com Mgr',
            'Role Finance Mgr',
            'Role GAF Mgr',
            'Role PRADA Mgr',
            'Role SS Mgr',
            'Role Tax Mgr',
        ];

        // Tier 3: Penilaian only (SPV, Staff, and all other active non-W_% roles)
        // Note: Matched dynamically against active non-W_% roles not in Tier 1 or Tier 2.

        $fullPerm = ['isview' => 'Y', 'iscreate' => 'Y', 'isupdate' => 'Y', 'isdelete' => 'Y'];
        $nonePerm = ['isview' => 'N', 'iscreate' => 'N', 'isupdate' => 'N', 'isdelete' => 'N'];

        $fullAccessLookup = array_flip(array_map('trim', $fullAccessRoles));
        $monitoringLookup = array_flip(array_map('trim', $monitoringRoles));

        // ------------------------------------------------------------------
        // 4. Fetch All Active Non-Workflow Roles & Upsert Accesses
        // ------------------------------------------------------------------
        $roles = $this->db->table('sys_role')
            ->where('isactive', 'Y')
            ->notLike('name', 'W_', 'after')
            ->orderBy('sys_role_id', 'ASC')
            ->get()
            ->getResult();

        foreach ($roles as $role) {
            $roleId   = (int) $role->sys_role_id;
            $roleName = trim((string) $role->name);

            if (isset($fullAccessLookup[$roleName])) {
                $submenuPerms = [
                    'template-penilaian'   => $fullPerm,
                    'siklus-penilaian'     => $fullPerm,
                    'penilaian'            => $fullPerm,
                    'monitoring-penilaian' => $fullPerm,
                ];
            } elseif (isset($monitoringLookup[$roleName])) {
                $submenuPerms = [
                    'template-penilaian'   => $nonePerm,
                    'siklus-penilaian'     => $nonePerm,
                    'penilaian'            => $fullPerm,
                    'monitoring-penilaian' => $fullPerm,
                ];
            } else {
                $submenuPerms = [
                    'template-penilaian'   => $nonePerm,
                    'siklus-penilaian'     => $nonePerm,
                    'penilaian'            => $fullPerm,
                    'monitoring-penilaian' => $nonePerm,
                ];
            }

            // Upsert Parent Menu Access (sys_submenu_id = 0)
            $this->upsertAccess($roleId, $sysMenuId, 0, $fullPerm, $sysUserId);

            // Upsert Submenu Accesses
            foreach ($submenuPerms as $url => $perm) {
                $this->upsertAccess($roleId, $sysMenuId, $submenuMap[$url], $perm, $sysUserId);
            }
        }
    }

    /**
     * Insert or update sys_access_menu idempotently for (sys_role_id, sys_menu_id, sys_submenu_id).
     */
    private function upsertAccess(int $roleId, int $menuId, int $submenuId, array $perm, int $sysUserId): void
    {
        $existing = $this->db->table('sys_access_menu')
            ->where([
                'sys_role_id'    => $roleId,
                'sys_menu_id'    => $menuId,
                'sys_submenu_id' => $submenuId,
            ])
            ->get()
            ->getRow();

        if ($existing) {
            $this->db->table('sys_access_menu')
                ->where('sys_access_menu_id', (int) $existing->sys_access_menu_id)
                ->update([
                    'isview'     => $perm['isview'],
                    'iscreate'   => $perm['iscreate'],
                    'isupdate'   => $perm['isupdate'],
                    'isdelete'   => $perm['isdelete'],
                    'isactive'   => 'Y',
                    'updated_by' => $sysUserId,
                ]);
        } else {
            $this->db->table('sys_access_menu')->insert([
                'sys_role_id'    => $roleId,
                'sys_menu_id'    => $menuId,
                'sys_submenu_id' => $submenuId,
                'isview'         => $perm['isview'],
                'iscreate'       => $perm['iscreate'],
                'isupdate'       => $perm['isupdate'],
                'isdelete'       => $perm['isdelete'],
                'isactive'       => 'Y',
                'created_by'     => $sysUserId,
                'updated_by'     => $sysUserId,
            ]);
        }
    }
}
