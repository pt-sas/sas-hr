<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ArticleSeederExtended extends Seeder
{
    public function run()
    {
        $this->db->disableForeignKeyChecks();

        helper('url');
        $now = date('Y-m-d H:i:s');

        // 1. Fetch category map (value => sys_ref_detail_id) filtered by ArticleCategory reference
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

        // Fallback default category ID if 'GENERAL' or active category is missing
        $defaultCategoryId = $categoryMap['GENERAL'] ?? ($categories[0]->sys_ref_detail_id ?? 1);

        // 2. Dataset containing various categories
        $articles = [
            // --- GENERAL ---
            [
                'category' => 'GENERAL',
                'title'    => 'Panduan Penggunaan Sistem HRIS',
                'content'  => '<p>Selamat datang di Pusat Bantuan HRIS. Artikel ini berisi panduan awal penggunaan portal karyawan:</p><ol><li><strong>Login System:</strong> Gunakan NIK dan password resmi perusahaan.</li><li><strong>Navigasi Menu:</strong> Akses menu utama melalui bilah navigasi di sebelah kiri.</li><li><strong>Keamanan Akun:</strong> Selalu lakukan <em>logout</em> setelah selesai mengoperasikan sistem pada perangkat publik.</li></ol>',
            ],
            [
                'category' => 'GENERAL',
                'title'    => 'Cara Memperbarui Data Profil Karyawan',
                'content'  => '<p>Untuk menjaga keakuratan data personal dan kontak darurat Anda:</p><p>1. Masuk ke <strong>Profil Saya</strong> → <strong>Edit Profil</strong>.<br>2. Perbarui nomor telepon, alamat domisili, atau kontak darurat.<br>3. Klik <strong>Simpan Perubahan</strong>.</p>',
            ],
            [
                'category' => 'GENERAL',
                'title'    => 'Struktur Organisasi dan Layanan Mandiri Karyawan (ESS)',
                'content'  => '<p>Fitur Employee Self-Service (ESS) memungkinkan Anda melihat posisi hirarki, melaporkan klaim, serta mengajukan permohonan administrasi secara terintegrasi tanpa perlu mengisi formulir kertas fisik.</p>',
            ],

            // --- ATTENDANCE (KEHADIRAN & CUTI) ---
            [
                'category' => 'ATTENDANCE',
                'title'    => 'Cara Mengajukan Cuti Tahunan',
                'content'  => '<p>Langkah-langkah pengajuan cuti tahunan melalui aplikasi HRIS:</p><ol><li>Buka menu <strong>Absensi & Cuti</strong> → <strong>Pengajuan Cuti</strong>.</li><li>Klik tombol <strong>+ Buat Pengajuan</strong>.</li><li>Pilih tipe cuti: <em>Cuti Tahunan</em>.</li><li>Tentukan rentang tanggal cuti dan tuliskan catatan pengajuan.</li><li>Klik <strong>Submit</strong> untuk meneruskan pengajuan ke atasan langsung.</li></ol>',
            ],
            [
                'category' => 'ATTENDANCE',
                'title'    => 'Prosedur Pengajuan Izin Sakit (Sick Leave)',
                'content'  => '<h2>Ketentuan Cuti Sakit Karyawan</h2><p>Berdasarkan Peraturan Ketenagakerjaan dan Kebijakan Perusahaan, pengajuan izin sakit wajib melampirkan bukti medis yang sah:</p><ul><li><strong>Sakit 1 Hari:</strong> Wajib memberikan pemberitahuan awal via aplikasi sebelum jam kerja dimulai.</li><li><strong>Sakit >1 Hari:</strong> Wajib melampirkan unggahan foto/PDF Surat Keterangan Dokter.</li></ul><p>Pengajuan dilakukan via menu <strong>Pengajuan Cuti</strong> → Tipe: <strong>Sakit</strong>.</p>',
            ],
            [
                'category' => 'ATTENDANCE',
                'title'    => 'Panduan Lupa Presensi (Correction/Clock In Miss)',
                'content'  => '<p>Jika Anda lupa melakukan clock-in atau clock-out pada mesin presensi:</p><p>Akses menu <strong>Absensi</strong> → <strong>Koreksi Presensi</strong>, pilih tanggal yang bermasalah, masukkan jam hadir sebenarnya, lalu cantumkan alasan yang valid sebelum dikirim ke atasan untuk disetujui.</p>',
            ],
            [
                'category' => 'ATTENDANCE',
                'title'    => 'Ketentuan Cuti Melahirkan dan Khusus',
                'content'  => '<p>Karyawan berhak mengajukan izin khusus sesuai undang-undang ketenagakerjaan:</p><ul><li><strong>Cuti Melahirkan:</strong> 3 bulan (dengan melampirkan surat HPL dari dokter/bidan).</li><li><strong>Pernikahan Karyawan:</strong> 3 hari kerja.</li><li><strong>Duka Cita (Keluarga Inti):</strong> 2 hari kerja.</li></ul>',
            ],

            // --- BENEFITS (PAYROLL & BENEFIT) ---
            [
                'category' => 'BENEFITS',
                'title'    => 'Cara Mengunduh Slip Gaji Bulanan',
                'content'  => '<p>Slip gaji bulanan diterbitkan setiap tanggal payroll resmi:</p><ol><li>Navigasi ke menu <strong>Payroll</strong> → <strong>Slip Gaji</strong>.</li><li>Pilih Periode Bulan dan Tahun.</li><li>Masukkan PIN Keamanan Payroll Anda.</li><li>Klik <strong>Download PDF</strong>.</li></ol>',
            ],
            [
                'category' => 'BENEFITS',
                'title'    => 'Prosedur Klaim Rawat Jalan & Asuransi Kesehatan',
                'content'  => '<p>Pengajuan klaim reimbursment kesehatan mandiri:</p><p>Unggah foto kuitansi asli, resep obat, serta rincian diagnosa dokter melalui menu <strong>Benefit</strong> → <strong>Klaim Kesehatan</strong>. Batas maksimal pengajuan klaim adalah 14 hari kerja setelah tanggal kuitansi.</p>',
            ],
            [
                'category' => 'BENEFITS',
                'title'    => 'Pendaftaran BPJS Ketenagakerjaan & Kesehatan',
                'content'  => '<p>Seluruh karyawan tetap dan kontrak berhak didaftarkan program BPJS. Untuk penambahan anggota keluarga (istri/anak), unggah dokumen Kartu Keluarga dan KTP pada menu <strong>Benefit Data BPJS</strong>.</p>',
            ],

            // --- IT_SUPPORT (BANTUAN IT) ---
            [
                'category' => 'IT_SUPPORT',
                'title'    => 'Cara Reset Password Email & Portal HRIS',
                'content'  => '<p>Apabila Anda lupa kata sandi akun kerja Anda:</p><p>1. Klik tautan <strong>Lupa Password?</strong> pada halaman login.<br>2. Masukkan alamat email kantor Anda.<br>3. Buka link verifikasi yang dikirimkan ke email untuk membuat password baru.</p>',
            ],
            [
                'category' => 'IT_SUPPORT',
                'title'    => 'Panduan Akses VPN Perusahaan untuk Kerja Remote (WFH)',
                'content'  => '<p>Untuk mengakses server internal saat bekerja di luar kantor:</p><ol><li>Unduh aplikasi FortiClient / GlobalProtect.</li><li>Masukkan Server Gateway: <code>vpn.company.com</code>.</li><li>Gunakan kredensial SSO Anda dan lakukan autentikasi 2FA.</li></ol>',
            ],
            [
                'category' => 'IT_SUPPORT',
                'title'    => 'Pengajuan Perangkat Kerja Baru (Laptop / Hardware)',
                'content'  => '<p>Permintaan pergantian laptop atau penambahan aksesori kerja dilakukan dengan membuat tiket bantuan pada menu <strong>IT Helpdesk</strong> → <strong>Request Hardware</strong> dengan persetujuan Head of Department.</p>',
            ],

            // --- POLICY (KEBIJAKAN PERUSAHAAN) ---
            [
                'category' => 'POLICY',
                'title'    => 'Kebijakan Kerahasiaan Data & Keamanan Informasi (NDA)',
                'content'  => '<p>Seluruh karyawan wajib menjaga kerahasiaan dokumen, kode sumber, data finansial, serta data pribadi pelanggan. Pelanggaran terhadap kebijakan ini dapat dikenakan sanksi Surat Peringatan (SP) hingga PHK.</p>',
            ],
            [
                'category' => 'POLICY',
                'title'    => 'Kode Etik Bisnis dan Penanggulangan Gratifikasi',
                'content'  => '<p>Perusahaan menerapkan prinsip <em>Zero Tolerance</em> terhadap suap dan gratifikasi. Karyawan dilarang menerima hadiah dari vendor atau klien melebihi batas nominal Rp 500.000 tanpa melaporkannya ke tim Compliance.</p>',
            ],
            [
                'category' => 'POLICY',
                'title'    => 'Peraturan Kerja Jam Kantor dan Fleksibilitas Waktu',
                'content'  => '<p>Jam kerja standar adalah 8 jam/hari (08:30 - 17:30 WIB) dengan waktu istirahat 1 jam. Toleransi keterlambatan maksimal adalah 15 menit per hari sebelum dianggap keterlambatan resmi.</p>',
            ],

            // --- PROCEDURE (PROSEDUR & PANDUAN) ---
            [
                'category' => 'PROCEDURE',
                'title'    => 'Prosedur Perjalanan Dinas & Klaim Reimbursement',
                'content'  => '<p>Tata cara pengajuan biaya dinas luar kota:</p><ol><li>Buat Form Perjalanan Dinas (SPD) sebelum keberangkatan.</li><li>Simpan seluruh bukti boarding pass, kuitansi hotel, dan nota makan.</li><li>Kirim klaim biaya maksimal 7 hari setelah perjalanan dinas selesai.</li></ol>',
            ],
            [
                'category' => 'PROCEDURE',
                'title'    => 'SOP Penilaian Kinerja Tahunan (Key Performance Indicator - KPI)',
                'content'  => '<p>Penilaian kinerja tahunan dilakukan dua kali setahun (Mid-Year dan Year-End Review). Karyawan mengisi self-appraisal pada portal HRIS sebelum dilanjutkan sesi perbincangan performa (*one-on-one*) bersama atasan.</p>',
            ],
            [
                'category' => 'PROCEDURE',
                'title'    => 'Prosedur Resign dan On-boarding / Off-boarding',
                'content'  => '<p>Pengajuan pengunduran diri wajib disampaikan secara tertulis minimal 30 hari sebelumnya (*one month notice*). Seluruh Aset kantor (Laptop, ID Card, Akses Card) wajib dikembalikan ke bagian IT & General Affairs pada hari terakhir bekerja.</p>',
            ],
            [
                'category' => 'PROCEDURE',
                'title'    => 'Pengajuan Pelatihan dan Pengembangan Diri (Training)',
                'content'  => '<p>Karyawan dapat mengajukan pelatihan external atau sertifikasi profesional yang relevan dengan tanggung jawab pekerjaan dengan mengisi formulir <strong>Training Request</strong> pada menu HR Development.</p>',
            ],
        ];

        // 3. Process and batch insert missing records
        $newBatch = [];

        foreach ($articles as $article) {
            $slug = url_title($article['title'], '-', true);

            $exists = $this->db->table('md_article')
                ->where('slug', $slug)
                ->get()
                ->getRow();

            if (!$exists) {
                $categoryId = $categoryMap[$article['category']] ?? $defaultCategoryId;

                $newBatch[] = [
                    'sys_ref_detail_id' => $categoryId,
                    'title'             => $article['title'],
                    'slug'              => $slug,
                    'content'           => html_entity_decode($article['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'isactive'          => 'Y',
                    'created_by'        => 1,
                    'updated_by'        => 1,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
            }
        }

        if (!empty($newBatch)) {
            $this->db->table('md_article')->insertBatch($newBatch);
        }

        $this->db->enableForeignKeyChecks();
    }
}