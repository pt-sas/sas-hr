<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Article extends Model
{
    protected $table            = 'md_article';
    protected $primaryKey       = 'md_article_id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\Article::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'sys_ref_detail_id',
        'title',
        'slug',
        'content',
        'isactive',
        'created_by',
        'updated_by',
    ];

    // Active State Constants
    public const ACTIVE   = 'Y';
    public const INACTIVE = 'N';

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // DataTables Configuration
    public $column_order = [
        'md_article.md_article_id',  // 0. ID
        '',                          // 1. No
        'rd.name',                   // 2. Category (sys_ref_detail)
        'md_article.title',          // 3. Article Title
        'md_article.content',        // 4. Text / Content
        'md_article.isactive',       // 5. Active
        '',                          // 6. Actions
    ];

    public $column_search = [
        'md_article.title',
        'md_article.content',
        'rd.name',
    ];

    public $order = ['md_article.created_at' => 'DESC'];

    protected $request;

    public function __construct(RequestInterface $request = null)
    {
        parent::__construct();
        if ($request) {
            $this->request = $request;
        }
    }

    public function getSelect(): string
    {
        return $this->table . '.*, rd.name as category_name';
    }

    public function getJoin(): array
    {
        return [
            [
                'tableJoin'  => 'sys_ref_detail rd',
                'columnJoin' => 'rd.sys_ref_detail_id = ' . $this->table . '.sys_ref_detail_id',
                'typeJoin'   => 'left',
            ],
        ];
    }

    // ENUM & DROPDOWN HELPERS
    public function getListActiveState(): array
    {
        return [
            ['id' => self::ACTIVE,   'text' => 'Active (Y)'],
            ['id' => self::INACTIVE, 'text' => 'Inactive (N)'],
        ];
    }
}