<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Article;
use Config\Services;

use function PHPSTORM_META\type;

class Help extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model   = new M_Article($this->request);
        $this->entity  = new \App\Entities\Article();

        $this->primaryKey = $this->model->primaryKey;
        $this->db = \Config\Database::connect();
        $this->menu = 'category-article';
    }

    public function index()
    {
        $db = \Config\Database::connect();
        $categories = $db->table('sys_ref_detail rd')
            ->select('rd.sys_ref_detail_id as id, rd.name')
            ->join('sys_reference r', 'r.sys_reference_id = rd.sys_reference_id')
            ->where('r.name', 'ArticleCategory')
            ->where('r.isactive', 'Y')
            ->where('rd.isactive', 'Y')
            ->orderBy('rd.name', 'ASC')
            ->get()->getResult();

        return $this->template->render('masterdata/help/v_help', [
            'categories' => $categories,
        ]);
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            try {
                $table  = $this->model->table;
                $select = $this->model->getSelect();
                $join   = $this->model->getJoin();
                $order  = $this->model->column_order;
                $sort   = $this->model->order;
                $search = $this->model->column_search;

                // Build Filter Parameters (framework sends filters under 'form')
                $where = [];
                $formFilter = $this->request->getPost('form') ?? [];

                foreach ($formFilter as $row) {
                    $name  = $row['name']  ?? null;
                    $value = $row['value'] ?? null;

                    if (empty($value)) continue;

                    if ($name === 'sys_ref_detail_id') {
                        $where['md_article.sys_ref_detail_id'] = $value;
                    }
                    if ($name === 'isactive') {
                        $where['md_article.isactive'] = $value;
                    }
                }

                $data   = [];
                $number = (int) ($this->request->getPost('start') ?? 0);

                // Fetch Filtered Data
                $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

                foreach ($list as $value) {
                    $row = [];

                    $ID           = is_object($value) ? ($value->md_article_id ?? null) : ($value['md_article_id'] ?? null);
                    $content      = is_object($value) ? ($value->content ?? '') : ($value['content'] ?? '');
                    $categoryName = is_object($value) ? ($value->category_name ?? '-') : ($value['category_name'] ?? '-');
                    $title        = is_object($value) ? ($value->title ?? '-') : ($value['title'] ?? '-');
                    $isactive     = is_object($value) ? ($value->isactive ?? 'N') : ($value['isactive'] ?? 'N');

                    $number++;

                    $cleanContent = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

                    $row[] = $ID;
                    $row[] = $number;
                    $row[] = $categoryName;
                    $row[] = $title;
                    $stripped = strip_tags($cleanContent);
                    $row[] = mb_strlen($stripped) > 80
                        ? mb_substr($stripped, 0, 80) . '…'
                        : $stripped;
                    $row[] = function_exists('active') ? active($isactive) : $isactive;
                    $row[] = isset($this->template) ? $this->template->tableButton($ID) : '';

                    $data[] = $row;
                }

                $result = [
                    'draw'            => (int) ($this->request->getPost('draw') ?? 1),
                    'recordsTotal'    => $this->datatable->countAll($table, $select, $order, $sort, $search, $join, $where),
                    'recordsFiltered' => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join, $where),
                    'data'            => $data,
                ];

                return $this->response->setJSON($result);

            } catch (\Throwable $e) {
                return $this->response->setJSON([
                    'draw'            => (int) ($this->request->getPost('draw') ?? 1),
                    'recordsTotal'    => 0,
                    'recordsFiltered' => 0,
                    'data'            => [],
                    'error'           => $e->getMessage()
                ]);
            }
        }
    }

    protected function handleUploadAttachments($articleId, $files)
    {
        $debug = [];

        if (empty($files)) {
            $debug[] = "ERROR: No files were passed to the function.";
            return $debug;
        }

        $mFile = new \App\Models\M_Article_File();
        $path  = FCPATH . 'uploads/help/attachments/';

        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                $debug[] = "ERROR: Failed to create directory at {$path}";
            }
        }

        foreach ($files as $file) {
            // 1. CHECK IF FILE UPLOADED CORRECTLY TO TEMP FOLDER
            if (!$file->isValid()) {
                $debug[] = "ERROR for '{$file->getName()}': " . $file->getErrorString() . " (Code: " . $file->getError() . ")";
                continue;
            }

            if (!$file->hasMoved()) {
                $ext = strtolower($file->getClientExtension());
                
                // 2. CHECK EXTENSION
                if (!in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf', 'odt', 'ods', 'odp', 'zip', 'rar', 'png', 'jpg', 'jpeg', 'webp', 'gif'])) {
                    $debug[] = "ERROR for '{$file->getName()}': Invalid extension '{$ext}'.";
                    continue;
                }

                $newName  = $file->getRandomName();
                $origName = $file->getClientName();
                $size     = $file->getSize();

                try {
                    // 3. ATTEMPT TO MOVE
                    $file->move($path, $newName);
                    $debug[] = "SUCCESS: Moved '{$origName}' to '{$path}'.";

                    // 4. ATTEMPT TO INSERT DB (USING QUERY BUILDER!)
                    // We use $this->db->table() to bypass Entity/Model strictness
                    $this->db->table('md_article_file')->insert([
                        'md_article_id' => (int) $articleId, // Cast to int for safety
                        'file_path'     => $newName,
                        'file_name'     => $origName,
                        'file_size'     => $size,
                        'file_type'     => $ext,
                        'created_by'    => session()->get('sys_user_id') ?? 1,
                        'isactive'      => 'Y'
                    ]);
                    
                    $debug[] = "SUCCESS: Inserted '{$origName}' into database.";

                } catch (\Exception $e) {
                    $debug[] = "ERROR during move/insert for '{$origName}': " . $e->getMessage();
                }
            }
        }

        return $debug;
    }

    public function deleteFile($fileId = null)
    {
        if ($this->request->isAJAX()) {
            try {
                $mFile = new \App\Models\M_Article_File();
                $file  = $mFile->find($fileId);

                if ($file) {
                    $filePath = FCPATH . 'uploads/help/attachments/' . $file->file_path;
                    if (file_exists($filePath)) {
                        @unlink($filePath); // Delete from server
                    }
                    $mFile->delete($fileId); // Delete from DB
                }

                return $this->response->setJSON(message('success', true, 'File berhasil dihapus.'));
            } catch (\Exception $e) {
                return $this->response->setJSON(message('error', false, $e->getMessage()));
            }
        }
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = (array) $this->request->getVar();
            $files = $this->request->getFileMultiple('attachments');

            try {
                $pk = $this->model->primaryKey; // 'md_article_id'
                $id = $post[$pk] ?? $post['id'] ?? null;
                $isUpdate = !empty($id);
                $articleId = $isUpdate ? (int) $id : null;

                // 1. Decode base64 content
                if (!empty($post['content'])) {
                    $decoded = base64_decode($post['content'], true);
                    $post['content'] = htmlentities($decoded !== false ? $decoded : $post['content'], ENT_QUOTES, 'UTF-8');
                }

                if (!empty($post['sys_ref_detail_id'])) {
                    $post['sys_ref_detail'] = $post['sys_ref_detail_id'];
                }

                // 2. Load existing record entity if updating
                if ($isUpdate) {
                    $existingEntity = $this->model->find($articleId);
                    if ($existingEntity) {
                        $this->entity = $existingEntity;
                    }
                    $post[$pk] = $articleId;
                    unset($post['id']);
                }

                $this->entity->fill($post);

                if ($isUpdate) {
                    $this->entity->{$pk} = $articleId;
                }

                if (!$this->validation->run($post, 'article')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $response = $this->save();

                    if (!empty($response[0]['success']) || !empty($response['success'])) {
                        
                        // 3. Resolve the actual article ID (existing ID for updates, inserted ID for creates)
                        $resolvedArticleId = $isUpdate ? $articleId : $this->model->getInsertID();

                        // 4. DELETE MARKED FILES (If updating and files were marked with strikethrough)
                        $deletedFileIds = $this->request->getPost('deleted_file_ids');
                        if (!empty($deletedFileIds) && is_array($deletedFileIds) && $resolvedArticleId > 0) {
                            $mFile = new \App\Models\M_Article_File();
                            $filesToDelete = $mFile->where('md_article_id', $resolvedArticleId)
                                                  ->whereIn('md_article_file_id', $deletedFileIds)
                                                  ->findAll();

                            foreach ($filesToDelete as $delFile) {
                                $filePath = is_object($delFile) ? $delFile->file_path : $delFile['file_path'];
                                $fullPath = FCPATH . 'uploads/help/attachments/' . $filePath;
                                if (file_exists($fullPath)) {
                                    @unlink($fullPath); // Delete physical file
                                }
                            }

                            $mFile->where('md_article_id', $resolvedArticleId)
                                  ->whereIn('md_article_file_id', $deletedFileIds)
                                  ->delete(); // Delete DB record
                        }

                        // 5. UPLOAD NEW ATTACHMENTS
                        $allFiles = $this->request->getFiles();
                        $files = $allFiles['attachments'] ?? $this->request->getFileMultiple('attachments');

                        if (!empty($files) && $resolvedArticleId > 0) {
                            $uploadDebug = $this->handleUploadAttachments($resolvedArticleId, $files);
                        }
                    }
                }
            } catch (\Exception $e) {
                log_message('error', '[Help::create] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function show($id)
    {
        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();

                if (!empty($list)) {

                    if (!empty($list[0]->content)) {
                        $list[0]->content = html_entity_decode($list[0]->content, ENT_QUOTES, 'UTF-8');
                    }

                    if (!empty($list[0]->sys_ref_detail_id)) {
                        $rowRef = $this->db->table('sys_ref_detail')
                            ->select('sys_ref_detail_id, name')
                            ->where('sys_ref_detail_id', $list[0]->sys_ref_detail_id)
                            ->get()
                            ->getRow();

                        if ($rowRef) {
                            $list = $this->field->setDataSelect(
                                'sys_ref_detail',
                                $list,
                                'sys_ref_detail_id',
                                $rowRef->sys_ref_detail_id,
                                $rowRef->name 
                            );        
                        }
                    }
                }
                $title = 'Detail Artikel';
                if (isset($list[0])) {
                    $title = method_exists($list[0], 'getTitle')
                        ? $list[0]->getTitle()
                        : ($list[0]->title ?? 'Detail Artikel');
                }

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $mFile = new \App\Models\M_Article_File();
                $attachments = $mFile->where('md_article_id', $id)->where('isactive', 'Y')->findAll();

                $result = [
                    'header' => $this->field->store($fieldHeader),
                    'attachments' => $attachments
                ];

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
    

    public function destroy($id)
    {
        if ($this->request->isAJAX()) {
            try {
                // Delete physical image/attachment
                $mFile = new \App\Models\M_Article_File();
                $attachments = $mFile->where('md_article_id', $id)->findAll();
                
                if (!empty($attachments)) {
                    foreach ($attachments as $file) {
                        // Assuming you used the entity, or array. Adjust syntax if needed:
                        $fileName = is_object($file) ? $file->file_path : $file['file_path'];
                        $filePath = FCPATH . 'uploads/help/attachments/' . $fileName;
                        
                        // Delete from hard drive
                        if (file_exists($filePath)) {
                            @unlink($filePath); 
                        }
                    }
                    // Delete from database
                    $mFile->where('md_article_id', $id)->delete();
                }

                $result   = $this->delete($id);
                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getListArticle()
    {
        if ($this->request->isAjax()) {
            $post     = $this->request->getVar();
            $response = [];

            try {
                if (isset($post['search'])) {
                    $list = $this->model->where('isactive', 'Y')
                        ->like('title', $post['search'])
                        ->orderBy('title', 'ASC')
                        ->findAll();
                } else {
                    $list = $this->model->where('isactive', 'Y')
                        ->orderBy('title', 'ASC')
                        ->findAll();
                }

                foreach ($list as $key => $row) :
                    $response[$key]['id']   = $row->getArticleId();
                    $response[$key]['text'] = $row->getTitle();
                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getListDocCategory()
    {
        $response = [];

        try {
            $db = \Config\Database::connect();

            $search = $this->request->getVar('search') ?? $this->request->getVar('term');

            if ($search === 'undefined' || $search === 'null') {
                $search = '';
            }

            $builder = $db->table('sys_ref_detail rd')
                ->select('rd.sys_ref_detail_id as id, rd.name as text')
                ->join('sys_reference r', 'r.sys_reference_id = rd.sys_reference_id')
                ->where('r.name', 'ArticleCategory')
                ->where('r.isactive', 'Y')
                ->where('rd.isactive', 'Y');

            if (!empty(trim($search))) {
                $builder->like('rd.name', trim($search));
            }

            $list = $builder->orderBy('rd.name', 'ASC')->get()->getResultArray();

            foreach ($list as $row) {
                $cleanText = mb_convert_encoding($row['text'], 'UTF-8', 'UTF-8');

                $response[] = [
                    'id'   => (string) $row['id'],
                    'text' => $cleanText,
                ];
            }

        } catch (\Throwable $e) {
            $response = [];
        }

        return $this->response->setJSON($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function uploadImage()
    {
        if ($this->request->isAJAX()) {
            $file = $this->request->getFile('file');

            if (!$file || !$file->isValid()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No file uploaded',
                ]);
            }

            // Validate
            if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Only images allowed',
                ]);
            }

            if ($file->getSize() > 5 * 1024 * 1024) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Max 5MB',
                ]);
            }

            // Generate unique name
            $newName = $file->getRandomName();
            $path    = FCPATH . 'uploads/help/image';

            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            $file->move($path, $newName);

            return $this->response->setJSON([
                'success' => true,
                'url'     => base_url('uploads/help/image/' . $newName),
            ]);
        }
    }

    public function getListStatus()
    {
        if ($this->request->isAJAX()) {
            try {
                $response = $this->model->getListStatus();
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getListActiveState()
    {
        if ($this->request->isAJAX()) {
            try {
                $response = $this->model->getListActiveState();
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}