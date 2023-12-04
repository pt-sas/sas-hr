<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Language extends BaseController
{
	public function en()
	{
			$session = session();
			$locale = $this->request->getLocale();
			$session->remove('lang');
			$session->set('lang', 'en');
			return redirect()->to($_SERVER['HTTP_REFERER']);
	}
	public function id()
	{
			$session = session();
			$locale = $this->request->getLocale();
			$session->remove('lang');
			$session->set('lang', 'id');
			return redirect()->to($_SERVER['HTTP_REFERER']);
	}
}
