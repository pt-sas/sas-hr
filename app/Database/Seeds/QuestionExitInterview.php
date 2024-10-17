<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class QuestionExitInterview extends Seeder
{
    public function run()
    {
        $questiongroup = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'value'         => 'QG00001',
            'name'          => 'Hal-hal yang masih harus diselesaikan',
            'sequence'      => 1,
            'menu_url'      => 'interview-keluar',
            'description'   => 'Form Exit Interview'
        ];

        $this->db->table('md_question_group')->insert($questiongroup);

        $questionlist = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 1,
                'question'      => 'Sisa Saldo Kas Bon',
                'answertype'    => 'text',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 2,
                'question'      => 'Biaya HP yang masih harus dibayar',
                'answertype'    => 'text',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 3,
                'question'      => 'Faktur Penjualan yang belum dibayar',
                'answertype'    => 'text',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 4,
                'question'      => 'Selisih barang hasil opname',
                'answertype'    => 'text',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 5,
                'question'      => 'Lain-lain',
                'answertype'    => 'text',
                'md_question_group_id' => $this->db->insertID()
            ]
        ];

        $this->db->table('md_question')->insertBatch($questionlist);

        $questiongroup = [
            'created_by'    => 1,
            'updated_by'    => 1,
            'value'         => 'QG00002',
            'name'          => 'Yang harus ditarik dari karyawan',
            'sequence'      => 2,
            'menu_url'      => 'interview-keluar',
            'description'   => 'Form Exit Interview'
        ];

        $this->db->table('md_question_group')->insert($questiongroup);

        $questionlist = [
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 1,
                'question'      => 'Aset (Mobil/Motor)',
                'answertype'    => 'checkbox',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 2,
                'question'      => 'Komputer / Laptop',
                'answertype'    => 'checkbox',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 3,
                'question'      => 'Alat Tulis (Kalkulator, dll)',
                'answertype'    => 'checkbox',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 4,
                'question'      => 'Seragam',
                'answertype'    => 'checkbox',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 5,
                'question'      => 'Kartu Nama',
                'answertype'    => 'checkbox',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 6,
                'question'      => 'Chip HP / Kartu Telepon',
                'answertype'    => 'checkbox',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 7,
                'question'      => 'Dokumen-dokumen pekerjaan',
                'answertype'    => 'checkbox',
                'md_question_group_id' => $this->db->insertID()
            ],
            [
                'created_by'    => 1,
                'updated_by'    => 1,
                'no'            => 8,
                'question'      => 'Lain-lain',
                'answertype'    => 'text',
                'md_question_group_id' => $this->db->insertID()
            ]
        ];

        $this->db->table('md_question')->insertBatch($questionlist);
    }
}
