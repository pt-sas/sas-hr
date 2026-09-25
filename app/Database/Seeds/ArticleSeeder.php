<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();

        helper('url');
        $now = date('Y-m-d H:i:s');

        // 1. Get category IDs from sys_ref_detail (ArticleCategory)
        $categories = $this->db->table('sys_ref_detail rd')
            ->select('rd.sys_ref_detail_id, rd.value')
            ->join('sys_reference r', 'r.sys_reference_id = rd.sys_reference_id')
            ->where('r.name', 'ArticleCategory')
            ->where('r.isactive', 'Y')
            ->where('rd.isactive', 'Y')
            ->get()
            ->getResult();

        $categoryMap = [];
        foreach ($categories as $c) {
            $categoryMap[$c->value] = $c->sys_ref_detail_id;
        }

        $defaultCategoryId = $categoryMap['GENERAL'] ?? ($categories[0]->sys_ref_detail_id ?? 1);

        // 2. Sample articles
        $articles = [
            [
                'title'    => 'Panduan Penggunaan Sistem HRIS',
                'content'  => '<p>Selamat datang di Pusat Bantuan. Artikel ini menjelaskan langkah-langkah dasar penggunaan aplikasi HRIS.</p><p>1. Login menggunakan akun resmi.<br>2. Akses menu profil untuk memperbarui data pribadi.</p>',
                'category' => 'GENERAL',
            ],
            [
                'title'    => 'Cara Mengajukan Cuti',
                'content'  => '<p>Berikut langkah-langkah mengajukan cuti:</p><ol><li>Masuk ke menu <strong>Cuti</strong>.</li><li>Klik tombol <strong>Ajukan Cuti</strong>.</li><li>Pilih tanggal mulai dan selesai.</li><li>Isi alasan cuti.</li><li>Klik <strong>Simpan</strong> untuk mengirim pengajuan ke atasan.</li></ol>',
                'category' => 'GENERAL',
            ],
            [
                'title'    => 'Cara Melihat Slip Gaji',
                'content'  => '<p>Slip gaji dapat diakses melalui menu <strong>Payroll</strong> → <strong>Slip Gaji</strong>. Pilih periode yang diinginkan untuk melihat rincian gaji bulanan Anda.</p>',
                'category' => 'GENERAL',
            ],
            [
                'title'    => 'Cara Memperbarui Data Pribadi',
                'content'  => '<p>Untuk memperbarui data pribadi, buka menu <strong>Profil</strong> → <strong>Data Pribadi</strong>. Pastikan data yang diisi sudah benar sebelum menyimpan.</p>',
                'category' => 'GENERAL',
            ],
        ];

        // 3. Insert each article if it doesn't already exist
        foreach ($articles as $article) {
            $slug = url_title($article['title'], '-', true);

            $existingArticle = $this->db->table('md_article')
                ->where('slug', $slug)
                ->get()
                ->getRow();

            if (!$existingArticle) {
                $categoryId = $categoryMap[$article['category']] ?? $defaultCategoryId;

                $this->db->table('md_article')->insert([
                    'sys_ref_detail_id' => $categoryId,
                    'title'             => $article['title'],
                    'slug'              => $slug,
                    'content'           => $article['content'],
                    'isactive'          => 'Y',
                    'created_by'        => 1,
                    'updated_by'        => 1,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }
        }

        $this->db->enableForeignKeyChecks();
    }
}