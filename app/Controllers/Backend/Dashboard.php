<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
	public function index()
	{
		return $this->template->render('backend/dashboard');
	}
}
