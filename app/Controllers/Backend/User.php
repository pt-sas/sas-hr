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
use App\Models\M_Levelling;
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
		$mLevelling = new M_Levelling($this->request);

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
				$rowLvl = $mLevelling->where("md_levelling_id", $list[0]->getLevellingId())->first();

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
					$empArr = array_column($rowEmpDel, 'md_employee_id');
					$empList = $mEmployee->whereIn('md_employee_id', $empArr)->findAll();
					$list = $this->field->setDataSelect($mEmployee->table, $list, $mEmpDelegation->primaryKey, $mEmployee->primaryKey, $mEmployee->primaryKey, $empList, 'md_employee_id', 'value');
				}

				if ($rowLvl) {
					$list = $this->field->setDataSelect($mLevelling->table, $list, $mLevelling->primaryKey, $rowLvl->getLevellingId(), $rowLvl->getName());
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
		$mProxySwitch = new M_ProxySwitching($this->request);
		$cMail = new Mail();
		$today = date('Y-m-d');

		// TODO : Get Manager and General Manager
		$employees = $mEmployee->where("isactive = 'Y' AND md_levelling_id IN (100002, 100003)")->findAll();
		if (!$employees) return;

		// TODO : Get HR Role and Employee, then get Email Adrress each of HR Employee
		$hrRole = $mUserRole->where('sys_role_id', 5)->findAll();
		$listHrUser = array_column($hrRole, "sys_user_id");
		$hrUsers = $this->model->whereIn('sys_user_id', $listHrUser)->findAll();
		$hrEmails = array_column($hrUsers, "email");

		// TODO : Preparing notification data
		$notifTindakan = $mNotifText->where('name', 'Tindakan Pengalihan Approval')->first();
		$notifPengalihan = $mNotifText->where('name', 'Pengalihan Approval')->first();

		// TODO : Get all attendance data for today and make it assosiatif multidimensional array
		$attendanceData = $mAttendance->getAttendance("v_attendance.date = '{$today}'")->getResult();
		$attendanceMap = [];
		foreach ($attendanceData as $att) {
			$attendanceMap[$att->md_employee_id] = $att;
		}

		// TODO : Get all submission data for today and make it assosiatif multidimensional array
		$submissionData = $mAbsent->getAllSubmission("date = '{$today}' AND isagree IN ('Y', 'M', 'H', 'S')")->getResult();
		$submissionMap = [];
		foreach ($submissionData as $sub) {
			$submissionMap[$sub->employee_id] = $sub;
		}

		// TODO : Get All User List and make it assosiatif multidimensional array
		$allUsers = $this->model->where('isactive', 'Y')->findAll();
		$userMap = [];
		foreach ($allUsers as $u) {
			$userMap[$u->md_employee_id] = $u;
		}

		// TODO : Process each employee
		foreach ($employees as $emp) {
			// TODO : Continoue loop if attendance or submission data is exist
			if (isset($attendanceMap[$emp->md_employee_id]) || isset($submissionMap[$emp->md_employee_id])) {
				continue;
			}

			// TODO : Continoue loop if user data is not exist
			$user = $userMap[$emp->md_employee_id] ?? null;
			if (!$user) continue;

			// TODO : Get User Approval Roles
			$userRoleWhere = "sys_user.sys_user_id = {$user->sys_user_id} AND sr.name LIKE 'W_App%'";
			$userRoles = $this->model->detail([], null, $userRoleWhere)->getResult();
			if (!$userRoles) continue;

			$proxySuccess = false;
			$proxyNames = [];
			$proxyEmails = [];

			if ($emp->md_levelling_id == 100002) {
				// TODO : Handle GM Level
				$otherGMs = $mEmployee->where([
					'md_levelling_id' => 100002,
					'isactive' => 'Y',
					'md_employee_id != ' => $emp->md_employee_id
				])->findAll();

				foreach ($otherGMs as $gm) {
					// TODO : Check if GM has attendance and user account
					if (!isset($attendanceMap[$gm->md_employee_id]) || !isset($userMap[$gm->md_employee_id])) {
						continue;
					}

					$gmUser = $userMap[$gm->md_employee_id];
					$roleSuccess = false;

					// TODO : Execute proxy switching for all roles
					foreach ($userRoles as $role) {
						if ($mProxySwitch->insertProxy($user->sys_user_id, $gmUser->sys_user_id, $role->role)) {
							$roleSuccess = true;
						}
					}

					if ($roleSuccess) {
						$proxySuccess = true;
						$proxyNames[] = $gm->fullname;
						$proxyEmails[] = $gmUser->email;
					}
				}

				$proxyNames = implode(', ', $proxyNames);
			} else {
				// TODO Handle Manager Level
				if (!$emp->superior_id || !isset($userMap[$emp->superior_id])) {
					continue;
				}

				$superior = $mEmployee->where(['md_employee_id' => $emp->superior_id, 'isactive' => 'Y'])->first();
				if (!$superior || !isset($attendanceMap[$superior->md_employee_id])) {
					continue;
				}

				$superiorUser = $userMap[$superior->md_employee_id];
				$roleSuccess = false;

				// TODO : Execute proxy switching for all roles
				foreach ($userRoles as $role) {
					if ($mProxySwitch->insertProxy($user->sys_user_id, $superiorUser->sys_user_id, $role->role)) {
						$roleSuccess = true;
					}
				}

				if ($roleSuccess) {
					$proxySuccess = true;
					$proxyNames = $superior->fullname;
					$proxyEmails = [$user->email, $superiorUser->email];
				}
			}

			// TODO : Send notifications
			if ($proxySuccess && $notifPengalihan) {
				// TODO : Send successful proxy notification
				$message = str_replace(['(Var1)', '(Var2)'], [$proxyNames, $today], $notifPengalihan->getText());
				$subject = $notifPengalihan->getSubject();
				$recipients = array_values(array_unique(array_filter(array_merge($proxyEmails, $hrEmails))));

				$plainMessage = (new Html2Text($message))->getText();
				foreach ($recipients as $email) {
					$cMail->sendEmail($email, $subject, $plainMessage, null, "SAS HR");
				}
			} elseif (!$proxySuccess && $notifTindakan) {
				// TODO : Send action required notification to HR
				$message = str_replace(['(Var1)', '(Var2)'], [$emp->fullname, $today], $notifTindakan->getText());
				$subject = $notifTindakan->getSubject();
				$plainMessage = (new Html2Text($message))->getText();

				foreach ($hrEmails as $email) {
					$cMail->sendEmail($email, $subject, $plainMessage, null, "SAS HR");
				}
			}
		}
	}

	public function sendEmailWhenDelegationAbsent()
	{
		$mEmpDelegation = new M_EmpDelegation($this->request);
		$mAttendance = new M_Attendance($this->request);
		$mNotifText = new M_NotificationText($this->request);
		$mEmployee = new M_Employee($this->request);
		$cMail = new Mail();

		$today = date('Y-m-d');
		$strDate = date('d/M/Y', strtotime($today));
		$userList = $mEmpDelegation->select('sys_user_id')->distinct()->findAll();

		$dataNotif = $mNotifText->where('name', 'Duta Tidak Hadir')->first();

		foreach ($userList as $value) {
			$user = $this->model->where('sys_user_id', $value->sys_user_id)->first();

			if ($user->md_employee_id) {
				$where = "md_employee.md_employee_id = {$user->md_employee_id}";
				$where .= " AND v_attendance.date = '{$today}'";
				$empAttendance = $mAttendance->getAttendance($where)->getRow();

				if (!$empAttendance) {
					$managerID = $mEmployee->getEmployeeManagerID($user->md_employee_id);
					$emailManager = $this->model->select('email')->where(['md_employee_id' => $managerID, 'isactive' => 'Y'])->first();

					$message = $dataNotif->getText();
					$message = str_replace(['(Var1)', '(Var2)'], [$user->username, $strDate], $message);

					$subject = $dataNotif->getSubject();
					$message = new Html2Text($message);
					$message = $message->getText();

					if ($emailManager->email) {
						$cMail->sendEmail($emailManager->email, $subject, $message, null, "SAS HR");
					}
				}
			}
		}
	}
}