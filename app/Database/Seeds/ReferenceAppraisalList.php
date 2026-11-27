<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReferenceAppraisalList extends Seeder
{
    public function run()
    {
        $references = [
            [
                'name'           => 'AppraisalWorkflowStatus',
                'description'    => 'Performance Appraisal Workflow Status',
                'validationtype' => 'L',
                'details'        => [
                    ['value' => 'PENILAIAN',        'name' => 'Penilaian'],
                    ['value' => 'SUBMIT_PENILAIAN', 'name' => 'Dinilai'],
                    ['value' => 'REVIEW',           'name' => 'Peninjauan'],
                    ['value' => 'SUBMIT_REVIEW',    'name' => 'Ditinjau'],
                    ['value' => 'FINAL',            'name' => 'Selesai'],
                ],
            ],
            [
                'name'           => 'AppraisalTemplateStatus',
                'description'    => 'Performance Appraisal Template Status',
                'validationtype' => 'L',
                'details'        => [
                    ['value' => 'DRAFT',     'name' => 'Draft'],
                    ['value' => 'PUBLISHED', 'name' => 'Published'],
                    ['value' => 'ARCHIVED',  'name' => 'Archived'],
                ],
            ],
            [
                'name'           => 'AppraisalCycleStatus',
                'description'    => 'Performance Appraisal Cycle Status',
                'validationtype' => 'L',
                'details'        => [
                    ['value' => 'DRAFT',  'name' => 'Draft'],
                    ['value' => 'OPEN',   'name' => 'Open'],
                    ['value' => 'CLOSED', 'name' => 'Closed'],
                ],
            ],
            [
                'name'           => 'AppraisalTargetGroup',
                'description'    => 'Performance Appraisal Target Group',
                'validationtype' => 'L',
                'details'        => [
                    ['value' => 'PERMANENT', 'name' => 'Permanent'],
                    ['value' => 'OPERATOR',  'name' => 'Operator'],
                ],
            ],
        ];

        foreach ($references as $ref) {
            $existingRef = $this->db->table('sys_reference')
                ->where('name', $ref['name'])
                ->get()
                ->getRow();

            if ($existingRef) {
                $refId = (int) $existingRef->sys_reference_id;
            } else {
                $this->db->table('sys_reference')->insert([
                    'created_by'     => 1,
                    'updated_by'     => 1,
                    'name'           => $ref['name'],
                    'description'    => $ref['description'],
                    'validationtype' => $ref['validationtype'],
                    'isactive'       => 'Y',
                ]);
                $refId = (int) $this->db->insertID();
            }

            foreach ($ref['details'] as $detail) {
                $existsDetail = $this->db->table('sys_ref_detail')
                    ->where('sys_reference_id', $refId)
                    ->where('value', $detail['value'])
                    ->get()
                    ->getRow();

                if (!$existsDetail) {
                    $this->db->table('sys_ref_detail')->insert([
                        'created_by'       => 1,
                        'updated_by'       => 1,
                        'value'            => $detail['value'],
                        'name'             => $detail['name'],
                        'isactive'         => 'Y',
                        'sys_reference_id' => $refId,
                    ]);
                }
            }
        }
    }
}
