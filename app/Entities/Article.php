<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Article extends Entity
{
    protected $sys_ref_detail_id;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'md_article_id'     => 'integer',
        'created_by'        => 'integer',
        'updated_by'        => 'integer',
    ];

    /**
     * Set title and auto-generate slug if empty
     */
    public function setTitle(string $title)
    {
        $this->attributes['title'] = $title;

        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = url_title($title, '-', true);
        }

        return $this;
    }

    public function getArticleId()
    {
        return $this->attributes['md_article_id'] ?? null;
    }

    public function getDocTypeId()
    {
        return $this->attributes['sys_ref_detail_id'] ?? null;
    }

    public function getSysRefDetailId()
    {
        return $this->attributes['sys_ref_detail_id'] ?? null;
    }

    // TODO: Set this setter
    // public function setSysRefDetailId($sys_ref_detail_id)
    // {
    //     $this->attributes['sys_ref_detail_id'] = $sys_ref_detail_id;
    // }

    // Framework accessor for BaseController / Field mapper (strips '_id')
    public function getSysRefDetail()
    {
        return $this->attributes['sys_ref_detail_id'];
    }

    public function setSysRefDetail($id)
    {
        $this->attributes['sys_ref_detail_id'] = ($id !== null && $id !== '') ? (int)$id : null;
        return $this;
    }

    public function getCategoryName()
    {
        return $this->attributes['category_name'] ?? null;
    }

    public function getTitle()
    {
        return $this->attributes['title'] ?? null;
    }

    public function getSlug()
    {
        return $this->attributes['slug'] ?? null;
    }

    public function getContent()
    {
        return $this->attributes['content'] ?? null;
    }

    public function getStatus()
    {
        return $this->attributes['status'] ?? 'DRAFT';
    }

    public function getIsActive()
    {
        return $this->attributes['isactive'] ?? 'Y';
    }

    public function getCreatedBy()
    {
        return $this->attributes['created_by'] ?? null;
    }

    public function getUpdatedBy()
    {
        return $this->attributes['updated_by'] ?? null;
    }
}