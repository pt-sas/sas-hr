<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Employee;
use App\Models\M_User;
use App\Models\M_Role;
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

		$data = [
			'role'		=> $role->where('isactive', 'Y')
				->orderBy('name', 'ASC')
				->findAll()
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
			$post = $this->request->getVar();

			try {
				$this->entity->fill($post);

				// 	// if (!$this->validation->run($post, 'user')) {
				// 	// 	$response =	$this->field->errorValidation($this->model->table, $post);
				// 	// } else {
				$response = $this->save();

				if (isset($response[0]["success"])) {
					$id = $this->getID();

					if ($this->isNew()) {
						$id = $this->insertID;
						$response[0]["primarykey"] = $id;
					}

					$response[0]["header"] = $this->getData($id);
				}
				// 	// }
			} catch (\Exception $e) {
				$response = message('error', false, $e->getMessage());
			}

			// return $this->response->setJSON($response);
			return json_encode($response);
		}
	}

	public function show($id = null)
	{
		if ($this->request->isAJAX()) {
			$get = $this->request->getGet();

			$result = [];

			try {
				$mEmployee = new M_Employee($this->request);
				$get = $this->request->getGet();

				$list = $this->model->detail([], $this->model->table . '.' . $this->model->primaryKey, $id);

				if (isset($get["md_employee_id"])) {
					$list = $this->model->detail([], $this->model->table . '.md_employee_id', $get["md_employee_id"]);
				}

				$data = $list->getResult();

				if ($data) {

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
					$list = $this->model->where('isactive', 'Y')
						->like('name', $post['search'])
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
}
