<?php

namespace Config;

use CodeIgniter\Validation\CreditCardRules;
use CodeIgniter\Validation\FileRules;
use CodeIgniter\Validation\FormatRules;
use CodeIgniter\Validation\Rules;
use App\Validation\PasswordRules;
use App\Validation\SASRules;
use App\Validation\SASFileRules;

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
        CreditCardRules::class,
        PasswordRules::class,
        SASRules::class,
        SASFileRules::class
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
                'required'      => 'Mohon pilih {field} dahulu.'
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
        'value'                 => [
            'label'             => 'Kode Provinsi',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_province.value,md_province_id,{id}]',
            'errors'            => [
                'is_unique'     => '{field} sudah ada',
                'required'      => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Nama Provinsi',
            'rules'            => 'required|is_exist[md_province.name,md_province_id,{id},md_country_id,{md_country_id}]',
            'errors'           => [
                'is_exist'     => '{field} sudah ada',
                'required'     => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_country_id'        => [
            'label'            => 'Negara',
            'rules'            => 'required',
            'errors'           => [
                'required'     => 'Mohon mengisi {field} dahulu'
            ]

        ]
    ];

    public $city = [
        'value'                 => [
            'label'             => 'Kode Kota',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_city.value,md_city_id,{id}]',
            'errors'            => [
                'is_unique'     => '{field} sudah ada',
                'required'      => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Nama Kota',
            'rules'            => 'required|is_exist[md_city.name,md_city_id,{id},md_province_id,{md_province_id}]',
            'errors'           => [
                'is_exist'     => '{field} sudah ada',
                'required'     => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_province_id'       => [
            'label'            => 'Provinsi',
            'rules'            => 'required',
            'errors'           => [
                'is_unique'    => '{field} sudah ada',
                'required'     => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $district = [
        'value'                 => [
            'label'             => 'Kode Kecamatan',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_district.value,md_district_id,{id}]',
            'errors'            => [
                'is_unique'     => '{field} sudah ada',
                'required'      => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Nama Kota',
            'rules'            => 'required|is_exist[md_district.name,md_district_id,{id},md_city_id,{md_city_id}]',
            'errors'           => [
                'is_exist'     => '{field} sudah ada',
                'required'     => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_city_id'           => [
            'label'            => 'kota',
            'rules'            => 'required',
            'errors'           => [
                'is_unique'    => '{field} sudah ada',
                'required'     => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $subdistrict = [
        'value'                 => [
            'label'             => 'Kode Kelurahan',
            'rules'             => 'required|min_length[7]|max_length[7]|is_unique[md_subdistrict.value,md_subdistrict_id,{id}]',
            'errors'            => [
                'is_unique'     => '{field} sudah ada',
                'required'      => 'Please Fill {field} first'
            ]
        ],
        'name'                 => [
            'label'            => 'Nama Kelurahan',
            'rules'            => 'required|is_exist[md_subdistrict.name,md_subdistrict_id,{id},md_district_id,{md_district_id}]',
            'errors'           => [
                'is_exist'     => '{field} sudah ada',
                'required'     => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_district_id'       => [
            'label'            => 'Kecamatan',
            'rules'            => 'required',
            'errors'           => [
                'required'     => 'Mohon mengisi {field} dahulu'
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
        'md_employee_id'        => [
            'label'             => 'karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_division_id'        => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'startdate'             => [
            'label'             => 'tanggal mulai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'enddate'               => [
            'label'             => 'tanggal selesai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'reason'                => [
            'label'             => 'alasan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $pengajuan = [
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
        'nik'                 => [
            'label'            => 'NIK',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'datestart'                 => [
            'label'            => 'Tanggal',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'starttime'                 => [
            'label'            => 'Jam',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'reason'                 => [
            'label'            => 'Alasan',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $pengajuantugas = [
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
        'nik'                 => [
            'label'            => 'NIK',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'datestart'                 => [
            'label'            => 'Tanggal',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'starttime'                 => [
            'label'            => 'Jam',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'endtime'                 => [
            'label'            => 'Jam',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'reason'                 => [
            'label'            => 'Alasan',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'image'                 => [
            'label'             => 'foto',
            'rules'             => 'max_size[image, 3024]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png]',
            'errors'            => [
                // 'uploaded'      => 'Mohon upload {field} dahulu',
                'max_size'      => 'Data {field} melebihi batas maksimum 3 Mb',
                'is_image'      => 'Format file {field} salah',
                'mime_in'       => 'Format file {field} wajib {param}',
            ]
        ],
    ];

    public $employee = [
        'value'                 => [
            'label'             => 'value',
            'rules'             => 'required|is_unique[md_employee.value,md_employee_id,{id}]',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
                'is_unique'     => 'Data {field} ini sudah ada'
            ]
        ],
        'image'                 => [
            'label'             => 'gambar',
            'rules'             => 'max_size[image, 3024]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png]',
            // 'rules'             => 'max_size[image, 3024]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png]',
            'errors'            => [
                // 'uploaded'      => 'Mohon upload {field} dahulu',
                'max_size'      => 'Data {field} melebehi batas maksimum 3 Mb',
                'is_image'      => 'Format file {field} salah',
                'mime_in'       => 'Format file {field} wajib {param}',
            ]
        ],
        'nik'                   => [
            'label'             => 'nik',
            'rules'             => 'required|min_length[6]|max_length[6]|is_unique[md_employee.nik,md_employee_id,{id}]',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
                'min_length'    => 'Minimal {field} harus {param} karakter',
                'max_length'    => 'Maksimal {field} harus {param} karakter',
                'is_unique'     => 'Data {field} ini sudah ada'
            ]
        ],
        'fullname'              => [
            'label'             => 'nama lengkap',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'pob'                   => [
            'label'             => 'tempat lahir',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'birthday'              => [
            'label'             => 'tanggal lahir',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'gender'                => [
            'label'             => 'jenis kelamin',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'nationality'           => [
            'label'             => 'kewarganegaraan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_religion_id'        => [
            'label'             => 'agama',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'marital_status'        => [
            'label'             => 'status menikah',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'phone'                 => [
            'label'             => 'no hp pribadi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'homestatus'            => [
            'label'             => 'status rumah',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_position_id'        => [
            'label'             => 'jabatan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_levelling_id'       => [
            'label'             => 'level',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_status_id'          => [
            'label'             => 'status karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_division_id.*'      => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'address_dom'           => [
            'label'             => 'alamat domisili',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_country_dom_id'     => [
            'label'             => 'negara',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_province_dom_id'    => [
            'label'             => 'provinsi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_city_dom_id'        => [
            'label'             => 'kota',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_district_dom_id'    => [
            'label'             => 'kecamatan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_subdistrict_dom_id' => [
            'label'             => 'kelurahan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'postalcode_dom'        => [
            'label'             => 'kode pos',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'card_id'               => [
            'label'             => 'no ktp',
            'rules'             => 'required|min_length[16]|is_unique[md_employee.card_id,md_employee_id,{id}]',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
                'min_length'    => 'Minimal {field} harus {param} karakter',
                'is_unique'     => 'Data {field} ini sudah ada'
            ]
        ],
    ];

    public $employee_family_core = [
        'childnumber'           => [
            'label'             => 'anak keberapa',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'nos'                   => [
            'label'             => 'jumlah saudara',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'line'                  => [
            'label'             => 'data keluarga',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.member_line'  => [
            'label'             => 'keluarga',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.name_line'  => [
            'label'             => 'nama',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.gender_line'  => [
            'label'             => 'jenis kelamin',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        // 'detail.table.*.age_line'  => [
        //     'label'             => 'umur',
        //     'rules'             => 'required',
        //     'errors'            => [
        //         'required'      => 'Mohon mengisi detail {field} dahulu'
        //     ]
        // ],
        // 'detail.table.*.education_line'  => [
        //     'label'             => 'pendidikan',
        //     'rules'             => 'required',
        //     'errors'            => [
        //         'required'      => 'Mohon mengisi detail {field} dahulu'
        //     ]
        // ],
        'detail.table.*.status_line'  => [
            'label'             => 'status',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.birthdate_line'  => [
            'label'             => 'Tanggal Lahir',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
    ];

    public $employee_family = [
        'line'                  => [
            'label'             => 'data keluarga setelah menikah',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.member_line'  => [
            'label'             => 'keluarga',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.name_line'  => [
            'label'             => 'nama',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.gender_line'  => [
            'label'             => 'jenis kelamin',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        // 'detail.table.*.age_line'  => [
        //     'label'             => 'umur',
        //     'rules'             => 'required',
        //     'errors'            => [
        //         'required'      => 'Mohon mengisi detail {field} dahulu'
        //     ]
        // ],
        // 'detail.table.*.education_line'  => [
        //     'label'             => 'pendidikan',
        //     'rules'             => 'required',
        //     'errors'            => [
        //         'required'      => 'Mohon mengisi detail {field} dahulu'
        //     ]
        // ],
        'detail.table.*.status_line'  => [
            'label'             => 'status',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.birthdate_line'  => [
            'label'             => 'Tanggal Lahir',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
    ];

    public $employee_education = [
        'line'                  => [
            'label'             => 'riwayat pendidikan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.education_line'  => [
            'label'             => 'pendidikan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.school_line'  => [
            'label'             => 'nama sekolah',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.city_line'  => [
            'label'             => 'kota',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.status_line'  => [
            'label'             => 'status',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
    ];

    public $employee_job = [
        'line'                  => [
            'label'             => 'riwayat pekerjaan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.company_line'  => [
            'label'             => 'perusahaan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.startdate_line'  => [
            'label'             => 'tanggal mulai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.enddate_line'  => [
            'label'             => 'tanggal selesai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.position_line'  => [
            'label'             => 'posisi terakhir',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
    ];

    public $employee_vaccine = [
        'line'                  => [
            'label'             => 'riwayat vaksin',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.vaccinetype_line'  => [
            'label'             => 'jenis vaksin',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.vaccinedate_line'  => [
            'label'             => 'tanggal vaksin',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ]
    ];

    public $employee_skill = [
        'line'                  => [
            'label'             => 'keterampilan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.name_line'  => [
            'label'             => 'keterampilan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.description_line'  => [
            'label'             => 'keterangan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.ability_line'  => [
            'label'             => 'kemampuan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ]
    ];

    public $employee_language = [
        'line'                  => [
            'label'             => 'Penguasaan Bahasa',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.name_line'  => [
            'label'             => 'Bahasa',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.written_ability_line'  => [
            'label'             => 'Kemampuan Tertulis',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.verbal_ability_line'  => [
            'label'             => 'kemampuan Lisan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ]
    ];

    public $employee_course = [
        'line'                  => [
            'label'             => 'kursus',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.course_line'  => [
            'label'             => 'kursus / training yang diikuti',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.intitution_line'  => [
            'label'             => 'nama tempat',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.level_line'  => [
            'label'             => 'level',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.startdate_line'  => [
            'label'             => 'tanggal mulai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.enddate_line'  => [
            'label'             => 'tanggal selesai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.status_line'  => [
            'label'             => 'status',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
    ];

    public $employee_contact = [
        'line'                  => [
            'label'             => 'kontak darurat',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.member_line'  => [
            'label'             => 'hubungan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.name_line'  => [
            'label'             => 'nama',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.phone_line'  => [
            'label'             => 'no hp',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ]
    ];

    public $employee_license = [
        'line'                  => [
            'label'             => 'surat ijin mengemudi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.licensetype_line'  => [
            'label'             => 'tipe SIM',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.license_id_line'  => [
            'label'             => 'no SIM',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.expireddate_line'  => [
            'label'             => 'masa berlaku',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ]
    ];

    public $rule = [
        'menu_url'              => [
            'label'             => 'menu',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'name'                  => [
            'label'             => 'nama',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'priority'              => [
            'label'             => 'prioritas',
            'rules'             => 'required|is_unique[md_rule.priority,md_rule_id,{id}]',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
                'is_unique'     => 'Data {field} ini sudah ada'
            ]
        ]
    ];

    public $rule_detail = [
        'line'                  => [
            'label'             => 'rule detail',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.name_line'  => [
            'label'             => 'nama',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
    ];

    public $rule_value = [
        'line'                  => [
            'label'             => 'rule value',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.name_line'  => [
            'label'             => 'nama',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.value_line'  => [
            'label'             => 'value',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
    ];

    public $responsible = [
        'name'                  => [
            'label'             => 'Nama',
            'rules'             => 'required|is_unique[sys_wfresponsible.name,sys_wfresponsible_id,{id}]',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
                'is_unique'     => 'Data {field} ini sudah ada'
            ]
        ],
        'responsibletype'       => [
            'label'             => 'tipe responsible',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'sys_role_id'           => [
            'label'             => 'role',
            'rules'             => 'required_based_field_value[responsibletype, R]',
            'errors'            => [
                'required_based_field_value'    => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'sys_user_id'           => [
            'label'             => 'user',
            'rules'             => 'required_based_field_value[responsibletype, H]',
            'errors'            => [
                'required_based_field_value'    => 'Mohon mengisi {field} dahulu',
            ]
        ]
    ];

    public $mail = [
        'smtphost'              => [
            'label'             => 'Mail Host',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'smtpport'              => [
            'label'             => 'SMTP Port',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'smtpcrypto'            => [
            'label'             => 'SMTP Crypto',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'smtpuser'              => [
            'label'             => 'Request User',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'smtppassword'          => [
            'label'             => 'Request User Password',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'requestemail'          => [
            'label'             => 'Request Email',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ]
    ];

    public $notifText = [
        'name'                  => [
            'label'             => 'nama',
            'rules'             => 'required|is_unique[sys_notiftext.name,sys_notiftext_id,{id}]',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
                'is_unique'     => 'Data {field} ini sudah ada'
            ]
        ],
        'subject'               => [
            'label'             => 'subjek',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'text'                  => [
            'label'             => 'text',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'notiftype'             => [
            'label'             => 'tipe notifikasi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ]
    ];

    public $wscenario = [
        'name'                  => [
            'label'             => 'nama',
            'rules'             => 'required|is_unique[sys_wfscenario.name,sys_wfscenario_id,{id}]',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
                'is_unique'     => 'Data {field} ini sudah ada'
            ]
        ],
        'menu'                  => [
            'label'             => 'menu',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'line'                  => [
            'label'             => 'scenario',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu',
            ]
        ],
        'detail.table.*.sys_wfresponsible_id_line'  => [
            'label'             => 'responsible',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu',
            ]
        ],
        'detail.table.*.sys_notiftext_id_line'  => [
            'label'             => 'template notifikasi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu',
            ]
        ]
    ];

    public $ijinkeluarkantor = [
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
        'nik'                 => [
            'label'            => 'NIK',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'datestart'                 => [
            'label'            => 'Tanggal',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'starttime'                 => [
            'label'            => 'Jam',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'reason'                 => [
            'label'            => 'Alasan',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $lemburAddRow = [
        'md_branch_id'          => [
            'label'             => 'Cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'md_division_id'        => [
            'label'             => 'Divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'startdate'         => [
            'label'         => 'Tanggal',
            'rules'         => 'required',
            'errors'        => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'md_supplier_id'        => [
            'label'             => 'Asal Outsourcing',
            'rules'             => 'required_based_field_value[isemployee, N]',
            'errors'            => [
                'required_based_field_value'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
    ];

    public $lembur = [
        'md_employee_id'        => [
            'label'             => 'Karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'Cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'md_division_id'        => [
            'label'             => 'Divisi',
            'rules'             => 'is_exist[md_division.name,md_division_id,{id},md_division_id,{md_division_id}]',
            'errors'            => [
                'is_exist'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'startdate'             => [
            'label'             => 'Tanggal',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'enddate'               => [
            'label'             => 'Tanggal',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'description'           => [
            'label'             => 'Deskripsi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon isi {field} dahulu'
            ]
        ],
        'md_supplier_id'        => [
            'label'             => 'Asal Outsourcing',
            'rules'             => 'required_based_field_value[isemployee, N]',
            'errors'            => [
                'required_based_field_value'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'line'                  => [
            'label'             => 'Lembur Detail',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu.'
            ]
        ],
        'detail.table.*.md_employee_id_line'  => [
            'label'             => 'Karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'detail.table.*.starttime_line'  => [
            'label'             => 'Jam Mulai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],

        'detail.table.*.endtime_line'  => [
            'label'             => 'Jam Mulai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
    ];

    public $alpa = [
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
        'nik'                 => [
            'label'            => 'NIK',
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
            'label'            => 'Jam',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'reason'                 => [
            'label'            => 'Alasan',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'submissiontype'                 => [
            'label'            => 'Tipe Alpa',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $sakit = [
        'md_employee_id'        => [
            'label'             => 'karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_division_id'        => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'startdate'             => [
            'label'             => 'tanggal mulai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'enddate'               => [
            'label'             => 'tanggal selesai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'reason'                => [
            'label'             => 'alasan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'image'                 => [
            'label'             => 'foto',
            'rules'             => 'max_size[image, 3072]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png]',
            'errors'            => [
                // 'uploaded'      => 'Mohon upload {field} dahulu',
                'max_size'      => 'Data {field} melebihi batas maksimum 1 Mb',
                'is_image'      => 'Format file {field} salah',
                'mime_in'       => 'Format file {field} wajib {param}',
            ]
        ],
        'image2'                 => [
            'label'             => 'foto 2',
            'rules'             => 'max_size[image2, 3072]|is_image[image2]|mime_in[image3,image/jpg,image/jpeg,image/png]',
            'errors'            => [
                'max_size'      => 'Data {field} melebihi batas maksimum 1 Mb',
                'is_image'      => 'Format file {field} salah',
                'mime_in'       => 'Format file {field} wajib {param}',
            ]
        ],
        'image3'                 => [
            'label'             => 'foto 3',
            'rules'             => 'max_size[image3, 3072]|is_image[image3]|mime_in[image3,image/jpg,image/jpeg,image/png]',
            'errors'            => [
                'max_size'      => 'Data {field} melebihi batas maksimum 1 Mb',
                'is_image'      => 'Format file {field} salah',
                'mime_in'       => 'Format file {field} wajib {param}',
            ]
        ],
    ];

    public $realisasi_agree = [
        'submissiondate'        => [
            'label'             => 'Tanggal Tidak Masuk',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $realisasi_not_agree = [
        'submissiondate'        => [
            'label'             => 'Tanggal Tidak Masuk',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'submissiontype'        => [
            'label'             => 'Tipe Form',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $realisasi_lembur_agree = [
        'enddate'        => [
            'label'             => 'Tanggal Selesai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'endtime'        => [
            'label'             => 'Jam Selesai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'starttime'        => [
            'label'             => 'Jam Mulai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'enddate_realization'        => [
            'label'             => 'Tanggal Realisasi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'endtime_realization'        => [
            'label'             => 'Jam Realisasi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'enddate_att'        => [
            'label'             => 'Tanggal Check Out',
            'rules'             => 'required',
            'errors'            => [
                'required'      => '{field} tidak ada'
            ]
        ],
        'endtime_att'        => [
            'label'             => 'Jam Check Out',
            'rules'             => 'required',
            'errors'            => [
                'required'      => '{field} tidak ada'
            ]
        ]
    ];

    public $realisasi_kehadiran = [
        'enddate_realization'        => [
            'label'             => 'Tanggal Selesai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'endtime_realization'        => [
            'label'             => 'Jam Selesai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $realisasi_tugaskantor = [
        'image'                 => [
            'label'             => 'foto',
            'rules'             => 'uploaded[image]|max_size[image, 3024]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png]',
            'errors'            => [
                'uploaded'      => 'Mohon upload {field} dahulu',
                'max_size'      => 'Data {field} melebihi batas maksimum 3 Mb',
                'is_image'      => 'Format file {field} salah',
                'mime_in'       => 'Format file {field} wajib {param}',
            ]
        ],
    ];

    public $realisasi_tugaskantor_setengah = [
        'image'                 => [
            'label'             => 'foto',
            'rules'             => 'uploaded[image]|max_size[image, 3024]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png]',
            'errors'            => [
                'uploaded'      => 'Mohon upload {field} dahulu',
                'max_size'      => 'Data {field} melebihi batas maksimum 3 Mb',
                'is_image'      => 'Format file {field} salah',
                'mime_in'       => 'Format file {field} wajib {param}',
            ]
        ],
        'starttime_att'          => [
            'label'             => 'jam mulai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'endtime_att'          => [
            'label'             => 'jam selesai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
    ];

    public $attendance = [
        'description'           => [
            'label'             => 'Keterangan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $doctype = [
        'name'                 => [
            'label'            => 'nama',
            'rules'            => 'required|is_unique[md_doctype.name,md_doctype_id,{id}]',
            'errors'           => [
                'is_unique'    => 'This {field} already exists.',
                'required'     => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $anulir = [
        // 'submissiontype'       => [
        //     'label'            => 'Tipe Form',
        //     'rules'            => 'required',
        //     'errors'           => [
        //         'required'     => 'Mohon mengisi {field} dahulu'
        //     ]
        // ],
        'documentno'       => [
            'label'            => 'Doc No',
            'rules'            => 'required',
            'errors'           => [
                'required'     => 'Mohon mengisi {field} dahulu'
            ]
        ],
    ];

    public $supplier = [
        'value'                => [
            'label'            => 'Supplier Code',
            'rules'            => 'required|min_length[7]|max_length[7]|is_unique[md_supplier.value,md_supplier_id,{id}]',
            'errors'           => [
                'is_unique'    => 'This {field} already exists.',
                'required'     => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'name'                 => [
            'label'            => 'Supplier Name',
            'rules'            => 'required|is_unique[md_supplier.name,md_supplier_id,{id}]',
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

    public $outsourcing = [
        'value' => [
            'label'            => 'value',
            'rules'            => 'required|is_unique[md_employee.value,md_employee_id,{id}]',
            'errors'           => [
                'is_unique'    => 'This {field} already exists.',
                'required'     => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'nik'                   => [
            'label'             => 'nik',
            'rules'             => 'required|min_length[6]|max_length[10]|is_unique[md_employee.nik,md_employee_id,{id}]',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
                'min_length'    => 'Minimal {field} harus {param} karakter',
                'max_length'    => 'Maksimal {field} harus {param} karakter',
                'is_unique'     => 'Data {field} ini sudah ada'
            ]
        ],
        'fullname'              => [
            'label'             => 'nama lengkap',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'pob'                   => [
            'label'             => 'tempat lahir',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'birthday'              => [
            'label'             => 'tanggal lahir',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'gender'                => [
            'label'             => 'jenis kelamin',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'nationality'           => [
            'label'             => 'kewarganegaraan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'phone'                 => [
            'label'             => 'no hp pribadi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_position_id'        => [
            'label'             => 'jabatan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_levelling_id'       => [
            'label'             => 'level',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_status_id'          => [
            'label'             => 'status karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_division_id.*'      => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'address_dom'           => [
            'label'             => 'alamat domisili',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_country_dom_id'     => [
            'label'             => 'negara',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_province_dom_id'    => [
            'label'             => 'provinsi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_city_dom_id'        => [
            'label'             => 'kota',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_district_dom_id'    => [
            'label'             => 'kecamatan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_subdistrict_dom_id' => [
            'label'             => 'kelurahan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'postalcode_dom'        => [
            'label'             => 'kode pos',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'card_id'               => [
            'label'             => 'no ktp',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        // 'md_supplier_id'        => [
        //     'label'             => 'vendor',
        //     'rules'             => 'required',
        //     'errors'            => [
        //         'required'      => 'Mohon mengisi {field} dahulu'
        //     ]
        // ],
        'resigndate'            => [
            'label'             => 'tanggal berhenti',
            'rules'             => 'required_based_field_value[md_status_id, 100004]',
            'errors'            => [
                'required_based_field_value'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
    ];

    public $wactivity = [
        'isanswer'        => [
            'label'             => 'isanswer',
            'rules'             => 'required',
            'errors'            => [
                'required'    => 'Mohon pilih {field} dahulu',
            ]
        ],
        'textmsg'        => [
            'label'             => 'message',
            'rules'             => 'required_based_field_value[isanswer, W]',
            'errors'            => [
                'required_based_field_value'    => 'Mohon mengisi {field} dahulu',
            ]
        ]
    ];

    public $absentManual = [
        'md_employee_id' => [
            'label' => 'Karyawan',
            'rules' => 'required',
            'errors'    => [
                'required' => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'date' => [
            'label' => 'Tanggal',
            'rules' => 'required',
            'errors'    => [
                'required' => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'time' => [
            'label' => 'Jam',
            'rules' => 'required',
            'errors'    => [
                'required' => 'Mohon mengisi {field} dahulu'
            ]
        ],
    ];

    public $tugasKantor = [
        'md_employee_id'        => [
            'label'             => 'pemohon',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_division_id'        => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'startdate'             => [
            'label'             => 'tanggal mulai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'enddate'               => [
            'label'             => 'tanggal selesai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'reason'                => [
            'label'             => 'alasan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'image'                 => [
            'label'             => 'foto',
            'rules'             => 'max_size[image, 3024]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png]',
            'errors'            => [
                // 'uploaded'      => 'Mohon upload {field} dahulu',
                'max_size'      => 'Data {field} melebihi batas maksimum 3 Mb',
                'is_image'      => 'Format file {field} salah',
                'mime_in'       => 'Format file {field} wajib {param}',
            ]
        ],
    ];

    public $memo = [
        'md_employee_id'        => [
            'label'             => 'karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_division_id'        => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'memocriteria'          => [
            'label'             => 'kriteria',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'totaldays'          => [
            'label'             => 'total',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'submissiondate'        => [
            'label'             => 'tanggal pengajuan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'memodate'              => [
            'label'             => 'tanggal',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'memocontent'           => [
            'label'             => 'isi memo',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
    ];
    public $employee_benefit = [
        'line'                  => [
            'label'             => 'benefit line',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.benefit_line'  => [
            'label'             => 'benefit',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.status_line'  => [
            'label'             => 'status',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.isdetail_line'  => [
            'label'             => 'detail',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ]
    ];

    public $benefit_detail = [
        'line'                  => [
            'label'             => 'benefit detail line',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.benefit_detail_line'  => [
            'label'             => 'benefit detail',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ]
    ];

    public $leave = [
        'md_employee_id'        => [
            'label'             => 'karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_division_id'        => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'submissiondate'        => [
            'label'             => 'tanggal pengajuan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'startdate'             => [
            'label'             => 'tanggal mulai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'enddate'               => [
            'label'             => 'tanggal selesai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'reason'                => [
            'label'             => 'alasan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $employee_allo = [
        'md_employee_id'        => [
            'label'             => 'karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_division_id'        => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_position_id'        => [
            'label'             => 'posisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'branch_to'          => [
            'label'             => 'cabang tujuan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'division_to'        => [
            'label'             => 'divisi tujuan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'position_to'        => [
            'label'             => 'posisi tujuan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'submissiondate'        => [
            'label'             => 'tanggal pengajuan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'date'             => [
            'label'             => 'tanggal',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'description'                => [
            'label'             => 'alasan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $broadcast_bb = [
        'title' => [
            'label'  => 'judul',
            'rules'  => 'required',
            'errors' => [
                'required' => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'message' => [
            'label' => 'pesan',
            'rules'  => 'required',
            'errors' => [
                'required' => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $resign = [
        'md_employee_id'        => [
            'label'             => 'karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_division_id'        => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_position_id'        => [
            'label'             => 'posisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'submissiondate'        => [
            'label'             => 'tanggal pengajuan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'date'             => [
            'label'             => 'tanggal',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'letterdate'             => [
            'label'             => 'tanggal surat',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'departuretype'                => [
            'label'             => 'tipe berhenti',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'description'                => [
            'label'             => 'alasan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $pembatalan_cuti = [
        'image'                 => [
            'label'             => 'foto',
            'rules'             => 'max_size[image, 3072]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png]',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu',
                'max_size'      => 'Data {field} melebihi batas maksimum 1 Mb',
                'is_image'      => 'Format file {field} salah',
                'mime_in'       => 'Format file {field} wajib {param}',
            ]
        ],
        'md_employee_id'        => [
            'label'             => 'Karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'Cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'md_division_id'        => [
            'label'             => 'Divisi',
            'rules'             => 'is_exist[md_division.name,md_division_id,{id},md_division_id,{md_division_id}]',
            'errors'            => [
                'is_exist'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'reference_id'        => [
            'label'             => 'referensi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'reason'           => [
            'label'             => 'Alasan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon isi {field} dahulu'
            ]
        ],
        'line'                  => [
            'label'             => 'Detail',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu.'
            ]
        ],
    ];

    public $list_pertanyaan = [
        'value'                  => [
            'label'             => 'value',
            'rules'             => 'required|is_unique[md_question_group.value,md_question_group_id,{id}]',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
                'is_unique'     => 'Data {field} ini sudah ada'
            ]
        ],
        'name'                  => [
            'label'             => 'name',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'menu_url'                  => [
            'label'             => 'menu',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'sequence'                  => [
            'label'             => 'sequence',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'line'                  => [
            'label'             => 'list pertanyaan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'detail.table.*.no_line'  => [
            'label'             => 'no',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu',
            ]
        ],
        'detail.table.*.question_line'  => [
            'label'             => 'pertanyaan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu',
            ]
        ],
        'detail.table.*.answertype_line'  => [
            'label'             => 'tipe jawaban',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu',
            ]
        ]
    ];

    public $exit_interview = [
        'reference_id'                  => [
            'label'             => 'doc referensi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'md_employee_id'                  => [
            'label'             => 'karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'nik'                  => [
            'label'             => 'nik',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'md_branch_id'                  => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'md_division_id'                  => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'md_position_id'                  => [
            'label'             => 'jabatan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'terminatedate'                  => [
            'label'             => 'Tanggal Berhenti',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'detail.table.*.answer_line'  => [
            'label'             => 'Jawaban',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu',
            ]
        ]
    ];

    public $monitor_probation = [
        'category'                  => [
            'label'             => 'Bulan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'md_employee_id'                  => [
            'label'             => 'karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'nik'                  => [
            'label'             => 'nik',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'md_branch_id'                  => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'md_division_id'                  => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'md_position_id'                  => [
            'label'             => 'jabatan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'registerdate'                  => [
            'label'             => 'Tanggal Bergabung',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ]
    ];

    public $evaluasi_probation = [
        'md_employee_id'                  => [
            'label'             => 'karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'nik'                  => [
            'label'             => 'nik',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'md_branch_id'                  => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'md_division_id'                  => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'md_position_id'                  => [
            'label'             => 'jabatan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'registerdate'                  => [
            'label'             => 'Tanggal Bergabung',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'probation_enddate'     => [
            'label'             => 'Tanggal Selesai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'feedback'     => [
            'label'             => 'Feedback',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'notes'     => [
            'label'             => 'Catatan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'passed'     => [
            'label'             => 'passed',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
    ];

    public $benefit = [
        'name'                  => [
            'label'             => 'name',
            'rules'             => 'required|is_unique[md_benefit.name,md_benefit_id,{id}]',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
                'is_unique'     => 'Data {field} ini sudah ada'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'md_division_id'        => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'md_levelling_id'       => [
            'label'             => 'level',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
        'md_position_id'        => [
            'label'             => 'jabatan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
            ]
        ],
    ];

    public $benefit_line = [
        'line'                  => [
            'label'             => 'benefit line',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.benefit_line'  => [
            'label'             => 'benefit',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.status_line'  => [
            'label'             => 'status',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.isdetail_line'  => [
            'label'             => 'detail',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ]
    ];

    public $benefit_value = [
        'line'                  => [
            'label'             => 'benefit line',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ],
        'detail.table.*.benefit_detail_line'  => [
            'label'             => 'benefit detail',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi detail {field} dahulu'
            ]
        ]
    ];

    public $TugasKantorAddRow = [
        'md_branch_id'          => [
            'label'             => 'Cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'md_division_id'        => [
            'label'             => 'Divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
    ];

    public $penugasan = [
        'md_employee_id'        => [
            'label'             => 'pemohon',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_division_id'        => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'branch_in'          => [
            'label'             => 'cabang absen masuk',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'branch_out'          => [
            'label'             => 'cabang absen pulang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'startdate'             => [
            'label'             => 'tanggal mulai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'enddate'               => [
            'label'             => 'tanggal selesai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'reason'                => [
            'label'             => 'alasan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'line'                  => [
            'label'             => 'Detail Penugasan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu.'
            ]
        ],
        'detail.table.*.md_employee_id_line'  => [
            'label'             => 'Karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
    ];

    public $realisasi_agree_penugasan = [
        'submissiondate'        => [
            'label'             => 'Tanggal Tidak Masuk',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'branch_in'          => [
            'label'             => 'cabang absen masuk',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'branch_out'          => [
            'label'             => 'cabang absen pulang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        // 'starttime_att'          => [
        //     'label'             => 'absen masuk',
        //     'rules'             => 'required',
        //     'errors'            => [
        //         'required'      => 'Mohon mengisi {field} dahulu'
        //     ]
        // ],
        // 'endtime_att'          => [
        //     'label'             => 'absen pulang',
        //     'rules'             => 'required',
        //     'errors'            => [
        //         'required'      => 'Mohon mengisi {field} dahulu'
        //     ]
        // ],
    ];

    public $attendance_machine = [
        'name'          => [
            'label'             => 'Nama',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'serialnumber'        => [
            'label'             => 'Nomor Seri',
            'rules'             => 'required|is_unique[md_attendance_machines.serialnumber,md_attendance_machines_id,{id}]',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu',
                'is_unique'     => 'Data {field} ini sudah ada'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'Cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $config = [
        'auto_reject_approval'          => [
            'label'             => 'Auto Reject Approval',
            'rules'             => 'required|max_length[2]|',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu',
                'max_length'    => 'Maksimal {field} harus {param} karakter',
            ]
        ],
        'auto_approve_realization'        => [
            'label'             => 'Auto Approve Realisasi',
            'rules'             => 'required|max_length[2]|',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu',
                'max_length'    => 'Maksimal {field} harus {param} karakter',
            ]
        ],
        'day_cut_off_leave'     => [
            'label'             => 'Tanggal Cut Off Cuti',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];

    public $proxy_special = [
        'sys_user_from'          => [
            'label'             => 'Pengguna',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'sys_user_to'          => [
            'label'             => 'Wakil',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'startdate'                 => [
            'label'            => 'Tanggal Mulai',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'enddate'                 => [
            'label'            => 'Tanggal Selesai',
            'rules'            => 'required_based_on_checkbox[ispermanent,N]',
            'errors'        => [
                'required_based_on_checkbox'    => 'Mohon mengisi {field} dahulu'
            ]
        ],

        'line'                  => [
            'label'             => 'List Role',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu.'
            ]
        ],
        'detail.table.*.sys_role_id_line'  => [
            'label'             => 'Role',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Please Insert the {field} Line'
            ]
        ]

    ];

    public $medical_certificate = [
        'trx_absent_id'          => [
            'label'             => 'No Sakit',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'md_employee_id'          => [
            'label'             => 'Karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'Cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'md_division_id'          => [
            'label'             => 'Divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'date'                 => [
            'label'            => 'Tanggal',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'reason'                => [
            'label'            => 'Alasan',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
    ];

    public $transfer_duta = [
        'employee_from'          => [
            'label'             => 'Duta Awal',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'employee_to'          => [
            'label'             => 'Duta Tujuan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'Cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'md_division_id'          => [
            'label'             => 'Divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'startdate'                 => [
            'label'            => 'Tanggal Mulai Pengalihan',
            'rules'            =>    'required',
            'errors'        => [
                'required'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'enddate'                 => [
            'label'            => 'Tanggal Pengalihan Berakhir',
            'rules'            => 'required_based_on_checkbox[ispermanent,N]',
            'errors'        => [
                'required_based_on_checkbox'    => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'line'                  => [
            'label'             => 'List Karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu.'
            ]
        ],
        'detail.table.*.md_employee_id_line'  => [
            'label'             => 'Karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Please Insert the {field} Line'
            ]
        ]
    ];

    public $ijin_resmi = [
        'md_employee_id'        => [
            'label'             => 'karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_division_id'        => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'startdate'             => [
            'label'             => 'tanggal mulai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'enddate'               => [
            'label'             => 'tanggal selesai',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'reason'                => [
            'label'             => 'alasan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'image'                 => [
            'label'             => 'foto',
            'rules'             => 'uploaded[image]|max_size[image, 3024]|is_image[image]|mime_in[image,image/jpg,image/jpeg,image/png]',
            'errors'            => [
                'uploaded'      => 'Mohon upload {field} dahulu',
                'max_size'      => 'Data {field} melebihi batas maksimum 3 Mb',
                'is_image'      => 'Format file {field} salah',
                'mime_in'       => 'Format file {field} wajib {param}',
            ]
        ],
    ];

    public $news = [
        'md_employee_id'          => [
            'label'             => 'Duta Karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ],
        'date'          => [
            'label'             => 'Tanggal todal masuk',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon pilih {field} dahulu'
            ]
        ]
    ];

    public $penyesuaian = [
        'md_employee_id'        => [
            'label'             => 'karyawan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'submissiontype'        => [
            'label'             => 'Tipe Form',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_branch_id'          => [
            'label'             => 'cabang',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'md_division_id'        => [
            'label'             => 'divisi',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'date'             => [
            'label'             => 'tanggal',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'adjustment'             => [
            'label'             => 'nilai penyesuaian',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ],
        'reason'                => [
            'label'             => 'alasan',
            'rules'             => 'required',
            'errors'            => [
                'required'      => 'Mohon mengisi {field} dahulu'
            ]
        ]
    ];
}
