<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\Template;
use App\Libraries\Field;
use App\Libraries\Access;
use App\Models\M_Datatable;
use App\Models\M_ChangeLog;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Config\Services;
use stdClass;
use CodeIgniter\Exceptions\ModelException;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 * 
 * 
 * This have been custom for support insert|update dynamic with header and line
 * @author Oki Permana
 */

class BaseController extends Controller
{
	/**
	 * An array of helpers to be loaded automatically upon
	 * class instantiation. These helpers will be available
	 * to all other controllers that extend BaseController.
	 *
	 * @var array
	 */
	protected $helpers = ['action_helper', 'url', 'date_helper'];

	protected $session;
	protected $language;
	protected $validation;
	protected $model;
	protected $modelDetail;
	protected $entity;
	protected $email;

	//TODO: LIBRARY
	protected $template;
	protected $field;
	protected $access;

	//TODO: EVENT
	/** Insert = I */
	protected $EVENTCHANGELOG_Insert = "I";
	/** Update = U */
	protected $EVENTCHANGELOG_Update = "U";
	/** Delete = D */
	protected $EVENTCHANGELOG_Delete = "D";
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
	/** Form Kelengkapan Absent */
	protected $Form_Kelengkapan_Absent = 'FK';
	/** Form Absent */
	protected $Form_Absent = 'FA';
	/** Status HIDUP*/
	protected $Status_Hidup = 'HIDUP';
	/** Status MENINGGAL */
	protected $Status_Meninggal = 'MENINGGAL';
	/** Status PERMANENT*/
	protected $Status_PERMANENT = 100001;
	/** Status PROBATION */
	protected $Status_PROBATION = 100002;
	/** Status OUTSOURCING*/
	protected $Status_OUTSOURCING = 100003;
	/** Status RESIGN */
	protected $Status_RESIGN = 100004;

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
	 * Button Print
	 *
	 * @var string
	 */
	protected $BTN_Print = 'PRINT';

	/**
	 * PATH Folder for Pengajuan upload data
	 *
	 * @var directory
	 */
	protected $PATH_Pengajuan = "pengajuan";

	/**
	 * Notification Text Approved
	 *
	 * @var int
	 */
	protected $Notif_Approved = 100002;

	/**
	 * Notification Create New Doc
	 *
	 * @var int
	 */
	protected $Notif_CreateDoc = 100004;

	/**
	 * Notification Text Not Approved
	 *
	 * @var int
	 */
	protected $Notif_NotApproved = 100003;

	/**
	 * Constructor.
	 *
	 * @param RequestInterface  $request
	 * @param ResponseInterface $response
	 * @param LoggerInterface   $logger
	 */
	public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
	{
		// Do Not Edit This Line
		parent::initController($request, $response, $logger);

		//--------------------------------------------------------------------
		// Preload any models, libraries, etc, here.
		//--------------------------------------------------------------------
		// E.g.: $this->session = \Config\Services::session();

		//* Load Service
		$this->session = Services::session();
		$this->language = Services::language();
		$this->validation = Services::validation();

		//* Load Libraries 
		$this->template = new Template();
		$this->field = new Field();
		$this->access = new Access();

		//* Load Models
		$this->datatable = new M_Datatable($request);

		//? Check language 
		if (!empty($this->session->lang))
			$this->session->lang;
		else
			$this->session->lang = 'en';

		//TODO: Setup language 
		$this->language->setLocale($this->session->lang);
	}

	/**
	 * Inserts data into the database
	 * 
	 */
	public function save()
	{
		$changeLog = new M_ChangeLog($this->request);

		$post = $this->request->getVar();

		//* Object class Old Value 
		$oldV = new stdClass();
		//* Object class New Value 
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

		$data = $this->entity;

		$data = $this->transformDataToArray($data, 'insert');

		// Must be called first so we don't
		$data = $this->doStrip($data);

		$this->model->db->transBegin();

		try {
			$fields = $this->model->db->getFieldData($modelTable);

			foreach ($data as $key => $val) :
				if ($newRecord) {
					$newV->{$key} = $val;

					if ($this->createdByField && !array_key_exists($this->createdByField, $data))
						$newV->{$this->createdByField} = $this->access->getSessionUser();

					if ($this->updatedByField && !array_key_exists($this->updatedByField, $data))
						$newV->{$this->updatedByField} = $this->access->getSessionUser();
				} else {
					//TODO: Check old data 
					$row = $this->model->find($this->getID());

					//TODO: Convert value type integer is null to Zero
					foreach ($fields as $field) :
						if ($field->type === 'int' && $field->name === $key && $data[$key] === "")
							$data[$key] = 0;
					endforeach;

					if (is_null($row->{$key}))
						$row->{$key} = "";

					if ((gettype($data[$key]) === 'integer' && $data[$key] != $row->{$key}) ||
						(gettype($data[$key]) === 'string' && $data[$key] !== $row->{$key})
					) {
						//* Old Value 
						$oldV->{$key} = $row->{$key};

						//* New Value 
						$newV->{$key} = $val;

						if ($this->updatedByField && !array_key_exists($this->updatedByField, $data))
							$newV->{$this->updatedByField} = $this->access->getSessionUser();
						else if ($this->updatedByField && array_key_exists($this->updatedByField, $data))
							$newV->{$this->updatedByField} = $row->{$this->updatedByField};

						$isChange = true;
					}

					if ((!empty($beforeUpdate) || !empty($afterUpdate)) && !$isChange) {
						if ($this->updatedByField && !array_key_exists($this->updatedByField, $data))
							$newV->{$this->updatedByField} = $this->access->getSessionUser();
						else if ($this->updatedByField && array_key_exists($this->updatedByField, $data))
							$newV->{$this->updatedByField} = $row->{$this->updatedByField};
					}

					$newV->{$this->primaryKey} = $this->getID();
				}
			endforeach;

			//? Exist Property Table Line
			if (isset($post['table']) && json_decode($post['table'])) {
				$arrLine = json_decode($post['table']);

				//! Must be called saveBatch
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
					//TODO: Insert Change Log Header
					$changeLog->insertLog($modelTable, $this->model->primaryKey, $this->insertID, null, $this->insertID, $this->EVENTCHANGELOG_Insert);

					$this->message = notification("insert");
				} else {
					$arrNew = $this->transformDataToArray($newV, 'insert');

					//* Remove element from array allowedFields
					$arrNew = $this->doStrip($arrNew);

					//TODO: Insert Change Log
					foreach ($arrNew as $key => $val) :
						if (!in_array($key, $this->allowedFields)) {
							//* Old Value 
							$oldV->{$key} = $row->{$key};

							//* New Value 
							$newV->{$key} = $val;

							$changeLog->insertLog($modelTable, $key, $this->getID(), $oldV->{$key}, $newV->{$key}, $this->EVENTCHANGELOG_Update);
						}
					endforeach;

					if (isset($newV->docstatus) && $newV->docstatus !== $this->DOCSTATUS_Invalid)
						$this->message = true;
					else if (isset($newV->docstatus) && $newV->docstatus === $this->DOCSTATUS_Invalid)
						$this->message = 'Document cannot be processed';
					else
						$this->message = notification("updated");
				}

				$ok = message('success', true, $this->message);
			} else {
				$this->message = 'No data to Insert';
				$ok = message('error', false, $this->message);
			}

			$this->model->db->transCommit();
		} catch (\Exception $e) {
			$this->model->db->transRollback();
			throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
		}

		return $ok;
	}

	/**
	 * Insert or update a lot of data
	 *
	 * @param string $event Type of data (insert|update)
	 * @param [type] $model class
	 * @param object $obj object
	 * @param array $data Data
	 * @return boolean
	 */
	protected function saveBatch(string $event, $model, object $obj, array $data)
	{
		$changeLog = new M_ChangeLog($this->request);

		$result = false;

		if (!is_array($data))
			return false;

		if (empty($data))
			return false;

		if (!empty($this->allowedFields))
			array_push($this->allowedFields, $model->primaryKey);

		if (is_array($data)) {
			//* Convert object to array 
			$arrDataHeader = $this->transformDataToArray($obj, 'insert');

			//? Check function is exists 
			if (method_exists($model, 'doChangeValueField'))
				$data = $model->doChangeValueField($data, $this->getID(), $obj);

			//* Must be called first so we don't
			$data = $this->doStripLine($data);

			//* Split data
			$data = $this->doSplitData($data, $model->primaryKey);

			/**
			 * TODO: Insert Data
			 */
			if ($event === 'insert' || isset($data['insert'])) {
				$dataInsert = $data['insert'];

				//TODO: Insert header data 
				if (empty($this->getID())) {
					$result = $this->model->save($obj);
					$this->insertID = $this->model->getInsertID();
				} else {
					if (count($arrDataHeader) > 1) //* Update data
						$result = $this->model->save($obj);

					$this->insertID = $this->getID();
				}

				//* Set Foreign Key Field from header table 
				$foreignKey = $this->primaryKey;
				$dataInsert = $this->doSetField($foreignKey, $this->insertID, $dataInsert);

				//* Set Created_By Field 
				$dataInsert = $this->doSetField($this->createdByField, $this->access->getSessionUser(), $dataInsert);

				//* Set Updated_By Field 
				$dataInsert = $this->doSetField($this->updatedByField, $this->access->getSessionUser(), $dataInsert);

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

					//TODO: Insert Change Log
					foreach ($newData as $new) :
						$changeLog->insertLog($model->table, $model->primaryKey, $new->{$model->primaryKey}, null, $new->{$model->primaryKey}, $this->EVENTCHANGELOG_Insert);
					endforeach;
				}
			}

			/**
			 * TODO: Update Data
			 */
			if ($event === 'update') {
				$dataUpdate = [];

				//? Check property update 
				if (isset($data['update'])) {
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
						$dataUpdate = $this->doSetField($this->updatedByField, $this->access->getSessionUser(), $dataUpdate);

						//TODO: Populate data old value and new value 
						$arrChangeData = [];
						foreach ($dataUpdate as $new) :
							$old = $model->find($new[$model->primaryKey]);

							$new = (array) $new;
							foreach (array_keys($new) as $column) :
								if (!empty($this->allowedFields) && !in_array($column, $this->allowedFields))
									$arrChangeData[] = [
										'table'		=> $model->table,
										'column'	=> $column,
										'record_id'	=> $new[$model->primaryKey],
										'old_value'	=> $old->{$column},
										'new_value'	=> $new[$column]
									];
							endforeach;
						endforeach;

						//TODO: Update line data 
						$result = $model->builder->updateBatch($dataUpdate, $model->primaryKey);

						if ($result > 0) {
							//TODO: Insert Change Log 
							foreach ($arrChangeData as $value) :
								$changeLog->insertLog($value['table'], $value['column'], $value['record_id'], $value['old_value'], $value['new_value'], $this->EVENTCHANGELOG_Update);
							endforeach;
						}
					}
				}
			}

			if (isset($data['delete'])) {
				$dataDelete = $data['delete'];

				//TODO: Update line data 
				$result = $model->delete($dataDelete['id']);

				if ($result) {
					$fields = $model->db->getFieldNames($model->table);

					//TODO: Insert Change Log 
					foreach ($dataDelete['data'] as $value) :
						foreach ($fields as $column) :
							$changeLog->insertLog($model->table, $column, $value->{$model->primaryKey}, $value->{$column}, null, $this->EVENTCHANGELOG_Delete);
						endforeach;
					endforeach;

					$result = 1;
				} else {
					$result = 0;
				}
			}

			return $result > 0 ? true : false;
		}

		return $result;
	}

	public function delete(int $id): bool
	{
		$changeLog = new M_ChangeLog($this->request);

		$row = $this->model->find($id);
		$result = $this->model->delete($id);

		if ($result) {
			$this->primaryKey = $this->model->primaryKey;
			$modelTable = $this->model->table;

			$fields = $this->model->db->getFieldNames($modelTable);

			//TODO: Insert Change Log 
			foreach ($fields as $column) :
				$changeLog->insertLog($modelTable, $column, $row->{$this->primaryKey}, $row->{$column}, null, $this->EVENTCHANGELOG_Delete);
			endforeach;

			if ($this->modelDetail) {
				$primaryKey = $this->modelDetail->primaryKey;
				$detailTable = $this->modelDetail->table;

				$line = $this->modelDetail->where($this->primaryKey, $id)->findAll();

				$this->modelDetail->where($this->primaryKey, $id)->delete();

				$fields = $this->modelDetail->db->getFieldNames($detailTable);

				//TODO: Insert Change Log 
				foreach ($line as $value) :
					foreach ($fields as $column) :
						$changeLog->insertLog($detailTable, $column, $value->{$primaryKey}, $value->{$column}, null, $this->EVENTCHANGELOG_Delete);
					endforeach;
				endforeach;
			}
		}

		return $result;
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
		$post = $this->request->getVar();

		//? Check property id or object primaryKey
		if (((isset($post['id']) && isset($this->entity->{$this->primaryKey})) ||
				isset($post['id']) ||
				isset($this->entity->{$this->primaryKey}))
			&& !$this->isNewRecord
		) {
			return false;
		}

		return true;
	}

	/**
	 *  Return Single Key Record ID
	 *  @return ID or 0
	 */
	protected function getID()
	{
		//* Get Request POST From View 
		$post = $this->request->getVar();

		//? Check property berdasarkan id dan entity primaryKey
		if ((isset($post['id']) && isset($this->entity->{$this->primaryKey}) ||
				isset($this->entity->{$this->primaryKey}))
			&& !$this->isNewRecord
		) {
			return $this->entity->{$this->primaryKey};
		} else if (isset($post['id']) && !$this->isNewRecord) { //? Property id
			return $post['id'];
		}

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

		foreach ($data as $value) :
			if (empty($value[$primaryKey])) {
				unset($value[$primaryKey]);

				$result['insert'][] = $value;
			} else {
				$result['update'][] = $value;
				$id[] = $value[$primaryKey];
			}
		endforeach;

		if (!empty($this->getID())) {
			$foreignKey = $this->primaryKey;

			if ($id)
				$list = $this->modelDetail->where($foreignKey, $this->getID())
					->whereNotIn($primaryKey, $id)
					->findAll();
			else
				$list = $this->modelDetail->where($foreignKey, $this->getID())->findAll();

			foreach ($list as $row) :
				$result['delete']['id'][] = $row->{$primaryKey};
				$result['delete']['data'][] = $row;
			endforeach;
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

	public function setField($field, $value, array $data, $aField = null): array
	{
		$result = [];

		foreach ($data as $key => $row) :
			if (gettype($value) !== "array") {
				if (isset($row->{$field}))
					$row->{$field} = $value;

				if ($field === $value && !is_null($aField))
					$row->{$field} = $row->{$aField};
			} else {
				foreach ($value as $key2 => $val) :
					if ($key == $key2)
						$row->{$field} = $val->{$aField};
				endforeach;
			}

			$result[] = $row;
		endforeach;

		return $result;
	}
}
