<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Employee;
use App\Models\M_User;
use App\Models\M_Role;
use App\Models\M_Branch;
use App\Models\M_BranchAccess;
use App\Models\M_DivAccess;
use App\Models\M_Division;
use App\Models\M_EmpDelegation;
use App\Models\M_ProxySwitching;
use App\Models\M_Attendance;
use App\Models\M_UserRole;
use App\Models\M_NotificationText;
use Html2Text\Html2Text;
use App\Models\M_Absent;
use App\Models\M_ProxySpecial;
use Config\Services;

class User extends BaseController
{
	public function __construct()
	{
		$this->request = Services::request();
		$this->model = new M_User($this->request);
		$this->entity = new \App\Entities\User();
	}

	public function index()
	{
		$role = new M_Role($this->request);
		$mBranch = new M_Branch($this->request);
		$mDiv = new M_Division($this->request);
		$mEmployee = new M_Employee($this->request);

		$employee = $mEmployee->where('isactive', 'Y')->whereNotIn('md_status_id', [$this->Status_OUTSOURCING, $this->Status_RESIGN])
			->orderBy('value', 'ASC')
			->findAll();

		$emp = [];

		foreach ($employee as $val) {
			$emp[] = ['md_employee_id' => $val->md_employee_id, 'value' => $val->value];
		}

		$data = [
			'role'		=> $role->where('isactive', 'Y')
				->orderBy('name', 'ASC')
				->findAll(),
			'branch'      => $mBranch->where('isactive', 'Y')
				->orderBy('name', 'ASC')
				->findAll(),
			'division'    => $mDiv->where('isactive', 'Y')
				->orderBy('name', 'ASC')
				->findAll(),
			'employee'    => $emp
		];

		return $this->template->render('backend/configuration/user/v_user', $data);
	}

	public function showAll()
	{
		if ($this->request->getMethod(true) === 'POST') {
			$table = $this->model->table;
			$select = $this->model->findAll();
			$order = $this->model->column_order;
			$sort = $this->model->order;
			$search = $this->model->column_search;

			$where = [];

			//? Session user SAS 
			if ($this->access->getSessionUser() != 1)
				$where['sys_user_id <>'] = 1;

			$data = [];

			$number = $this->request->getPost('start');
			$list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, [], $where);

			foreach ($list as $value) :
				$row = [];
				$ID = $value->sys_user_id;

				$number++;

				$row[] = $ID;
				$row[] = $number;
				$row[] = $value->username;
				$row[] = $value->name;
				$row[] = $value->description;
				$row[] = $value->email;
				$row[] = active($value->isactive);
				$row[] = $this->template->tableButton($ID);
				$data[] = $row;
			endforeach;

			$result = [
				'draw'              => $this->request->getPost('draw'),
				'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, [], $where),
				'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, [], $where),
				'data'              => $data
			];

			return $this->response->setJSON($result);
		}
	}

	public function create()
	{
		if ($this->request->getMethod(true) === 'POST') {
			$mEmpDelegation = new M_EmpDelegation($this->request);
			$mEmployee = new M_Employee($this->request);
			$post = $this->request->getVar();

			try {
				$empDelegation = null;

				if (!empty($post['sys_emp_delegation_id'])) {
					$empList = explode(',', $post['sys_emp_delegation_id']);
					foreach ($empList as $val) {
						if (!empty($post['id'])) {
							$empDelegation = $mEmpDelegation->where(['md_employee_id' => $val])->whereNotIn('sys_user_id', [$post['id']])->first();
						} else {
							$empDelegation = $mEmpDelegation->where(['md_employee_id' => $val])->first();
						}

						if ($empDelegation) {
							break;
						}
					}
				}

				if ($empDelegation) {
					$employee = $mEmployee->find($empDelegation->md_employee_id);
					$response = message('success', false, "Karyawan {$employee->value} sudah ada duta lain");
				} else {
					$this->entity->fill($post);
					$response = $this->save();


					if (isset($response[0]["success"])) {
						$id = $this->getID();

						if ($this->isNew()) {
							$id = $this->insertID;
							$response[0]["primarykey"] = $id;
						}

						$response[0]["header"] = $this->getData($id);
					}
				}
			} catch (\Exception $e) {
				$response = message('error', false, $e->getMessage());
			}

			// return $this->response->setJSON($response);
			return json_encode($response);
		}
	}

	public function show($id = null)
	{
		$mEmployee = new M_Employee($this->request);
		$mBranchAcc = new M_BranchAccess($this->request);
		$mUserRole = new M_UserRole($this->request);
		$mDivAcc = new M_DivAccess($this->request);
		$mBranch = new M_Branch($this->request);
		$mDiv = new M_Division($this->request);
		$mRole = new M_Role($this->request);
		$mEmpDelegation = new M_EmpDelegation($this->request);

		if ($this->request->isAJAX()) {
			$get = $this->request->getGet();

			$result = [];

			try {
				$list = $this->model->where($this->model->primaryKey, $id)->findAll();

				if (isset($get["md_employee_id"])) {
					$list = $this->model->where("md_employee_id", $get["md_employee_id"])->findAll();
				}

				$rowRoleAcc = $mUserRole->where($this->model->primaryKey, $id)->findAll();
				$rowBranchAcc = $mBranchAcc->where($this->model->primaryKey, $id)->findAll();
				$rowDivAcc = $mDivAcc->where($this->model->primaryKey, $id)->findAll();
				$rowEmpDel = $mEmpDelegation->where($this->model->primaryKey, $id)->findAll();

				if ($rowRoleAcc) {
					$list = $this->field->setDataSelect($mUserRole->table, $list, $mRole->primaryKey, $mRole->primaryKey, $mRole->primaryKey, $rowRoleAcc);
				}

				if ($rowBranchAcc) {
					$list = $this->field->setDataSelect($mBranchAcc->table, $list, $mBranch->primaryKey, $mBranch->primaryKey, $mBranch->primaryKey, $rowBranchAcc);
				}

				if ($rowDivAcc) {
					$list = $this->field->setDataSelect($mDivAcc->table, $list, $mDiv->primaryKey, $mDiv->primaryKey, $mDiv->primaryKey, $rowDivAcc);
				}

				if ($rowEmpDel) {
					$list = $this->field->setDataSelect($mEmployee->table, $list, $mEmpDelegation->primaryKey, $mEmployee->primaryKey, $mEmployee->primaryKey, $rowEmpDel, 'md_employee_id');
				}

				if ($list) {
					if (!empty($list[0]->getEmployeeId()) && isset($get["md_employee_id"])) {
						$rowEmp = $mEmployee->find($list[0]->getEmployeeId());
						$list[0]->setEmployeeId($rowEmp->getFullName());
					}

					if (!empty($list[0]->getEmployeeId()) && !isset($get["md_employee_id"])) {
						$rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();
						$list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());
					}

					$fieldHeader = new \App\Entities\Table();
					$fieldHeader->setTitle($list[0]->getUserName());
					$fieldHeader->setTable($this->model->table);
					$fieldHeader->setField([$mRole->primaryKey, $mBranch->primaryKey, $mDiv->primaryKey, $mEmpDelegation->primaryKey]);
					$fieldHeader->setList($list);

					$result = [
						'header'    => $this->field->store($fieldHeader)
					];
				}

				$response = message('success', true, $result);
			} catch (\Exception $e) {
				$response = message('error', false, $e->getMessage());
			}

			return $this->response->setJSON($response);
		}
	}

	private function getData($id)
	{
		$mEmployee = new M_Employee($this->request);
		$get = $this->request->getGet();

		$list = $this->model->detail([], $this->model->table . '.' . $this->model->primaryKey, $id);

		if (isset($get["md_employee_id"])) {
			$list = $this->model->detail([], $this->model->table . '.md_employee_id', $get["md_employee_id"]);
		}

		$data = $list->getResult();

		if (!empty($data[0]->md_employee_id)) {
			$rowEmp = $mEmployee->find($data[0]->md_employee_id);
			$data[0]->md_employee_id = $rowEmp->getFullName();
		}

		$fieldHeader = new \App\Entities\Table();
		$fieldHeader->setTitle($data[0]->username);
		$fieldHeader->setTable($this->model->table);
		$fieldHeader->setPrimaryKey($this->model->primaryKey);
		$fieldHeader->setQuery($list);
		$fieldHeader->setList($data);

		return $this->field->store($fieldHeader);
	}

	public function destroy($id)
	{
		if ($this->request->isAJAX()) {
			try {
				$result = $this->delete($id);
				$response = message('success', true, $result);
			} catch (\Exception $e) {
				$response = message('error', false, $e->getMessage());
			}

			return $this->response->setJSON($response);
		}
	}

	public function getList()
	{
		if ($this->request->isAjax()) {
			$post = $this->request->getVar();

			$response = [];

			try {
				if (isset($post['search'])) {
					if (isset($post['sys_user_id'])) {
						$list = $this->model->where('isactive', 'Y')
							->whereNotIn('sys_user_id', $post['sys_user_id'])
							->like('name', $post['search'])
							->orderBy('name', 'ASC')
							->findAll();
					} else {
						$list = $this->model->where('isactive', 'Y')
							->like('name', $post['search'])
							->orderBy('name', 'ASC')
							->findAll();
					}
				} else if (isset($post['sys_user_id'])) {
					$list = $this->model->where('isactive', 'Y')
						->whereNotIn('sys_user_id', [$post['sys_user_id']])
						->orderBy('name', 'ASC')
						->findAll();
				} else {
					$list = $this->model->where('isactive', 'Y')
						->orderBy('name', 'ASC')
						->findAll();
				}

				foreach ($list as $key => $row) :
					$response[$key]['id'] = $row->getUserId();
					$response[$key]['text'] = $row->getName();
				endforeach;
			} catch (\Exception $e) {
				$response = message('error', false, $e->getMessage());
			}

			return $this->response->setJSON($response);
		}
	}

	public function proxyReguler()
	{
		$mAttendance = new M_Attendance($this->request);
		$mAbsent = new M_Absent($this->request);
		$mEmployee = new M_Employee($this->request);
		$mUserRole = new M_UserRole($this->request);
		$mNotifText = new M_NotificationText($this->request);
		$mProxySwitch = new M_ProxySpecial($this->request);
		$cMail = new Mail();

		$dataNotif = $mNotifText->where('name', 'Tindakan Pengalihan Approval')->first();
		$message = $dataNotif->getText();


		$hrRole = $mUserRole->where('sys_role_id', 5)->findAll();
		$listHrUser = array_column($hrRole, "sys_user_id");
		$hrUser = $this->model->whereIn('sys_user_id', $listHrUser)->findAll();

		$where = "isactive = 'Y'";
		// 100002 GM, 100003 Manager
		$where .= " AND md_levelling_id IN (100002, 100003)";
		$employee = $mEmployee->where($where)->findAll();

		if ($employee) {
			$today = date('Y-m-d');
			foreach ($employee as $emp) {

				$where = "md_employee.md_employee_id = {$emp->md_employee_id}";
				$where .= " AND v_attendance.date = '{$today}'";
				$empAttendance = $mAttendance->getAttendance($where)->getRow();

				$where = "employee_id = {$emp->md_employee_id}";
				$where .= " AND date = '{$today}'";
				$where .= " AND isagree IN ('Y', 'M', 'H', 'S')";
				$submission = $mAbsent->getAllSubmission($where)->getRow();

				$user = $this->model->where(['md_employee_id' => $emp->md_employee_id, 'isactive' => 'Y'])->first();

				if ((!$empAttendance && !$submission) && $user) {
					//TODO : Get Superior data and do absent or submission check on superior
					$superiorEmp = $mEmployee->where(['md_employee_id' => $emp->superior_id, 'isactive' => 'Y'])->first();
					$superiorAtten = $superiorEmp ? $mAttendance->getAttendance("md_employee.md_employee_id = {$superiorEmp->md_employee_id} AND v_attendance.date = '{$today}'")->getRow() : null;
					$superiorUser = $superiorEmp ? $this->model->where(['md_employee_id' => $superiorEmp->md_employee_id, 'isactive' => 'Y'])->first() : null;

					$email = array_column($hrUser, "email");

					if ($superiorAtten && $superiorUser) {
						//TODO : Get All User Role Contains W_App
						$where = "sys_user.sys_user_id = {$user->sys_user_id}";
						$where .= " AND sr.name like 'W_App%'";
						$userRole = $this->model->detail([], null, $where)->getResult();

						//TODO : Switching Proxy 
						foreach ($userRole as $role) {
							$mProxySwitch->insertProxy($user->sys_user_id, $superiorUser->sys_user_id, $role->role);
						}

						// TODO : Set Email Recipient and get Data Notif Pengalihan Approval
						$dataNotif = $mNotifText->where('name', 'Pengalihan Approval')->first();
						$email = [$user->email, $superiorUser->email];
					}
					$message = $dataNotif->getText();
					$message = str_replace(['(Var1)', '(Var2)'], [$user->username, $today], $message);

					//TODO : Filter the data use array_unique remove duplicate value then array_filter and exclude null value 
					$recipients = array_values(array_unique(array_filter($email)));

					$subject = $dataNotif->getSubject();
					$message = new Html2Text($message);
					$message = $message->getText();

					//TODO : Send Email
					foreach ($recipients as $email) {
						$cMail->sendEmail($email, $subject, $message, null, "SAS HR");
					}
				}
			}
		}
	}
}
