<?php

namespace Config;

use CodeIgniter\Validation\CreditCardRules;
use CodeIgniter\Validation\FileRules;
use CodeIgniter\Validation\FormatRules;
use CodeIgniter\Validation\Rules;
use App\Validation\PasswordRules;
use App\Validation\SASRules;

class Validation
{
    //--------------------------------------------------------------------
    // Setup
    //--------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var string[]
     */
    public $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
        PasswordRules::class,
        SASRules::class
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    //--------------------------------------------------------------------
    // Rules
    //--------------------------------------------------------------------
    public $menu = [
        'name'              => [
            'rules'         =>    'required|is_unique[sys_menu.name,sys_menu_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.'
            ]
        ],
        'url'               => [
            'rules'         =>    'required|valid_url'
        ],
        'icon'              => [
            'rules'         =>    'required'
        ],
        'sequence'          => [
            'rules'         =>    'required'
        ]
    ];

    public $submenu = [
        'name'              => [
            'rules'         => 'required|is_unique[sys_submenu.name,sys_submenu_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.'
            ]
        ],
        'url'               => [
            'rules'         => 'required'
        ],
        'sequence'          => [
            'rules'         => 'required'
        ],
        'sys_menu_id' => [
            'label'         => 'Parent',
            'rules'         => 'required',
            'errors'        => [
                'required'  => 'Please Choose the {field} Line'
            ]
        ],
        'action' => [
            'label'         => 'Action',
            'rules'         => 'required',
            'errors'        => [
                'required'  => 'Please Choose the {field} Line'
            ]
        ]
    ];

    public $role = [
        'name'              => [
            'rules'         =>    'required|is_unique[sys_role.name,sys_role_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.'
            ]
        ]
    ];

    public $user = [
        'username'          => [
            'label'         => 'Username',
            'rules'         => 'required|is_unique[sys_user.username,sys_user_id,{id}]',
            'errors'        => [
                'required'  => 'Please Insert the {field} first',
                'is_unique' => 'This {field} already exists.'
            ]
        ],
        'name'              => [
            'label'         => 'Name',
            'rules'         => 'required|is_unique[sys_user.name,sys_user_id,{id}]',
            'errors'        => [
                'required'  => 'Please Insert the {field} first',
                'is_unique' => 'This {field} already exists.'
            ]
        ],
        'password'          => [
            'label'         => 'Password',
            'rules'         => 'required',
            'errors'        => [
                'required'  => 'Please Insert the {field} first'

            ]
        ]
    ];

    public $login = [
        'username'    => 'required',
        'password'    => 'required'
    ];

    public $change_password = [
        'password'        => [
            'label'        => 'old password',
            'rules'        => 'required|match',
            'errors'    => [
                'match'    => 'The {field} does not match'
            ]
        ],
        'new_password'    => [
            'label'        => 'new password',
            'rules'        => 'required|min_length[5]'
        ],
        'conf_password'    => [
            'label'        => 'confirmation password',
            'rules'        => 'required|matches[new_password]'
        ]
    ];

    public $branch = [
        'value'             => [
            'label'            => 'Branch Code',
            'rules'            =>    'required|min_length[7]|max_length[7]|is_unique[md_branch.value,md_branch_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Branch Name',
            'rules'            => 'required|is_unique[md_branch.name,md_branch_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Please Insert the {field} first'
            ]
        ],
        'address'              => [
            'label'            => 'Address',
            'rules'            => 'required',
            'errors'        => [
                'required'    => 'Please Insert the {field} first'
            ]
        ]
    ];

    public $reference = [
        'name'                  => [
            'label'             => 'Name',
            'rules'             => 'required|is_unique[sys_reference.name,sys_reference_id,{id}]',
            'errors'            => [
                'is_unique'     => 'This {field} already exists.',
                'required'      => 'Please Insert the {field} first'
            ]
        ],
        'validationtype'        => [
            'label'             => 'Validation Type',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Please Choose the {field} first.'
            ]
        ],
        'line'                  => [
            'label'             => 'Reference List',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Please Insert the {field} first.'
            ]
        ],
        'detail.table.*.value_line'  => [
            'label'             => 'Search Key',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Please Insert the {field} Line'
            ]
        ],
        'detail.table.*.name_line'  => [
            'label'             => 'Name',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Please Insert the {field} Line'
            ]
        ]
    ];
}
