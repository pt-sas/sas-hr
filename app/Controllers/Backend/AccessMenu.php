<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Role;
use App\Models\M_Menu;
use App\Models\M_Submenu;
use App\Models\M_WActivity;
use Config\Services;

class AccessMenu extends BaseController
{
	protected $request;

	public function __construct()
	{
		$this->request = Services::request();
	}

	public function index()
	{
		// $mActivity = new M_WActivity($this->request);
		// $cActivity = new WActivity();

		// $where = 'ADDDATE(sys_wfactivity.created_at, INTERVAL 3 DAY) <= NOW()';

		// $list = $mActivity->getActivity(null, $where);
		// if ($list) {
		// 	foreach ($list as $row) {
		// 		// log_message("warning", $this->access->getSessionUser());
		// 		$result = $cActivity->setActivity($row->sys_wfactivity_id, $row->sys_wfscenario_id, $row->sys_wfresponsible_id, 1, $this->DOCSTATUS_Aborted, true, "Not Approved By System", $row->table, $row->record_id, $row->menu);
		// 	}
		log_message("error", "Test Error Message");

		log_message("info", "Test Info Message");
		// }
		// dd($list);
	}

	public function getAccess()
	{
		$menu = new M_Menu($this->request);
		$submenu = new M_Submenu($this->request);
		$role = new M_Role($this->request);

		if ($this->request->getMethod(true) === 'POST') {
			$post = $this->request->getVar();

			try {
				if (isset($post)) {
					// Check uri segment from submenu
					$sub = $submenu->detail('sys_submenu.url', $post['last_url'])->getRow();

					// Check uri segment from main menu
					$parent = $menu->where('url', $post['last_url'])->first();

					if (isset($sub)) {
						$access = $role->detail([
							'am.sys_submenu_id'		=> $sub->sys_submenu_id,
							'am.sys_role_id'		=> session()->get('sys_role_id')
						])->getRow();

						if ($post['action'] === 'view')
							$value = $access->isview;

						if ($post['action'] === 'create')
							$value = $access->iscreate;

						if ($post['action'] === 'update')
							$value = $access->isupdate;

						if ($post['action'] === 'delete')
							$value = $access->isdelete;
					} else if (isset($parent)) {
						$access = $role->detail([
							'am.sys_menu_id'		=> $parent->getMenuId(),
							'am.sys_role_id'		=> $this->session->get('sys_role_id')
						])->getRow();

						if ($post['action'] === 'view')
							$value = $access->isview;

						if ($post['action'] === 'create')
							$value = $access->iscreate;

						if ($post['action'] === 'update')
							$value = $access->isupdate;

						if ($post['action'] === 'delete')
							$value = $access->isdelete;
					}

					$response = message('success', true, $value);
				}
			} catch (\Exception $e) {
				$response = message('error', false, $e->getMessage());
			}

			return $this->response->setJSON($response);
		}
	}
}
