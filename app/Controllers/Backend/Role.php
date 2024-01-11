<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Role;
use App\Models\M_Menu;
use App\Models\M_Submenu;
use App\Models\M_AccessMenu;
use App\Models\M_DocAction;
use App\Models\M_User;
use App\Models\M_Reference;
use Config\Services;

class Role extends BaseController
{
	public function __construct()
	{
		$this->request = Services::request();
		$this->model = new M_Role($this->request);
		$this->entity = new \App\Entities\Role();
		$this->modelDetail = new M_DocAction($this->request);
	}

	public function index()
	{
		$menu = new M_Menu($this->request);
		$submenu = new M_Submenu($this->request);

		$data = [
			'menu'		=> $menu->where('isactive', 'Y')
				->orderBy('name', 'ASC')
				->findAll(),
			'submenu'	=> $submenu->where('isactive', 'Y')
				->orderBy('name', 'ASC')
				->findAll()
		];

		return $this->template->render('backend/configuration/role/v_role', $data);
	}

	public function showAll()
	{
		if ($this->request->getMethod(true) === 'POST') {
			$table = $this->model->table;
			$select = $this->model->findAll();
			$order = $this->model->column_order;
			$sort = $this->model->order;
			$search = $this->model->column_search;

			$data = [];

			$number = $this->request->getPost('start');
			$list = $this->datatable->getDatatables($table, $select, $order, $sort, $search);

			foreach ($list as $value) :
				$row = [];
				$ID = $value->sys_role_id;

				$number++;

				$row[] = $ID;
				$row[] = $number;
				$row[] = $value->name;
				$row[] = $value->description;
				$row[] = active($value->ismanual);
				$row[] = active($value->iscanexport);
				$row[] = active($value->iscanreport);
				$row[] = active($value->isallowmultipleprint);
				$row[] = active($value->isactive);
				$row[] = $this->template->tableButton($ID);
				$data[] = $row;
			endforeach;

			$result = [
				'draw'              => $this->request->getPost('draw'),
				'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search),
				'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search),
				'data'              => $data
			];

			return $this->response->setJSON($result);
		}
	}

	public function create()
	{
		if ($this->request->getMethod(true) === 'POST') {
			$post = $this->request->getVar();

			$table = json_decode($post['table']);

			//! Mandatory property for detail validation
			$post['line'] = countLine($table);
			$post['detail'] = [
				'table' => arrTableLine($table)
			];

			try {
				$this->entity->fill($post);

				if (!$this->validation->run($post, 'role')) {
					$response =	$this->field->errorValidation($this->model->table, $post);
				} else {
					$response = $this->save();
				}
			} catch (\Exception $e) {
				$response = message('error', false, $e->getMessage());
			}

			return $this->response->setJSON($response);
		}
	}

	public function show($id)
	{
		$acessMenu = new M_AccessMenu($this->request);

		if ($this->request->isAJAX()) {
			try {
				$list = $this->model->where($this->model->primaryKey, $id)->findAll();
				$line = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();
				$accRoles = $acessMenu->where($this->model->primaryKey, $id)->findAll();

				$fieldHeader = new \App\Entities\Table();
				$fieldHeader->setTitle($list[0]->getName());
				$fieldHeader->setTable($this->model->table);
				$fieldHeader->setList($list);

				$fieldRole = new \App\Entities\Table();
				$fieldRole->setTable($acessMenu->table);
				$fieldRole->setList($accRoles);
				$fieldRole->setQuery("table");

				$result = [
					'header'	=> $this->field->store($fieldHeader),
					'line'    	=> $this->tableLine('edit', $line),
					'role'		=> $this->field->store($fieldRole)
				];

				$response = message('success', true, $result);
			} catch (\Exception $e) {
				$response = message('error', false, $e->getMessage());
			}

			return $this->response->setJSON($response);
		}
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

	public function getUserRoleName()
	{
		$user = new M_User($this->request);

		if ($this->request->isAJAX()) {
			$post = $this->request->getVar();

			$response = true;

			try {
				$role = $user->detail([
					'sr.isactive'           => $this->access->active(),
					'sys_user.sys_user_id'  => $this->access->getSessionUser(),
					'sr.name'               => $post['role_name']
				])->getRow();

				if (!$role)
					$response = false;
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
					$response[$key]['id'] = $row->getRoleId();
					$response[$key]['text'] = $row->getName();
				endforeach;
			} catch (\Exception $e) {
				$response = message('error', false, $e->getMessage());
			}

			return $this->response->setJSON($response);
		}
	}

	public function tableLine($set = null, $detail = [])
	{
		$mRef = new M_Reference($this->request);
		$mSub = new M_Submenu($this->request);
		$mDocAction = new M_DocAction($this->request);

		$table = [];

		$fieldMenu = new \App\Entities\TableLine();
		$fieldMenu->setName("menu");
		$fieldMenu->setType("select");
		$fieldMenu->setClass("select2");
		$fieldMenu->setField([
			"id"    => "url",
			"text"  => "name"
		]);

		$menuList = $mSub->where([
			'sys_menu_id'	=> 3,
			'isactive'		=> 'Y'
		])->orderBy('name', 'ASC')->findAll();

		$fieldMenu->setList($menuList);
		$fieldMenu->setLength(170);

		$fieldRefList = new \App\Entities\TableLine();
		$fieldRefList->setName("ref_list");
		$fieldRefList->setType("select");
		$fieldRefList->setClass("select2");
		$fieldRefList->setField([
			"id"    => "value",
			"text"  => "name"
		]);

		$refList = $mRef->findBy([
			'sys_reference.name'              => '_DocAction',
			'sys_reference.isactive'          => 'Y',
			'sys_ref_detail.isactive'         => 'Y',
		], null, [
			'field'     => 'sys_ref_detail.name',
			'option'    => 'ASC'
		])->getResult();

		$fieldRefList->setList($refList);
		$fieldRefList->setLength(300);

		$btnDelete = new \App\Entities\TableLine();
		$btnDelete->setName($mDocAction->primaryKey);
		$btnDelete->setType("button");
		$btnDelete->setClass("delete");

		//? Create
		if (empty($set)) {
			$table = [
				$this->field->fieldTable($fieldMenu),
				$this->field->fieldTable($fieldRefList),
				$this->field->fieldTable($btnDelete)
			];
		}

		//? Update
		if (!empty($set) && count($detail) > 0) {
			foreach ($detail as $row) :
				$fieldMenu->setValue($row->getMenu());
				$fieldRefList->setValue($row->getRefList());
				$btnDelete->setValue($row->getDocActionId());

				$table[] = [
					$this->field->fieldTable($fieldMenu),
					$this->field->fieldTable($fieldRefList),
					$this->field->fieldTable($btnDelete)
				];
			endforeach;
		}

		return json_encode($table);
	}

	public function destroyLine($id)
	{
		if ($this->request->isAJAX()) {
			try {
				$result = $this->modelDetail->delete($id);
				$response = message('success', true, $result);
			} catch (\Exception $e) {
				$response = message('error', false, $e->getMessage());
			}

			return $this->response->setJSON($response);
		}
	}
}
