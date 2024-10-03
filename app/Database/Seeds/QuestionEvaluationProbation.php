<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class QuestionEvaluationProbation extends Seeder
{
    public function run()
    {
        $questiongroup = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'value'         => 'QG00006',
            'name'          => 'Kedisiplinan',
            'sequence'      => 1,
            'menu_url'      => 'evaluasi-percobaan',
            'description'   => 'Form Evaluasi Lembur'
        ];

        $this->db->table('md_question_group')->insert($questiongroup);

        $questionlist = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 1,
                'question'      => 'Kehadiran tepat waktu',
                'answertype'    => 'scale',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 2,
                'question'      => 'Jumlah keterlambatan & Ijin',
                'answertype'    => 'scale',
                'md_question_group_id' => $this->db->insertID()
            ]
        ];

        $this->db->table('md_question')->insertBatch($questionlist);

        $questiongroup = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'value'         => 'QG00007',
            'name'          => 'Kinerja',
            'sequence'      => 2,
            'menu_url'      => 'evaluasi-percobaan',
            'description'   => 'Form Evaluasi Lembur'
        ];

        $this->db->table('md_question_group')->insert($questiongroup);

        $questionlist = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 1,
                'question'      => 'Kualitas Pekerjaan',
                'answertype'    => 'scale',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 2,
                'question'      => 'Kuantitas Pekerjaan',
                'answertype'    => 'scale',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 3,
                'question'      => 'Pemahaman Terhadap Tugas',
                'answertype'    => 'scale',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 4,
                'question'      => 'Kemampuan menyelesaikan Tugas',
                'answertype'    => 'scale',
                'md_question_group_id' => $this->db->insertID()
            ],
        ];

        $this->db->table('md_question')->insertBatch($questionlist);

        $questiongroup = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'value'         => 'QG00008',
            'name'          => 'Sikap & Prilaku',
            'sequence'      => 3,
            'menu_url'      => 'evaluasi-percobaan',
            'description'   => 'Form Evaluasi Lembur'
        ];

        $this->db->table('md_question_group')->insert($questiongroup);

        $questionlist = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 1,
                'question'      => 'Kerjasama dengan tim',
                'answertype'    => 'scale',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 2,
                'question'      => 'Kepatuhan terhadap Peraturan',
                'answertype'    => 'scale',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 3,
                'question'      => 'Adaptasi terhadap lingkungan',
                'answertype'    => 'scale',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 4,
                'question'      => 'Respon terhadap feedback',
                'answertype'    => 'scale',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 5,
                'question'      => 'Inisiatif dan Kreativitas**',
                'answertype'    => 'scale',
                'md_question_group_id' => $this->db->insertID()
            ],
        ];

        $this->db->table('md_question')->insertBatch($questionlist);
    }
}