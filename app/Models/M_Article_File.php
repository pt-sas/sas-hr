<?php

namespace App\Models;

use CodeIgniter\Model;

class M_Article_File extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'md_article_file';
    protected $primaryKey       = 'md_article_file_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = \App\Entities\ArticleFile::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'md_article_id',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'created_by',
        'updated_by',
        'isactive',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    // 1. Helper method to handle multiple file uploads
    protected function handleUploadAttachments($articleId)
    {
        $files = $this->request->getFiles();

        if (!empty($files['attachments'])) {
            $mFile = new M_Article_File();
            $path  = FCPATH . 'uploads/help/attachments/';

            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            foreach ($files['attachments'] as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $ext = strtolower($file->getClientExtension());
                    
                    // Allow PDF, DOC, DOCX only
                    if (in_array($ext, ['pdf', 'doc', 'docx'])) {
                        $newName  = $file->getRandomName();
                        $origName = $file->getClientName();
                        $size     = $file->getSize();

                        $file->move($path, $newName);

                        $mFile->insert([
                            'md_article_id' => $articleId,
                            'file_path'     => $newName,
                            'file_name'     => $origName,
                            'file_size'     => $size,
                            'file_type'     => $ext,
                            'created_by'    => session()->get('sys_user_id'),
                        ]);
                    }
                }
            }
        }
    }

    // 2. Call handleUploadAttachments inside create() and update()
    // Add this right after successful article save ($this->save()):
    // $articleId = $this->entity->md_article_id ?? $id;
    // $this->handleUploadAttachments($articleId);

    // 3. AJAX Endpoint: Delete individual attachment in Edit Modal
    public function deleteFile($fileId = null)
    {
        if ($this->request->isAJAX()) {
            try {
                $mFile = new M_Article_File();
                $file  = $mFile->find($fileId);

                if ($file) {
                    $filePath = FCPATH . 'uploads/help/attachments/' . $file->file_path;
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                    $mFile->delete($fileId);
                }

                return $this->response->setJSON(message('success', true, 'File berhasil dihapus.'));
            } catch (\Exception $e) {
                return $this->response->setJSON(message('error', false, $e->getMessage()));
            }
        }
    }
}
