<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var string[]
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------
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
            'rules'         =>    'required|is_unique[sys_user.username,sys_user_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.'
            ]
        ],
        'name'              => [
            'rules'         =>    'required|is_unique[sys_user.name,sys_user_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.'
            ]
        ],
        'password'          => 'required',
        'role.*'            => [
            'label'         => 'role',
            'rules'         => 'required'
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
            'rules'            =>    'required|is_unique[md_branch.name,md_branch_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Please Insert the {field} first'
            ]
        ]
    ];
}
