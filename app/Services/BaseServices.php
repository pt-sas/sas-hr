<?php

namespace App\Services;

use App\Libraries\Field;
use App\Models\M_ChangeLog;
use CodeIgniter\Exceptions\ModelException;
use Config\Services;
use stdClass;

class BaseServices
{
    protected $request;

    protected $model;
    protected $modelDetail;
    protected $modelSubDetail;

    protected $entity;
    protected $table;
    protected $email;

    //* LIBRARY
    protected $field;

    //EVENT
    /** Insert = I */
    protected $EVENTCHANGELOG_Insert = "I";
    /** Update = U */
    protected $EVENTCHANGELOG_Update = "U";
    /** Delete = D */
    protected $EVENTCHANGELOG_Delete = "D";

    //DOCSTATUS
    /** Drafted = DR */
    protected $DOCSTATUS_Drafted = "DR";
    /** Completed = CO */
    protected $DOCSTATUS_Completed = "CO";
    /** Approved = AP */
    protected $DOCSTATUS_Approved = "AP";
    /** Not Approved = NA */
    protected $DOCSTATUS_NotApproved = "NA";
    /** Voided = VO */
    protected $DOCSTATUS_Voided = "VO";
    /** Invalid = IN */
    protected $DOCSTATUS_Invalid = "IN";
    /** In Progress = IP */
    protected $DOCSTATUS_Inprogress = "IP";
    /** Suspended = OS */
    protected $DOCSTATUS_Suspended = "OS";
    /** Aborted = AB */
    protected $DOCSTATUS_Aborted = "AB";
    /** Requested = RE */
    protected $DOCSTATUS_Requested = "RE";
    /** Aborted = XL */
    protected $DOCSTATUS_Unlock = "XL";
    /** Reopen = RO */
    protected $DOCSTATUS_Reopen = "RO";

    //STATUS HIDUP
    /** Status HIDUP*/
    protected $Status_Hidup = 'HIDUP';
    /** Status MENINGGAL */
    protected $Status_Meninggal = 'MENINGGAL';

    //STATUS KARYAWAN
    /** Status PERMANENT*/
    protected $Status_PERMANENT = 100001;
    /** Status PROBATION */
    protected $Status_PROBATION = 100002;
    /** Status OUTSOURCING*/
    protected $Status_OUTSOURCING = 100003;
    /** Status RESIGN */
    protected $Status_RESIGN = 100004;
    /** Status PENSIUN */
    protected $Status_PENSIUN = 100005;
    /** Status FREELANCE */
    protected $Status_FREELANCE = 100006;
    /** Status MAGANG */
    protected $Status_MAGANG = 100007;
    /** Status KONTRAK */
    protected $Status_KONTRAK = 100008;

    //SUBMISSION TYPE
    /** Tipe Pengajuan Satu Hari */
    protected $Form_Satu_Hari = [100001, 100002, 100003, 100004, 100005, 100007, 100008];
    /** Tipe Pengajuan Setengah Hari */
    protected $Form_Setengah_Hari = [100006, 100009, 100010, 100011, 100012, 100013];

    //LINE STATUS
    /** Line Status Pengajuan Approval */
    protected $LINESTATUS_Approval = 'H';
    /** Line Status Pengajuan Realisasi Atasan */
    protected $LINESTATUS_Realisasi_Atasan = 'M';
    /** Line Status Pengajuan Realisasi HRD */
    protected $LINESTATUS_Realisasi_HRD = 'S';
    /** Line Status Pengajuan Disetujui */
    protected $LINESTATUS_Disetujui = 'Y';
    /** Line Status Pengajuan Ditolak */
    protected $LINESTATUS_Ditolak = 'N';
    /** Line Status Pengajuan Dibatalkan */
    protected $LINESTATUS_Dibatalkan = 'C';

    // STATUS CALENDAR PERIOD
    /** Period Status Closed */
    protected $PERIOD_CLOSED = 'C';
    /** Period Status Open */
    protected $PERIOD_OPEN = 'O';

    /**
     * The column used for primaryKey int
     *
     * @var int
     */
    protected $primaryKey = '';

    /**
     * The column used for insert int
     *
     * @var int
     */
    protected $createdByField = 'created_by';

    /**
     * The column used for update int
     *
     * @var int
     */
    protected $updatedByField = 'updated_by';

    /**
     * The column used for insert timestamps
     *
     * @var string
     */
    protected $createdField = 'created_at';

    /**
     * The column used for update timestamps
     *
     * @var string
     */
    protected $updatedField = 'updated_at';

    /**
     * The type of column that created_at and updated_at
     * are expected to.
     *
     * Allowed: 'datetime', 'date', 'int'
     *
     * @var string
     */
    protected $dateFormat = 'datetime';

    /**
     * The message used for return value string
     *
     * @var string
     */
    protected $message = '';

    /**
     * An array of field names that are allowed
     * to be set by the user in inserts/updates.
     *
     * @var array
     */
    protected $allowedFields = [];

    /**
     * Integer of field for get last insert id after insert
     *
     * @var int
     */
    protected $insertID = 0;

    /**
     * Integer of field for get user session
     *
     * @var int
     */
    protected $userID = 0;

    /**
     * Boolean of field for identification record is new or update
     *
     * @var boolean
     */
    protected $isNewRecord = false;

    /**
     * PATH Folder for upload data
     *
     * @var directory
     */
    protected $PATH_UPLOAD = FCPATH . "/uploads/";

    /**
     * PATH Folder for Pengajuan upload data
     *
     * @var directory
     */
    protected $PATH_Pengajuan = "pengajuan";

    /**
     * PATH Folder for Pengajuan upload data Keterangan
     *
     * @var directory
     */
    protected $PATH_Keterangan_Sakit = "keterangan";

    public function __construct()
    {
        helper(['action_helper', 'url', 'date_helper']);
        $this->request = Services::request();

        //* Load Libraries
        $this->field = new Field();
    }

    public function save()
    {
        //* Object class Old Value
        $oldV = new stdClass();
        //* Object class new Value
        $newV = new stdClass();

        $this->primaryKey = $this->model->primaryKey;
        $modelTable = $this->model->table;
        $beforeUpdate = $this->model->beforeUpdate;
        $afterUpdate = $this->model->afterUpdate;
        $isChange = false;
        $newRecord = $this->isNew();

        //* Set column is allowedFields 
        $this->setAllowedFields([
            $this->primaryKey,
            $this->createdField,
            $this->createdByField,
            $this->updatedField,
            $this->updatedByField
        ]);

        $data = $this->transformDataToArray($this->entity, 'insert');

        // TODO : Cleansing data, only allowed fields to inserted or updated
        $data = $this->doStrip($data);

        $this->model->db->transBegin();

        try {
            $fields = $this->model->db->getFieldData($modelTable);

            // TODO : Get old data if update data
            if (!$newRecord)
                $row = $this->model->find($this->getID());

            // TODO : Populate data to oldV and newV
            foreach ($data as $key => $val) {
                if ($newRecord) {
                    $newV->{$key} = $val;
                } else {
                    // TODO : Convert value type integer is null to Zero
                    foreach ($fields as $field) :
                        if ($field->type === 'int' && $field->name === $key && $data[$key] === "")
                            $data[$key] = 0;
                    endforeach;

                    if (is_null($row->{$key}))
                        $row->{$key} = "";

                    if ((gettype($data[$key]) === 'integer' && $data[$key] != $row->{$key}) ||
                        (gettype($data[$key]) === 'string' && $data[$key] != $row->{$key})
                    ) {
                        //* Old Value
                        $oldV->{$key} = $row->{$key};

                        //* New Value
                        $newV->{$key} = $val;

                        $isChange = true;
                    }

                    $newV->{$this->primaryKey} = $this->getID();
                }
            }

            // TODO : Check is created by or updated by already set?
            if ($newRecord && !array_key_exists($this->createdByField, $data))
                $newV->{$this->createdByField} = $this->userID;

            if (
                $newRecord || (!$newRecord && ($isChange || (!empty($beforeUpdate) || !empty($afterUpdate))) && !array_key_exists($this->updatedByField, $data))
            )
                $newV->{$this->updatedByField} = $this->userID;

            if (!empty($this->table) && json_decode($this->table)) {
                $arrLine = json_decode($this->table);

                if ($newRecord)
                    $ok = $this->saveBatch('insert', $this->modelDetail, $newV, $arrLine);
                else
                    $ok = $this->saveBatch('update', $this->modelDetail, $newV, $arrLine);
            } else {
                $ok = $this->model->save($newV);
                $this->insertID = $this->model->getInsertID();
            }

            if ($ok) {
                if ($newRecord) {
                    $arrInsertData[] = (object) [
                        'table'     => $modelTable,
                        'column'    => $this->model->primaryKey,
                        'record_id' => $this->insertID,
                        'old_value' => null,
                        'new_value' => $this->insertID
                    ];

                    // TODO : Insert change log header
                    $this->changeLog($modelTable, $this->model->primaryKey, $arrInsertData, null, $this->EVENTCHANGELOG_Insert);
                } else {
                    $arrNew = $this->transformDataToArray($newV, 'insert');

                    $arrNew = $this->doStrip($arrNew);

                    $arrChangeData = [];

                    foreach ($arrNew as $key => $val) {
                        if (!in_array($key, $this->allowedFields)) {
                            $arrChangeData[] = (object) [
                                'table'     => $modelTable,
                                'column'    => $key,
                                'record_id' => $this->getID(),
                                'old_value' => $oldV->{$key},
                                'new_value' => $newV->{$key}
                            ];
                        }
                    }

                    $this->changeLog($modelTable, $this->model->primaryKey, $arrChangeData, null, $this->EVENTCHANGELOG_Update);
                }

                $ok = true;
            } else {
                $ok = false;
            }

            $this->model->db->transCommit();
        } catch (\Exception $e) {
            $this->model->db->transRollBack();
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return $ok;
    }

    protected function saveBatch(string $event, $model, object $obj, array $data)
    {
        $result = false;

        if (!is_array($data) || empty($data))
            return false;

        if (!empty($this->allowedFields))
            array_push($this->allowedFields, $model->primaryKey);

        //* Convert object to array
        $arrDataHeader = $this->transformDataToArray($obj, 'insert');

        // TODO : Run function change field before inserting data line
        if (method_exists($model, 'doChangeValueField'))
            $data = $model->doChangeValueField($data, $this->getID(), $obj);

        // TODO : Cleansing data, only allowed fields to inserted or updated
        $data = $this->doStripLine($data, $model);

        // TODO : Split data
        $data = $this->doSplitData($data, $model->primaryKey);

        // TODO : Insert data
        if ($event === 'insert' || isset($data['insert'])) {
            $dataInsert = $data['insert'];

            // TODO : Insert header data
            if (empty($this->getID())) {
                $result = $this->model->save($obj);
                $this->insertID = $this->model->getInsertID();
            } else {
                if (count($arrDataHeader) > 1)
                    $result = $this->model->save($obj);

                $this->insertID = $this->getID();
            }

            //* Set Foreign Key Field from header table 
            $foreignKey = $this->primaryKey;
            $dataInsert = $this->doSetField($foreignKey, $this->insertID, $dataInsert);

            //* Set Created_By Field 
            $dataInsert = $this->doSetField($this->createdByField, $this->userID, $dataInsert);

            //* Set Updated_By Field 
            $dataInsert = $this->doSetField($this->updatedByField, $this->userID, $dataInsert);

            //* Old data line 
            $lineId = [];
            $oldData = $model->where($foreignKey, $this->insertID)->findAll();

            //? Check old data is exist
            if ($oldData)
                foreach ($oldData as $row) :
                    $lineId[] = $row->{$model->primaryKey};
                endforeach;

            //TODO: Insert line data 
            $result = $model->builder->insertBatch($dataInsert);

            if ($result > 0) {
                //? Check data line
                if (empty($lineId)) {
                    $newData = $model->where($foreignKey, $this->insertID)->findAll();
                } else {
                    $newData = $model->whereNotIn($model->primaryKey, $lineId)
                        ->where($foreignKey, $this->insertID)
                        ->findAll();
                }

                $arrInsertData = [];

                foreach ($newData as $val) {
                    $arrInsertData[] = (object) [
                        'table'     => $model->table,
                        'column'    => $model->primaryKey,
                        'record_id' => $val->{$model->primaryKey},
                        'old_value' => null,
                        'new_value' => $val->{$model->primaryKey}
                    ];
                }

                // TODO : Insert log
                $this->changeLog($model->table, $model->primaryKey, $arrInsertData, null, $this->EVENTCHANGELOG_Insert);
            }
        }

        // TODO : Update data
        if ($event === 'update' && isset($data['update'])) {
            $dataUpdate = $data['update'];

            //TODO: Check value change 
            $dataUpdate = $this->getValueChange($model, $dataUpdate, $model->primaryKey);

            //? Header no data to update and Line No data and Not set property insert when submit data
            if (count($arrDataHeader) == 1 && empty($dataUpdate) && !isset($data['insert']) && !isset($data['delete']))
                $result = $this->model->save($obj);

            //? Header exists data
            if (count($arrDataHeader) > 1)
                $result = $this->model->save($obj);

            //? Check line data
            if (!empty($dataUpdate)) {
                //* Set Updated_At Field 
                $dataUpdate = $this->doSetField($this->updatedField, $this->setDate(), $dataUpdate);

                //* Set Updated_By Field 
                $dataUpdate = $this->doSetField($this->updatedByField, $this->userID, $dataUpdate);

                //TODO: Populate data old value and new value 
                $arrChangeData = [];
                foreach ($dataUpdate as $new) :
                    $old = $model->find($new[$model->primaryKey]);

                    $new = (array) $new;
                    foreach (array_keys($new) as $column) :
                        if (!empty($this->allowedFields) && !in_array($column, $this->allowedFields))
                            $arrChangeData[] = (object) [
                                'table'     => $model->table,
                                'column'    => $column,
                                'record_id' => $new[$model->primaryKey],
                                'old_value' => $old->{$column},
                                'new_value' => $new[$column]
                            ];
                    endforeach;
                endforeach;

                //TODO: Update line data 
                $result = $model->builder->updateBatch($dataUpdate, $model->primaryKey);

                if ($result > 0) {
                    //TODO: Insert change log 
                    $this->changeLog($model->table, $model->primaryKey, $arrChangeData, null, $this->EVENTCHANGELOG_Update);
                }
            }
        }

        if (isset($data['delete'])) {
            $dataDelete = $data['delete'];

            // TODO : Update line data
            $result = $model->delete($dataDelete['id']);

            if ($result) {
                // TODO : Insert delete log
                $this->changeLog($model->table, $model->primaryKey, $dataDelete, $model->db->getFieldNames($model->table), $this->EVENTCHANGELOG_Delete);

                $result = 1;
            } else {
                $result = 0;
            }
        }

        return $result > 0 ? true : false;
    }

    public function delete(int $id): bool
    {
        $row = $this->model->where($this->model->primaryKey, $id)->findAll();
        $result = $this->model->delete($id);

        if ($result) {
            $this->changeLog($this->model->table, $this->model->primaryKey, $row, $this->model->db->getFieldNames($this->model->table), $this->EVENTCHANGELOG_Delete);

            if ($this->modelDetail) {
                $lines = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();

                if ($this->modelSubDetail) {
                    foreach ($lines as $detail) {
                        $subLines = $this->modelSubDetail->where(
                            $this->modelDetail->primaryKey,
                            $detail->{$this->modelDetail->primaryKey}
                        )->findAll();
                        $this->modelSubDetail->where(
                            $this->modelDetail->primaryKey,
                            $detail->{$this->modelDetail->primaryKey}
                        )->delete();
                        $this->changeLog($this->modelSubDetail->table, $this->modelSubDetail->primaryKey, $subLines, $this->modelSubDetail->db->getFieldNames($this->modelSubDetail->table), $this->EVENTCHANGELOG_Delete);
                    }
                }

                $this->modelDetail->where($this->model->primaryKey, $id)->delete();
                $this->changeLog($this->modelDetail->table, $this->modelDetail->primaryKey, $lines, $this->modelDetail->db->getFieldNames($this->modelDetail->table), $this->EVENTCHANGELOG_Delete);
            }
        }

        return $result;
    }

    public function deleteBatch(array $id): bool
    {
        foreach ($id as $val) {
            $row = $this->model->where($this->model->primaryKey, $val)->findAll();
            $result = $this->model->delete($val);

            if ($result) {
                $this->changeLog($this->model->table, $this->model->primaryKey, $row, $this->model->db->getFieldNames($this->model->table), $this->EVENTCHANGELOG_Delete);

                if ($this->modelDetail) {
                    $line = $this->modelDetail->where($this->primaryKey, $val)->findAll();

                    if ($this->modelSubDetail) {
                        foreach ($line as $detail) {
                            $subLines = $this->modelSubDetail->where(
                                $this->modelDetail->primaryKey,
                                $detail->{$this->modelDetail->primaryKey}
                            )->findAll();

                            $this->modelSubDetail->where(
                                $this->modelDetail->primaryKey,
                                $detail->{$this->modelDetail->primaryKey}
                            )->delete();

                            $this->changeLog($this->modelSubDetail->table, $this->modelSubDetail->primaryKey, $subLines, $this->modelSubDetail->db->getFieldNames($this->modelSubDetail->table), $this->EVENTCHANGELOG_Delete);
                        }
                    }

                    $this->modelDetail->where($this->primaryKey, $val)->delete();

                    //** Inserting log changes for detail*/
                    $this->changeLog($this->modelDetail->table, $this->modelDetail->primaryKey, $line, $this->modelDetail->db->getFieldNames($this->modelDetail->table), $this->EVENTCHANGELOG_Delete);
                }
            } else {
                return false;
            }
        }

        return true;
    }


    /**
     * It could be used when you have to change default or override current allowed fields.
     *
     * @param array $allowedFields Array with names of fields
     *
     * @return $this
     */
    protected function setAllowedFields(array $allowedFields)
    {
        $this->allowedFields = $allowedFields;

        return $this;
    }

    /**
     * Ensures that only the fields that are allowed to be updated
     * are in the data array.
     *
     * @param array $data Data
     */
    protected function doStrip(array $data): array
    {
        foreach (array_keys($data) as $key) :
            if (!in_array($key, $this->model->allowedFields, true))
                unset($data[$key]);
        endforeach;

        return $data;
    }

    /**
     * Ensures that only the fields that are allowed to be updated
     * are in the data array table line.
     *
     * @param array $data
     * @return array
     */
    protected function doStripLine(array $data, $model = null): array
    {
        $result = [];

        if (!is_null($model) && is_object($model))
            $this->modelDetail = $model;

        foreach ($data as $value) :
            $data = (array) $value;

            foreach (array_keys($data) as $key) :
                if (!in_array($key, $this->modelDetail->allowedFields, true) && $key !== $this->modelDetail->primaryKey)
                    unset($data[$key]);
            endforeach;

            $result[] = $data;
        endforeach;

        return $result;
    }

    /**
     * Transform data to array
     *
     * @param array|object|null $data Data
     * @param string            $type Type of data (insert|update)
     */
    protected function transformDataToArray($data, string $type): array
    {
        // If $data is using a custom class with public or protected
        // properties representing the collection elements, we need to grab
        // them as an array.
        if (is_object($data) && !$data instanceof stdClass)
            $data = $this->objectToArray($data, ($type === 'update'), true);

        // If it's still a stdClass, go ahead and convert to
        // an array so doProtectFields and other model methods
        // don't have to do special checks.
        if (is_object($data))
            $data = (array) $data;

        return $data;
    }

    /**
     * Takes a class an returns an array of it's public and protected
     * properties as an array suitable for use in creates and updates.
     * This method use objectToRawArray internally and does conversion
     * to string on all Time instances
     *
     * @param object|string $data        Data
     * @param bool          $onlyChanged Only Changed Property
     * @param bool          $recursive   If true, inner entities will be casted as array as well
     *
     * @throws ReflectionException
     *
     * @return array Array
     */
    protected function objectToArray($data, bool $onlyChanged = true, bool $recursive = false): array
    {
        $properties = $this->objectToRawArray($data, $onlyChanged, $recursive);

        return $properties;
    }

    /**
     * Takes a class an returns an array of it's public and protected
     * properties as an array with raw values.
     *
     * @param object|string $data        Data
     * @param bool          $onlyChanged Only Changed Property
     * @param bool          $recursive   If true, inner entities will be casted as array as well
     *
     * @throws ReflectionException
     *
     * @return array|null Array
     */
    protected function objectToRawArray($data, bool $onlyChanged = true, bool $recursive = false): ?array
    {
        if (method_exists($data, 'toRawArray')) {
            $properties = $data->toRawArray($onlyChanged, $recursive);
        } else {
            $mirror = new ReflectionClass($data);
            $props  = $mirror->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

            $properties = [];

            // Loop over each property,
            // saving the name/value in a new array we can return.
            foreach ($props as $prop) :
                // Must make protected values accessible.
                $prop->setAccessible(true);
                $properties[$prop->getName()] = $prop->getValue($data);
            endforeach;
        }

        return $properties;
    }

    /**
     * 	Is new record
     *	@return true if new
     */
    protected function isNew()
    {
        //* Get Request POST From View 
        // $post = $this->request->getVar();

        // //? Check property id or object primaryKey
        // if (((isset($post['id']) && !empty($post['id']) && isset($this->entity->{$this->primaryKey})) ||
        //         (isset($post['id']) && !empty($post['id'])) ||
        //         isset($this->entity->{$this->primaryKey}))
        //     && !$this->isNewRecord
        // ) {
        //     return false;
        // }

        if (isset($this->entity->{$this->primaryKey}) && !empty($this->entity->{$this->primaryKey}))
            return false;

        return true;
    }

    /**
     *  Return Single Key Record ID
     *  @return ID or 0
     */
    protected function getID()
    {
        if (isset($this->entity->{$this->primaryKey}) && !empty($this->entity->{$this->primaryKey}))
            return $this->entity->{$this->primaryKey};

        return 0;
    }

    /**
     * Split of data (insert|update|update)
     *
     * @param array $data Data
     * @return array
     */
    protected function doSplitData(array $data, string $primaryKey): array
    {
        $result = [];
        $id = [];

        foreach ($data as $value) {
            if (empty($value[$primaryKey])) {
                unset($value[$primaryKey]);

                $result['insert'][] = $value;
            } else {
                $result['update'][] = $value;
                $id[] = $value[$primaryKey];
            }
        }

        if (!empty($this->getID())) {
            $foreignKey = $this->primaryKey;

            //? Check function is exists 
            if (method_exists($this->modelDetail, 'doCheckExistData')) {
                $list = $this->modelDetail->doCheckExistData($data, $id, $this->getID());
            } else {
                $listQuery = $this->modelDetail->where($foreignKey, $this->getID());

                if ($id) {
                    $listQuery->whereNotIn($primaryKey, $id);
                }
                $list = $listQuery->findAll();
            }

            // Proses data yang akan dihapus
            foreach ($list as $row) {
                $result['delete']['id'][] = $row->{$primaryKey};
                $result['delete']['data'][] = $row;
            }
        }

        return $result;
    }

    /**
     * Add New Property to array
     *
     * @param [type] $field
     * @param [type] $value
     * @param array $data Data
     * @return array
     */
    protected function doSetField($field, $value, array $data): array
    {
        $result = [];

        foreach ($data as $row) :
            if (!isset($row[$field]))
                $row[$field] = $value;

            $result[] = $row;
        endforeach;

        return $result;
    }

    /**
     * Retrieves a changed data from old data
     *
     * @param [type] $model class
     * @param array $data Data
     * @return array
     */
    protected function getValueChange($model, array $data, string $primaryKey): array
    {
        $result = [];

        $fields = $model->db->getFieldData($model->table);

        if (empty($data))
            return $result;

        foreach ($data as $value) :
            //* Object class New Value 
            $newV = new stdClass();

            //TODO: Get old value based on primary key data 
            $oldValue = $model->find($value[$primaryKey]);

            $data = (array) $value;

            foreach (array_keys($data) as $key) :
                //TODO: Convert value type integer is null to Zero
                foreach ($fields as $field) :
                    if ($field->type === 'int' && $field->name === $key && $value[$key] === "")
                        $value[$key] = 0;
                endforeach;

                //? New value is change 
                if ((gettype($value[$key]) === 'integer' && $value[$key] != $oldValue->{$key}) ||
                    (gettype($value[$key]) === 'string' && $value[$key] !== $oldValue->{$key})
                ) {
                    $newV->{$primaryKey} = $value[$primaryKey];
                    $newV->{$key} = $value[$key];
                }
            endforeach;

            $arrNew = $this->transformDataToArray($newV, 'insert');

            if (!empty($arrNew))
                $result[] = $arrNew;

        endforeach;

        return $result;
    }

    /**
     * Sets the date or current date if null value is passed
     *
     * @param int|null $userData An optional PHP timestamp to be converted.
     *
     * @throws ModelException
     *
     * @return mixed
     */
    protected function setDate(?int $userData = null)
    {
        $currentDate = $userData ?? time();

        return $this->intToDate($currentDate);
    }

    /**
     * A utility function to allow child models to use the type of
     * date/time format that they prefer. This is primarily used for
     * setting created_at, updated_at and deleted_at values, but can be
     * used by inheriting classes.
     *
     * The available time formats are:
     *  - 'int'      - Stores the date as an integer timestamp
     *  - 'datetime' - Stores the data in the SQL datetime format
     *  - 'date'     - Stores the date (only) in the SQL date format.
     *
     * @param int $value value
     *
     * @throws ModelException
     *
     * @return int|string
     */
    protected function intToDate(int $value)
    {
        switch ($this->dateFormat) {
            case 'int':
                return $value;

            case 'datetime':
                return date('Y-m-d H:i:s', $value);

            case 'date':
                return date('Y-m-d', $value);

            default:
                throw ModelException::forNoDateFormat(static::class);
        }
    }

    private function changeLog($table, $primaryKey, array $rows, $fields, $event)
    {
        $changeLog = new M_ChangeLog($this->request);

        foreach ($rows as $row) {
            if ($event == $this->EVENTCHANGELOG_Delete && !empty($fields)) {
                foreach ($fields as $column) {
                    $changeLog->insertLog($table, $column, $row->{$primaryKey}, $row->{$column}, null, $event, $this->userID);
                }
            } else {
                $changeLog->insertLog($table, $row->column, $row->record_id, $row->old_value, $row->new_value, $event, $this->userID);
            }
        }
    }

    protected function respondService(bool $success, $message)
    {
        return ['status' => $success, 'message' => $message];
    }
}
