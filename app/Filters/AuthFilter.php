<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

use App\Libraries\Access;

class AuthFilter implements FilterInterface
{
	/**
	 * Do whatever processing this filter needs to do.
	 * By default it should not return anything during
	 * normal execution. However, when an abnormal state
	 * is found, it should return an instance of
	 * CodeIgniter\HTTP\Response. If it does, script
	 * execution will end and that Response will be
	 * sent back to the client, allowing for error pages,
	 * redirects, etc.
	 *
	 * @param RequestInterface $request
	 * @param array|null       $arguments
	 *
	 * @return mixed
	 */
	public function before(RequestInterface $request, $arguments = null)
	{
		$access = new Access();

		$uri = $request->uri->getSegment(1);

		if (!empty($uri)) {
			if ($uri === 'auth' && session()->get('logged_in')) {
				return redirect()->to(site_url('sas'));
			} else if ($uri !== 'auth' && !session()->get('logged_in')) {
				return redirect()->to(site_url('auth'));
			} else if ($uri === 'sas') {
				$isView = 'isview';

				$uri2 = $request->uri->getSegment(2);
				$previous_url = session()->get('previous_url');

				$checkMenu = $access->getMenu($uri2, "name");
				$checkCrud = $access->checkCrud($uri2, $isView);

				if (!empty($uri2) && $checkMenu) {
					if ($checkCrud) {
						// same url and access is not Y
						if ($previous_url === current_url() && $checkCrud !== 'Y' && !$request->isAjax()) {
							session()->setFlashdata('error', "You are role don't have permission");
							return redirect()->to(site_url('sas'));
						} else if ($previous_url !== current_url() && $checkCrud !== 'Y' && !$request->isAjax()) {
							session()->setFlashdata('error', "You are role don't have permission");
							return redirect()->back();
						}
					} else {
						session()->setFlashdata('error', "Menu has not been set permission");
						return redirect()->back();
					}
				}
			}
		} else {
			return redirect()->to(site_url('auth'));
		}
	}

	/**
	 * Allows After filters to inspect and modify the response
	 * object as needed. This method does not allow any way
	 * to stop execution of other after filters, short of
	 * throwing an Exception or Error.
	 *
	 * @param RequestInterface  $request
	 * @param ResponseInterface $response
	 * @param array|null        $arguments
	 *
	 * @return mixed
	 */
	public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
	{
		//
	}
}
