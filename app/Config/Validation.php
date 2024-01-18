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
                'required'  => 'Mohon mengisi {field} dahulu',
                'is_unique' => 'This {field} already exists.'
            ]
        ],
        'name'              => [
            'label'         => 'Name',
            'rules'         => 'required|is_unique[sys_user.name,sys_user_id,{id}]',
            'errors'        => [
                'required'  => 'Mohon mengisi {field} dahulu',
                'is_unique' => 'This {field} already exists.'
            ]
        ],
        'password'          => [
            'label'         => 'Password',
            'rules'         => 'required',
            'errors'        => [
                'required'  => 'Mohon mengisi {field} dahulu'

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

    public $reference = [
        'name'                  => [
            'label'             => 'Name',
            'rules'             => 'required|is_unique[sys_reference.name,sys_reference_id,{id}]',
            'errors'            => [
                'is_unique'     => 'This {field} already exists.',
                'required'      => 'Mohon mengisi {field} dahulu'
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
                'required'      => 'Mohon mengisi {field} dahulu.'
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
        'value'                => [
            'label'            => 'Branch Code',
            'rules'            => 'required|min_length[7]|max_length[7]|is_unique[md_branch.value,md_branch_id,{id}]',
            'errors'           => [
                'is_unique'    => 'This {field} already exists.',
                'required'     => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'name'                 => [
            'label'            => 'Branch Name',
            'rules'            => 'required|is_unique[md_branch.name,md_branch_id,{id}]',
            'errors'           => [
                'is_unique'    => 'This {field} already exists.',
                'required'     => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'address'              => [
            'label'            => 'Address',
            'rules'            => 'required',
            'errors'           => [
                'required'     => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $division = [
        'value'                 => [
            'label'             => 'Division Code',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_division.value,md_division_id,{id}]',
            'errors'            => [
                'is_unique'     => 'This {field} already exists.',
                'required'      => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Division Name',
            'rules'            => 'required|is_unique[md_division.name,md_division_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $religion = [
        'value'                 => [
            'label'             => 'Religion Code',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_religion.value,md_religion_id,{id}]',
            'errors'            => [
                'is_unique'     => 'This {field} already exists.',
                'required'      => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Religion Name',
            'rules'            => 'required|is_unique[md_religion.name,md_religion_id,{id}]',
            'errors'           => [
                'is_unique'    => 'This {field} already exists.',
                'required'     => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $country = [
        'value'                 => [
            'label'             => 'Country Code',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_religion.value,md_religion_id,{id}]',
            'errors'            => [
                'is_unique'     => 'This {field} already exists.',
                'required'      => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Country Name',
            'rules'            => 'required|is_unique[md_country.name,md_country_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $bloodtype = [
        'value'             => [
            'label'             => 'BloodType Code',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_bloodtype.value,md_bloodtype_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'BloodType Name',
            'rules'            => 'required|is_unique[md_bloodtype.name,md_bloodtype_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $position = [
        'value'             => [
            'label'             => 'Position Code',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_position.value,md_position_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Position Name',
            'rules'            =>    'required|is_unique[md_position.name,md_position_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $status = [
        'value'             => [
            'label'             => 'Status Code',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_status.value,md_status_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Status Name',
            'rules'            =>    'required|is_unique[md_status.name,md_status_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $province = [
        'value'             => [
            'label'             => 'Kode Provinsi',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_province.value,md_province_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Nama Provinsi',
            'rules'            =>    'required|is_unique[md_province.name,md_province_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_country_id'     => [
            'label'         => 'Negara',
            'rules'         => 'required',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]

        ]
    ];

    public $city = [
        'value'             => [
            'label'             => 'Kode Kota',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_city.value,md_city_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Nama Kota',
            'rules'            =>    'required|is_unique[md_city.name,md_city_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_province_id'     => [
            'label'         => 'Provinsi',
            'rules'         => 'required',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $district = [
        'value'             => [
            'label'             => 'Kode Kecamatan',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_district.value,md_district_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Nama Kota',
            'rules'            =>    'required|is_unique[md_district.name,md_district_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_city_id'     => [
            'label'         => 'kota',
            'rules'         => 'required',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $subdistrict = [
        'value'             => [
            'label'             => 'Kode Kelurahan',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_subdistrict.value,md_subdistrict_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Nama Kelurahan',
            'rules'            =>    'required|is_unique[md_subdistrict.name,md_subdistrict_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_district_id'     => [
            'label'         => 'Kecamatan',
            'rules'         => 'required',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $levelling = [
        'value'             => [
            'label'             => 'Kode Jabatan',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_levelling.value,md_levelling_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Nama Jabatan',
            'rules'            =>    'required|is_unique[md_leveling.name,md_levelling_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $day = [
        'value'             => [
            'label'             => 'Kode Hari',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_day.value,md_day_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Nama Hari',
            'rules'            =>    'required|is_unique[md_day.name,md_day_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $holiday = [
        'name'                 => [
            'label'            => 'Nama Holiday',
            'rules'            =>    'required|is_unique[md_holiday.name,md_holiday_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],

        'startdate'                 => [
            'label'            => 'Tanggal',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $leavetype = [
        'name'                 => [
            'label'            => 'Nama Tipe Cuti',
            'rules'            =>    'required|is_unique[md_leavetype.name,md_leavetype_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'duration'                 => [
            'label'            => 'Durasi',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $skill = [
        'value'             => [
            'label'             => 'Kode Keterampilan',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_skill.value,md_skill_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Nama Keterampilan',
            'rules'            =>    'required|is_unique[md_skill.name,md_skill_id,{id}]',
            'errors'        => [
                'is_unique' => 'This {field} already exists.',
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $massleave = [
        'name'                 => [
            'label'            => 'Nama Cuti Massal',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'startdate'                 => [
            'label'            => 'Tanggal',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $absent = [
        'md_employee_id'                 => [
            'label'            => 'Karyawan',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_branch_id'                 => [
            'label'            => 'Cabang',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_division_id'                 => [
            'label'            => 'Divisi',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'startdate'                 => [
            'label'            => 'Tanggal',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'enddate'                 => [
            'label'            => 'Tanggal',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
    ];

    public $izinpulangcepat = [
        'md_employee_id'                 => [
            'label'            => 'Karyawan',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_branch_id'                 => [
            'label'            => 'Cabang',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_division_id'                 => [
            'label'            => 'Divisi',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'startdate'                 => [
            'label'            => 'Tanggal',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];
}
