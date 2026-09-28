<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Article;
use App\Models\M_Article_File;
use Config\Database;
use Config\Services;

class HelpFront extends BaseController
{
    protected $mArticle;
    protected $mArticleFile;
    protected $db;

    public function __construct()
    {
        helper(['text', 'url']);

        $this->request  = Services::request();
        $this->db       = Database::connect();
        $this->mArticle     = new M_Article();
        $this->mArticleFile = new M_Article_File();
    }

    /**
     * Help Center Landing Page
     */
    public function index()
    {
        $categories = $this->db->table('sys_ref_detail rd')
            ->select('rd.sys_ref_detail_id as help_category_id, rd.name, rd.description, rd.value as icon')
            ->join('sys_reference r', 'r.sys_reference_id = rd.sys_reference_id')
            // Replace 'articles' with your actual article table name
            ->join('md_article a', 'a.sys_ref_detail_id = rd.sys_ref_detail_id', 'inner')
            ->where('r.name', 'ArticleCategory')
            ->where('r.isactive', 'Y')
            ->where('rd.isactive', 'Y')
            ->where('a.isactive', 'Y') // Only count active articles
            ->groupBy('rd.sys_ref_detail_id') // Avoid duplicates
            ->get()
            ->getResultArray();

        $data = [
            'title'      => 'Pusat Bantuan',
            'categories' => $categories,
        ];

        return $this->template->render('help/v_help', $data);
    }

    /**
     * Category landing — redirects to first article in the category
     */
    public function category($categoryId = null)
    {
        $category = $this->db->table('sys_ref_detail')
            ->where('sys_ref_detail_id', $categoryId)
            ->where('isactive', 'Y')
            ->get()
            ->getRowArray();

        if (!$category) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Kategori tidak ditemukan.');
        }

        // Fetch the first article under this category
        $firstArticle = $this->mArticle
            ->where('sys_ref_detail_id', $categoryId)
            ->where('isactive', 'Y')
            ->orderBy('title', 'ASC')
            ->first();

        if (!$firstArticle) {
            return redirect()->to(base_url('sas/help'))->with('error', 'Belum ada artikel dalam kategori ini.');
        }

        $articleId = is_object($firstArticle) ? $firstArticle->md_article_id : $firstArticle['md_article_id'];
        return redirect()->to(base_url('sas/help/article/' . $articleId));
    }

    /**
     * Single Article Reader View
     */
public function detail($articleId = null)
{
    // 1. Fetch current active article with category and author name
    $activeArticle = $this->mArticle
        ->select('md_article.*, rd.name as category_name, rd.sys_ref_detail_id as category_id, u.name as created_by_name')
        ->join('sys_ref_detail rd', 'rd.sys_ref_detail_id = md_article.sys_ref_detail_id', 'left')
        ->join('sys_user u', 'u.sys_user_id = md_article.created_by', 'left')
        ->where('md_article.md_article_id', $articleId)
        ->where('md_article.isactive', 'Y')
        ->get()
        ->getRow();

    if (!$activeArticle) {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Artikel tidak ditemukan.');
    }

    // Decode HTML entities for reader view
    if (!empty($activeArticle->content)) {
        $activeArticle->content = html_entity_decode($activeArticle->content, ENT_QUOTES, 'UTF-8');
    }

    // 2. Fetch raw attachments from md_article_file
    $rawAttachments = $this->mArticleFile
        ->where('md_article_id', $articleId)
        ->where('isactive', 'Y')
        ->findAll();

    $attachments = [];
    foreach ($rawAttachments as $file) {
        // Safe extraction without property name mangling
        if ($file instanceof \CodeIgniter\Entity\Entity) {
            $fileData = $file->toArray();
        } elseif (is_object($file)) {
            $fileData = get_object_vars($file);
        } else {
            $fileData = (array) $file;
        }

        // Standardize column name aliases (handles filename/filesize/filepath)
        if (!isset($fileData['file_name']) && isset($fileData['filename'])) {
            $fileData['file_name'] = $fileData['filename'];
        }
        if (!isset($fileData['file_size']) && isset($fileData['filesize'])) {
            $fileData['file_size'] = $fileData['filesize'];
        }
        if (!isset($fileData['file_path']) && isset($fileData['filepath'])) {
            $fileData['file_path'] = $fileData['filepath'];
        }

        // Force 'pdf' for file_type if extension or type is pdf
        $checkType = $fileData['file_type'] ?? $fileData['file_name'] ?? '';
        if (str_contains(strtolower($checkType), 'pdf')) {
            $fileData['file_type'] = 'pdf';
        }

        $attachments[] = new \App\Entities\ArticleFile($fileData);
    }

    // 3. Fetch all articles under this category for the sidebar navigation
    $sidebarArticles = $this->mArticle
        ->where('sys_ref_detail_id', $activeArticle->category_id)
        ->where('isactive', 'Y')
        ->orderBy('title', 'ASC')
        ->findAll();

    $data = [
        'title'           => $activeArticle->title,
        'activeArticle'   => $activeArticle,
        'sidebarArticles' => $sidebarArticles,
        'attachments'     => $attachments,
    ];

    return $this->template->render('help/v_detail', $data);
}

    /**
     * Live Search AJAX Handler
     */
    public function search()
    {
        if ($this->request->isAJAX()) {
            $keyword  = $this->request->getVar('query');
            $response = [];

            try {
                if (!empty($keyword)) {
                    $list = $this->mArticle
                        ->select('md_article.md_article_id, md_article.title, rd.name as category_name')
                        ->join('sys_ref_detail rd', 'rd.sys_ref_detail_id = md_article.sys_ref_detail_id', 'left')
                        ->where('md_article.isactive', 'Y')
                        ->groupStart()
                            ->like('md_article.title', $keyword)
                            ->orLike('md_article.content', $keyword)
                        ->groupEnd()
                        ->orderBy('md_article.title', 'ASC')
                        ->findAll();

                    foreach ($list as $row) {
                        $response[] = [
                            'id'       => $row->md_article_id,
                            'title'    => $row->title,
                            'category' => $row->category_name ?? 'General',
                            'url'      => base_url('sas/help/article/' . $row->md_article_id),
                        ];
                    }
                }
            } catch (\Exception $e) {
                return $this->response->setJSON(message('error', false, $e->getMessage()));
            }

            return $this->response->setJSON(['status' => true, 'data' => $response]);
        }
    }
}