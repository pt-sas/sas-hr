<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ArticleFile extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'md_article_file_id' => 'integer',
        'md_article_id'      => 'integer',
        'file_size'          => 'integer',
    ];

    /**
     * Formats raw byte size into KB, MB, or GB
     */
    public function getFormattedSize(): string
    {
        $bytes = (int) ($this->attributes['file_size'] ?? 0);

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 0) {
            return $bytes . ' B';
        }

        return '0 KB';
    }

    /**
     * Resolves to: http://localhost:8080/uploads/help/attachments/{filename}
     */
    public function getFileUrl(): string
    {
        $file = $this->attributes['file_path'] 
            ?? $this->attributes['filepath'] 
            ?? $this->attributes['file_name'] 
            ?? $this->attributes['filename'] 
            ?? '';

        if (empty($file)) {
            return '#';
        }

        // Clean any leading slashes or 'public/' prefix
        $clean = ltrim(str_replace('public/', '', $file), '/');

        // Ensure the path includes 'uploads/help/attachments/'
        if (!str_contains($clean, 'uploads/help/attachments/')) {
            $clean = 'uploads/help/attachments/' . basename($clean);
        }

        return base_url($clean);
    }

    /**
     * Gets display file name
     */
    public function getFileName(): string
    {
        return $this->attributes['file_name'] 
            ?? $this->attributes['filename'] 
            ?? $this->attributes['name'] 
            ?? 'Lampiran File';
    }

    public function getIconClass(): string
    {
        $ext = strtolower(pathinfo($this->attributes['file_name'] ?? '', PATHINFO_EXTENSION));
        $type = strtolower($this->attributes['file_type'] ?? '');

        if ($ext === 'pdf' || str_contains($type, 'pdf')) {
            return 'far fa-file-pdf text-danger';
        } elseif (in_array($ext, ['doc', 'docx', 'odt', 'rtf'])) {
            return 'far fa-file-word text-primary';
        } elseif (in_array($ext, ['xls', 'xlsx', 'csv', 'ods'])) {
            return 'far fa-file-excel text-success';
        } elseif (in_array($ext, ['ppt', 'pptx', 'odp']) || str_contains($type, 'presentation') || str_contains($type, 'powerpoint')) {
            return 'far fa-file-powerpoint text-warning';
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return 'far fa-file-image text-info';
        } elseif (in_array($ext, ['zip', 'rar', '7z'])) {
            return 'far fa-file-archive text-secondary';
        } elseif ($ext === 'txt') {
            return 'far fa-file-alt text-secondary';
        }

        return 'far fa-file text-muted';
    }
}
