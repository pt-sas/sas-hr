<?php

namespace App\Libraries;

use App\Models\M_User;
use App\Models\M_Menu;
use App\Models\M_Submenu;
use App\Models\M_Role;

use Config\Services;

class Access
{
    protected $request;
    protected $session;
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
        $this->session = Services::session();
        $this->request = Services::request();
    }

    /**
     * check login
     * 0 = username tidak ada
     * 1 = sukses
     * 2 = password salah
     * 3 = user nonaktif
     * 4 = role tidak ada
     * @param unknown_type $post
     * @return boolean
     */
    public function checkLogin($post)
    {
        $user = new M_User($this->request);

        $dataUser = $user->detail([
            'BINARY(username)'    => $post['username']
        ])->getRow();

        if ($dataUser) {
            if ($dataUser->isactive === $this->active() && !empty($dataUser->role)) {
                if (password_verify($post['password'], $dataUser->password)) {
                    $this->session->set([
                        'sys_user_id'   => $dataUser->sys_user_id,
                        'sys_role_id'   => $dataUser->role,
                        'logged_in'     => TRUE
                    ]);
                    return $this->correctPassword();
                } else {
                    return $this->inCorrectPassword();
                }
            } else if ($dataUser->isactive === $this->active() && empty($dataUser->role)) {
                return $this->notExistRole();
            } else {
                return $this->nonActiveUser();
            }
        }

        return $this->notExistUser();
    }

    /**
     * Check permission Create Read Update Delete
     *
     * @param [type] $uri uri segment
     * @param [type] $field from database
     * @param [type] $menu_id
     * @param [type] $setmenu
     * @return void
     */
    public function checkCrud($uri = null, $field = null, $menu_id = null, $setmenu = null)
    {
        $menu = new M_Menu($this->request);
        $submenu = new M_Submenu($this->request);
        $role = new M_Role($this->request);

        try {
            if (!empty($uri)) {
                // Check uri segment from submenu
                $sub = $submenu->where('url', $uri)->first();

                // Check uri segment from main menu
                $parent = $menu->where('url', $uri)->first();

                // submenu already in submenu
                if (isset($sub)) {
                    // Check submenu is set in menu access
                    $access = $role->detail([
                        'am.sys_submenu_id'     => $sub->getSubId(),
                        'am.sys_role_id'        => $this->getSessionRole()
                    ])->getRow();

                    // submenu set in role and role isactive Y
                    if ($access && $access->isactive === 'Y')
                        $field = $access->$field;
                    else
                        $field = false;
                } else if (isset($parent)) {
                    // Check menu is set in menu access
                    $access = $role->detail([
                        'am.sys_menu_id'        => $parent->getMenuId(),
                        'am.sys_role_id'        => $this->getSessionRole()
                    ])->getRow();

                    // menu set in role and role isactive Y
                    if ($access && $access->isactive === 'Y')
                        $field = $access->$field;
                    else
                        $field = false;
                } else {
                    // not already
                    $field = false;
                }
            } else {
                if ($setmenu === 'parent') {
                    $access = $role->detail([
                        'am.sys_menu_id'        => $menu_id,
                        'am.sys_role_id'        => $this->getSessionRole()
                    ])->getRow();

                    if ($access && $access->isactive === 'Y')
                        $field = $access->$field;
                    else
                        $field = false;
                } else {
                    $access = $role->detail([
                        'am.sys_submenu_id'     => $menu_id,
                        'am.sys_role_id'        => $this->getSessionRole()
                    ])->getRow();

                    // submenu set in role
                    if ($access && $access->isactive === 'Y')
                        $field = $access->$field;
                    else
                        $field = false;
                }
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }

        return $field;
    }

    public function getUser($field)
    {
        $user = new M_User($this->request);
        $row = $user->find($this->getSessionUser());
        return $row->$field;
    }

    public function getRole()
    {
        $role = new M_Role($this->request);
        $row = $role->find($this->getSessionRole());
        return $row;
    }

    public function getMenu($uri, $field)
    {
        $menu = new M_Menu($this->request);
        $submenu = new M_Submenu($this->request);

        if (!empty($uri)) {
            $sub = $submenu->where('url', $uri)->first();

            $parent = $menu->where('url', $uri)->first();

            if (isset($sub)) {
                $field = $sub->$field;
            } else if (isset($parent)) {
                $field = $parent->$field;
            } else {
                $field = false;
            }
        } else {
            $field = false;
        }

        return $field;
    }

    /**
     * Get session user
     *
     * @return sys_user_id or null
     */
    public function getSessionUser()
    {
        return !empty($this->session->get('sys_user_id')) ? $this->session->get('sys_user_id') : null;
    }

    /**
     * Get session role
     *
     * @return sys_role_id or null
     */
    public function getSessionRole()
    {
        return !empty($this->session->get('sys_role_id')) ? $this->session->get('sys_role_id') : null;
    }

    /**
     * Username tidak ada
     *
     * @return void
     */
    public function notExistUser()
    {
        return 0;
    }

    /**
     * Password benar
     *
     * @return void
     */
    public function correctPassword()
    {
        return 1;
    }

    /**
     * Password salah
     *
     * @return void
     */
    public function inCorrectPassword()
    {
        return 2;
    }

    /**
     * 
     * User nonaktif
     *
     * @return void
     */
    public function nonActiveUser()
    {
        return 3;
    }

    /**
     * 
     * Role tidak ada
     *
     * @return void
     */
    public function notExistRole()
    {
        return 4;
    }

    /**
     * Data aktif
     *
     * @return void
     */
    public function active()
    {
        return "Y";
    }

    /**
     * Data nonaktif
     *
     * @return void
     */
    public function nonActive()
    {
        return "N";
    }

    /**
     * Get data user role based on role and user
     *
     * @param [type] $user_id
     * @param [type] $role_name
     * @return void
     */
    public function getUserRoleName($user_id, $role_name)
    {
        $user = new M_User($this->request);

        $role = $user->detail([
            'sr.isactive'           => $this->active(),
            'sys_user.sys_user_id'  => $user_id,
            'sr.name'               => $role_name
        ])->getRow();

        return $role;
    }
}
