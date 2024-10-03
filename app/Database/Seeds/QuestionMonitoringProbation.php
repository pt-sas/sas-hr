<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class QuestionMonitoringProbation extends Seeder
{
    public function run()
    {
        $questiongroup = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'value'         => 'QG00004',
            'name'          => 'Feedback Atasan',
            'sequence'      => 1,
            'menu_url'      => 'monitor-percobaan',
            'description'   => 'Form Monitoring Lembur'
        ];

        $this->db->table('md_question_group')->insert($questiongroup);

        $questionlist = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 1,
                'question'      => 'Kecakapan karyawan melakukan tugas yang diberikan',
                'answertype'    => 'text',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 2,
                'question'      => 'Kerjasama karyawan dengan tim',
                'answertype'    => 'text',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 3,
                'question'      => 'Kedisiplinan kerja ( Kehadiran, Keterlambatan, Ijin, dll)',
                'answertype'    => 'text',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 4,
                'question'      => 'Hal yang berhasil dilakukan dengan baik',
                'answertype'    => 'text',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 5,
                'question'      => 'Perlu improvement',
                'answertype'    => 'text',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 6,
                'question'      => 'Hal yang ingin dipelajari/dicapai dalam 2 bulan ke depan',
                'answertype'    => 'text',
                'md_question_group_id' => $this->db->insertID()
            ],
        ];

        $this->db->table('md_question')->insertBatch($questionlist);

        $questiongroup = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'value'         => 'QG00005',
            'name'          => 'Feedback Karyawan baru',
            'sequence'      => 2,
            'menu_url'      => 'monitor-percobaan',
            'description'   => 'Form Monitoring Lembur'
        ];

        $this->db->table('md_question_group')->insert($questiongroup);

        $questionlist = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 1,
                'question'      => 'Kendala yang dihadapai saat bekerja',
                'answertype'    => 'text',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 2,
                'question'      => 'Hal-Hal yang mendukung dalam menyelesaikan pekerjaan',
                'answertype'    => 'text',
                'md_question_group_id' => $this->db->insertID()
            ],
        ];

        $this->db->table('md_question')->insertBatch($questionlist);
    }
}