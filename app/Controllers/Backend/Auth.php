<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_User;
use Config\Services;

class Auth extends BaseController
{
	public function __construct()
	{
		$this->request = Services::request();
		$this->validation = Services::validation();
		$this->model = new M_User($this->request);
		$this->entity = new \App\Entities\User();
	}

	public function index()
	{
		if ($this->session->get('logged_in')) {
			return redirect()->to(site_url());
		} else {
			$this->new_title = 'Login';

			$data = [
				'title'    	=> '' . $this->new_title . ''
			];

			return view('backend/auth/login', $data);
		}
	}

	public function login()
	{
		if ($this->request->getMethod(true) === 'POST') {
			$post = $this->request->getVar();

			try {
				if (!$this->validation->run($post, 'login')) {
					$response =	$this->field->errorValidation($this->model->table, $post);
				} else {
					$check = $this->access->checkLogin($post);

					if ($check == $this->access->nonActiveUser() || $check == $this->access->notExistRole()) {
						$response = message('error', false, "User don't have access");
					} else if ($check == $this->access->notExistUser() || $check == $this->access->inCorrectPassword()) {
						$response = message('error', false, 'Wrong Username or Password');
					} else {
						if ($check == $this->access->correctPassword()) {
							$this->entity->setDateLastLogin(date('Y-m-d H:i:s'));
							$this->entity->setUserId($this->session->get('sys_user_id'));

							$this->model->save($this->entity);

							$msg = 'Login successfully !';
						} else {
							$msg = $check;
						}

						$response = message('success', true, $msg);
					}
				}
			} catch (\Exception $e) {
				$response = message('error', false, $e->getMessage());
			}

			return $this->response->setJSON($response);
		}
	}

	public function logout()
	{
		$this->session->destroy();
		return redirect()->to(site_url('auth'));
	}

	public function changePassword()
	{
		if ($this->request->getMethod(true) === 'POST') {
			$post = $this->request->getVar();

			try {
				$this->entity->setPassword($post['new_password']);

				if (!$this->validation->run($post, 'change_password')) {
					$errors = [
						'password'		=> $this->validation->getError('password'),
						'new_password'	=> $this->validation->getError('new_password'),
						'conf_password'	=> $this->validation->getError('conf_password')
					];

					$response = message('error', true, $errors);
				} else {
					$response = $this->save();
				}
			} catch (\Exception $e) {
				$response = message('error', false, $e->getMessage());
			}

			return $this->response->setJSON($response);
		}
	}
}
