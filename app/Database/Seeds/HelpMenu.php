<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class HelpMenu extends Seeder
{
    public function run()
    {
        // SEEDING HELP (Bantuan) MENU
        // 1. Seed `sys_menu`, `sys_submenu`, and `sys_reference` tables with initial data for the Help (Bantuan) menu.
        // TODO: Check this against the migration
        // sys_menu
        $data = [
            [
                'sys_menu_id' => 20,
                'isactive' => 'Y',
                'created_by' => 1,
                'updated_by' => 1,
                'name' => 'Bantuan',
                'url' => 'help',
                'sequence' => 11, // TODO: Edit this sequence number to the appropriate value [x]
                'icon' => 'fas fa-question-circle',
                'action' => 'T',
                'initialcode' => '',
                'status' => '',
            ],
        ];
        $this->db->table('sys_menu')->insertBatch($data);

        // sys_reference
        $headerData = [
            'name'        => 'SYS_Reference Validation Types',
            'description' => 'Validation type options',
            'isactive'    => 'Y',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        $this->db->table('sys_reference')->insert($headerData);
        $referenceId = $this->db->insertID();

        // sys_ref_detail
        $detailData = [
            [
                'sys_reference_id' => $referenceId,
                'value'            => 'VAL_REQUIRED',
                'name'             => 'Required Field',
                'description'      => 'Field cannot be empty',
                'isactive'         => 'Y',
                'created_at'       => date('Y-m-d H:i:s'),
            ],
            [
                'sys_reference_id' => $referenceId,
                'value'            => 'VAL_NUMERIC',
                'name'             => 'Numeric Only',
                'description'      => 'Field must be numbers only',
                'isactive'         => 'Y',
                'created_at'       => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('sys_ref_detail')->insertBatch($detailData);

        // 2. Seed the Help (Bantuan) for its role based access 
        // Seed sys_role
        // Should check if this role already exists to avoid duplicates
        // TODO : Should revisit this later, temporary using backend logic instead

        // 1. Disable FK checks to prevent order-dependency crashes
        $this->db->disableForeignKeyChecks();

        // 2. Seed sys_role
        $roleData = [
            [
                'sys_role_id'          => 1,
                'isactive'             => 'Y',
                'created_at'           => '2023-04-10 03:07:11',
                'created_by'           => 1,
                'updated_at'           => '2026-09-15 13:47:38',
                'updated_by'           => 1,
                'name'                 => 'awn Admin',
                'description'          => 'Master Role',
                'ismanual'             => 'N',
                'iscanexport'          => 'Y',
                'iscanreport'          => 'Y',
                'isallowmultipleprint' => 'Y',
            ],
        ];
        $this->db->table('sys_role')->ignore(true)->insertBatch($roleData);

        // 3. Seed sys_user
        $userData = [
            [
                'sys_user_id'           => 1,
                'isactive'              => 'Y',
                'created_at'            => '2023-04-10 03:07:11',
                'created_by'            => 1,
                'updated_at'            => '2026-09-15 14:49:11',
                'updated_by'            => 1,
                'sys_employee_id'       => 0,
                'username'              => 'sas',
                'name'                  => 'SAS',
                'password'              => password_hash('sas123', PASSWORD_BCRYPT), // Clear intent & fallback
                'islogged'              => 'N',
                'date_password_updated' => '2026-09-15 14:49:11',
                'last_login'            => '2026-09-14 15:01:52',
                'sys_org_id'            => 0,
            ],
        ];
        $this->db->table('sys_user')->ignore(true)->insertBatch($userData);

        // 4. Seed sys_user_role (Pivot)
        $userRoleData = [
            [
                'sys_user_role_id' => 1,
                'isactive'          => 'Y',
                'created_at'        => '2023-04-10 03:07:11',
                'created_by'        => 1,
                'updated_at'        => '2026-09-14 15:01:52',
                'updated_by'        => 1,
                'sys_user_id'       => 1,
                'sys_role_id'       => 1,
            ],
        ];
        $this->db->table('sys_user_role')->ignore(true)->insertBatch($userRoleData);

        // 5. Seed sys_access_menu
        $accessMenuData = [
            [
                'sys_access_menu_id' => 1,
                'isactive'           => 'Y',
                'created_at'         => '2023-04-10 03:10:07',
                'created_by'         => 1,
                'updated_at'         => '2026-09-15 13:47:38',
                'updated_by'         => 1,
                'sys_role_id'        => 1,
                'sys_menu_id'        => 1,
                'sys_submenu_id'     => 0,
                'isview'             => 'Y',
                'iscreate'           => 'Y',
                'isupdate'           => 'Y',
                'isdelete'           => 'Y',
            ],
        ];
        $this->db->table('sys_access_menu')->ignore(true)->insertBatch($accessMenuData);

        // 6. Re-enable FK checks
        $this->db->enableForeignKeyChecks();
    }
}
