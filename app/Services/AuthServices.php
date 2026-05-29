<?php

namespace App\Services;

use App\Entities\EmpEducation;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnAuthorizedException;
use App\Models\M_Attendance;
use App\Models\M_Role;
use App\Models\M_Submenu;
use App\Services\BaseServices;

class AuthServices extends BaseServices
{
    protected $roleID;

    public function __construct(int $userID, int $employeeID, int $roleID)
    {
        parent::__construct();

        //* Set User & Employee Session
        $this->userID = $userID;
        $this->employeeID = $employeeID;
        $this->roleID = $roleID;
    }

    public function checkAccess(String $menuUrl, String $method)
    {
        $mRole = new M_Role($this->request);
        $mSubMenu = new M_Submenu($this->request);

        $subMenu = $mSubMenu->where(['url' => $menuUrl, 'isactive' => 'Y'])->first();
        if (empty($subMenu)) throw new NotFoundException('Menu tidak ditemukan');

        $access = $mRole->detail(['am.sys_role_id' => $this->roleID, 'am.sys_submenu_id' => $subMenu->sys_submenu_id])->getRow();
        if (empty($access) || $access->{$method} == 'N') throw new UnAuthorizedException("Anda tidak memiliki akses");
    }
}
