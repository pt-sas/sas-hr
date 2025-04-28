<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_ChangeLog extends Model
{
    protected $table      = 'sys_changelog';
    protected $primaryKey = 'sys_changelog_id';
    protected $allowedFields = [
        'sys_sessions_id',
        'table',
        'column',
        'isactive',
        'created_by',
        'updated_by',
        'record_id',
        'oldvalue',
        'newvalue',
        'description',
        'eventchangelog'
    ];
    protected $useTimestamps = true;
    protected $returnType = 'App\Entities\ChangeLog';
    protected $request;
    protected $db;
    protected $builder;

    private $entity;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->request = $request;
        $this->builder = $this->db->table($this->table);
        $this->entity = new \App\Entities\ChangeLog();
    }

    public function insertLog($table, $column, $recordID, $oldValue, $newValue, $event, $description = null)
    {
        $this->entity->setTable($table);
        $this->entity->setColumn($column);
        $this->entity->setRecordId($recordID);
        $this->entity->setOldValue($oldValue);
        $this->entity->setNewValue($newValue);
        $this->entity->setEventChangeLog($event);
        $this->entity->setDescription($description);
        $this->entity->setCreatedBy(session()->get('sys_user_id'));
        $this->entity->setUpdatedBy(session()->get('sys_user_id'));

        return $this->save($this->entity);
    }
}