<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.

$routes->get('/', 'Backend\Dashboard::index', ['filter' => 'auth']);

$routes->get('auth', 'Backend\Auth::index', ['filter' => 'auth']);

$routes->post('auth/login', 'Backend\Auth::login');

$routes->get('logout', 'Backend\Auth::logout');

$routes->post('(:any)/AccessMenu/getAccess', 'Backend\AccessMenu::getAccess');

$routes->group('sas', ['filter' => 'auth'], function ($routes) {
    $routes->add('/', 'Backend\Dashboard::index');

    $routes->post('(:any)/accessmenu/getAccess', 'Backend\AccessMenu::getAccess');

    $routes->post('auth/changePassword', 'Backend\Auth::changePassword');

    $routes->add('user', 'Backend\User::index');
    $routes->match(['get', 'post'], 'user/showAll', 'Backend\User::showAll');
    $routes->post('user/create', 'Backend\User::create');
    $routes->get('user/show', 'Backend\User::show');
    $routes->get('user/show/(:any)', 'Backend\User::show/$1');
    $routes->get('user/destroy/(:any)', 'Backend\User::destroy/$1');
    $routes->match(['get', 'post'], 'user/getList', 'Backend\User::getList');

    $routes->add('role', 'Backend\Role::index');
    $routes->match(['get', 'post'], 'role/showAll', 'Backend\Role::showAll');
    $routes->post('role/create', 'Backend\Role::create');
    $routes->get('role/show/(:any)', 'Backend\Role::show/$1');
    $routes->get('role/destroy/(:any)', 'Backend\Role::destroy/$1');
    $routes->post('role/getUserRoleName', 'Backend\Role::getUserRoleName');
    $routes->match(['get', 'post'], 'role/getList', 'Backend\Role::getList');
    $routes->post('role/tableLine', 'Backend\Role::tableLine');
    $routes->get('role/destroyLine/(:any)', 'Backend\Role::destroyLine/$1');
    $routes->post('docaction/getDocaction', 'Backend\DocAction::getDocaction');

    $routes->add('menu', 'Backend\Menu::index');
    $routes->match(['get', 'post'], 'menu/showAll', 'Backend\Menu::showAll');
    $routes->post('menu/create', 'Backend\Menu::create');
    $routes->get('menu/show/(:any)', 'Backend\Menu::show/$1');
    $routes->get('menu/destroy/(:any)', 'Backend\Menu::destroy/$1');
    $routes->match(['get', 'post'], 'menu/getList', 'Backend\Menu::getList');

    $routes->add('submenu', 'Backend\Submenu::index');
    $routes->match(['get', 'post'], 'submenu/showAll', 'Backend\Submenu::showAll');
    $routes->post('submenu/create', 'Backend\Submenu::create');
    $routes->get('submenu/show/(:any)', 'Backend\Submenu::show/$1');
    $routes->get('submenu/destroy/(:any)', 'Backend\Submenu::destroy/$1');
    $routes->match(['get', 'post'], 'submenu/getList', 'Backend\Submenu::getList');

    $routes->add('branch', 'Backend\Branch::index');
    $routes->match(['get', 'post'], 'branch/showAll', 'Backend\Branch::showAll');
    $routes->post('branch/create', 'Backend\Branch::create');
    $routes->get('branch/show/(:any)', 'Backend\Branch::show/$1');
    $routes->get('branch/destroy/(:any)', 'Backend\Branch::destroy/$1');
    $routes->get('branch/getSeqCode', 'Backend\Branch::getSeqCode');
    $routes->match(['get', 'post'], 'branch/getList', 'Backend\Branch::getList');

    $routes->add('reference', 'Backend\Reference::index');
    $routes->match(['get', 'post'], 'reference/showAll', 'Backend\Reference::showAll');
    $routes->post('reference/create', 'Backend\Reference::create');
    $routes->get('reference/show/(:any)', 'Backend\Reference::show/$1');
    $routes->get('reference/destroy/(:any)', 'Backend\Reference::destroy/$1');
    $routes->post('reference/tableLine', 'Backend\Reference::tableLine');
    $routes->match(['get', 'post'], 'reference/getList', 'Backend\Reference::getList');

    $routes->add('division', 'Backend\Division::index');
    $routes->match(['get', 'post'], 'division/showAll', 'Backend\Division::showAll');
    $routes->post('division/create', 'Backend\Division::create');
    $routes->get('division/show/(:any)', 'Backend\Division::show/$1');
    $routes->get('division/destroy/(:any)', 'Backend\Division::destroy/$1');
    $routes->get('division/getSeqCode', 'Backend\Division::getSeqCode');
    $routes->match(['get', 'post'], 'division/getList', 'Backend\Division::getList');

    $routes->add('religion', 'Backend\Religion::index');
    $routes->match(['get', 'post'], 'religion/showAll', 'Backend\Religion::showAll');
    $routes->post('religion/create', 'Backend\Religion::create');
    $routes->get('religion/show/(:any)', 'Backend\Religion::show/$1');
    $routes->get('religion/destroy/(:any)', 'Backend\Religion::destroy/$1');
    $routes->get('religion/getSeqCode', 'Backend\Religion::getSeqCode');
    $routes->match(['get', 'post'], 'religion/getList', 'Backend\Religion::getList');

    $routes->add('country', 'Backend\Country::index');
    $routes->match(['get', 'post'], 'country/showAll', 'Backend\Country::showAll');
    $routes->post('country/create', 'Backend\Country::create');
    $routes->get('country/show/(:any)', 'Backend\Country::show/$1');
    $routes->get('country/destroy/(:any)', 'Backend\Country::destroy/$1');
    $routes->get('country/getSeqCode', 'Backend\Country::getSeqCode');
    $routes->match(['get', 'post'], 'country/getList', 'Backend\Country::getList');

    $routes->add('bloodtype', 'Backend\BloodType::index');
    $routes->match(['get', 'post'], 'bloodtype/showAll', 'Backend\BloodType::showAll');
    $routes->post('bloodtype/create', 'Backend\BloodType::create');
    $routes->get('bloodtype/show/(:any)', 'Backend\BloodType::show/$1');
    $routes->get('bloodtype/destroy/(:any)', 'Backend\BloodType::destroy/$1');
    $routes->get('bloodtype/getSeqCode', 'Backend\BloodType::getSeqCode');
    $routes->match(['get', 'post'], 'bloodtype/getList', 'Backend\BloodType::getList');

    $routes->add('position', 'Backend\Position::index');
    $routes->match(['get', 'post'], 'position/showAll', 'Backend\Position::showAll');
    $routes->post('position/create', 'Backend\Position::create');
    $routes->get('position/show/(:any)', 'Backend\Position::show/$1');
    $routes->get('position/destroy/(:any)', 'Backend\Position::destroy/$1');
    $routes->get('position/getSeqCode', 'Backend\Position::getSeqCode');
    $routes->match(['get', 'post'], 'position/getList', 'Backend\Position::getList');

    $routes->add('status', 'Backend\Status::index');
    $routes->match(['get', 'post'], 'status/showAll', 'Backend\Status::showAll');
    $routes->post('status/create', 'Backend\Status::create');
    $routes->get('status/show/(:any)', 'Backend\Status::show/$1');
    $routes->get('status/destroy/(:any)', 'Backend\Status::destroy/$1');
    $routes->get('status/getSeqCode', 'Backend\Status::getSeqCode');
    $routes->match(['get', 'post'], 'status/getList', 'Backend\Status::getList');

    $routes->add('province', 'Backend\Province::index');
    $routes->match(['get', 'post'], 'province/showAll', 'Backend\Province::showAll');
    $routes->post('province/create', 'Backend\Province::create');
    $routes->get('province/show/(:any)', 'Backend\Province::show/$1');
    $routes->get('province/destroy/(:any)', 'Backend\Province::destroy/$1');
    $routes->get('province/getSeqCode', 'Backend\Province::getSeqCode');
    $routes->match(['get', 'post'], 'province/getList', 'Backend\Province::getList');

    $routes->add('city', 'Backend\City::index');
    $routes->match(['get', 'post'], 'city/showAll', 'Backend\City::showAll');
    $routes->post('city/create', 'Backend\City::create');
    $routes->get('city/show/(:any)', 'Backend\City::show/$1');
    $routes->get('city/destroy/(:any)', 'Backend\City::destroy/$1');
    $routes->get('city/getSeqCode', 'Backend\City::getSeqCode');
    $routes->match(['get', 'post'], 'city/getList', 'Backend\City::getList');

    $routes->add('district', 'Backend\District::index');
    $routes->match(['get', 'post'], 'district/showAll', 'Backend\District::showAll');
    $routes->post('district/create', 'Backend\District::create');
    $routes->get('district/show/(:any)', 'Backend\District::show/$1');
    $routes->get('district/destroy/(:any)', 'Backend\District::destroy/$1');
    $routes->get('district/getSeqCode', 'Backend\District::getSeqCode');
    $routes->match(['get', 'post'], 'district/getList', 'Backend\District::getList');

    $routes->add('subdistrict', 'Backend\SubDistrict::index');
    $routes->match(['get', 'post'], 'subdistrict/showAll', 'Backend\SubDistrict::showAll');
    $routes->post('subdistrict/create', 'Backend\SubDistrict::create');
    $routes->get('subdistrict/show/(:any)', 'Backend\SubDistrict::show/$1');
    $routes->get('subdistrict/destroy/(:any)', 'Backend\SubDistrict::destroy/$1');
    $routes->get('subdistrict/getSeqCode', 'Backend\SubDistrict::getSeqCode');
    $routes->match(['get', 'post'], 'subdistrict/getList', 'Backend\SubDistrict::getList');

    $routes->add('levelling', 'Backend\Levelling::index');
    $routes->match(['get', 'post'], 'levelling/showAll', 'Backend\Levelling::showAll');
    $routes->post('levelling/create', 'Backend\Levelling::create');
    $routes->get('levelling/show/(:any)', 'Backend\Levelling::show/$1');
    $routes->get('levelling/destroy/(:any)', 'Backend\Levelling::destroy/$1');
    $routes->get('levelling/getSeqCode', 'Backend\Levelling::getSeqCode');
    $routes->match(['get', 'post'], 'levelling/getList', 'Backend\Levelling::getList');

    $routes->match(['get', 'post'], 'karyawan/getDetail', 'Backend\Employee::getDetailEmployee');
    $routes->match(['get', 'post'], 'employee/getList', 'Backend\Employee::getList');

    $routes->add('sakit', 'Backend\SickLeave::index');
    $routes->match(['get', 'post'], 'sakit/showAll', 'Backend\SickLeave::showAll');
    $routes->post('sakit/create', 'Backend\SickLeave::create');
    $routes->get('sakit/show/(:any)', 'Backend\SickLeave::show/$1');
    $routes->get('sakit/destroy/(:any)', 'Backend\SickLeave::destroy/$1');
    $routes->get('sakit/processIt', 'Backend\SickLeave::processIt');

    $routes->add('cuti', 'Backend\Leave::index');
    $routes->match(['get', 'post'], 'cuti/showAll', 'Backend\Leave::showAll');
    $routes->post('cuti/create', 'Backend\Leave::create');
    $routes->get('cuti/show/(:any)', 'Backend\Leave::show/$1');
    $routes->get('cuti/destroy/(:any)', 'Backend\Leave::destroy/$1');
    $routes->get('cuti/processIt', 'Backend\Leave::processIt');

    $routes->add('ijin-resmi', 'Backend\OfficialPermission::index');
    $routes->match(['get', 'post'], 'ijin-resmi/showAll', 'Backend\OfficialPermission::showAll');
    $routes->post('ijin-resmi/create', 'Backend\OfficialPermission::create');
    $routes->get('ijin-resmi/show/(:any)', 'Backend\OfficialPermission::show/$1');
    $routes->get('ijin-resmi/destroy/(:any)', 'Backend\OfficialPermission::destroy/$1');
    $routes->get('ijin-resmi/processIt', 'Backend\OfficialPermission::processIt');
    $routes->post('ijin-resmi/getEndDate', 'Backend\OfficialPermission::getEndDate');

    $routes->add('alpa', 'Backend\Alpha::index');
    $routes->match(['get', 'post'], 'alpa/showAll', 'Backend\Alpha::showAll');
    $routes->post('alpa/create', 'Backend\Alpha::create');
    $routes->get('alpa/show/(:any)', 'Backend\Alpha::show/$1');
    $routes->get('alpa/destroy/(:any)', 'Backend\Alpha::destroy/$1');
    $routes->get('alpa/processIt', 'Backend\Alpha::processIt');

    $routes->add('datang-terlambat', 'Backend\PermissionArrived::index');
    $routes->match(['get', 'post'], 'datang-terlambat/showAll', 'Backend\PermissionArrived::showAll');
    $routes->post('datang-terlambat/create', 'Backend\PermissionArrived::create');
    $routes->get('datang-terlambat/show/(:any)', 'Backend\PermissionArrived::show/$1');
    $routes->get('datang-terlambat/destroy/(:any)', 'Backend\PermissionArrived::destroy/$1');
    $routes->get('datang-terlambat/processIt', 'Backend\PermissionArrived::processIt');

    $routes->add('lupa-absen-masuk', 'Backend\ForgotAbsentLeave::index');
    $routes->match(['get', 'post'], 'lupa-absen-masuk/showAll', 'Backend\ForgotAbsentLeave::showAll');
    $routes->post('lupa-absen-masuk/create', 'Backend\ForgotAbsentLeave::create');
    $routes->get('lupa-absen-masuk/show/(:any)', 'Backend\ForgotAbsentLeave::show/$1');
    $routes->get('lupa-absen-masuk/destroy/(:any)', 'Backend\ForgotAbsentLeave::destroy/$1');
    $routes->get('lupa-absen-masuk/processIt', 'Backend\ForgotAbsentLeave::processIt');

    $routes->add('lupa-absen-pulang', 'Backend\ForgotAbsentArrive::index');
    $routes->match(['get', 'post'], 'lupa-absen-pulang/showAll', 'Backend\ForgotAbsentArrive::showAll');
    $routes->post('lupa-absen-pulang/create', 'Backend\ForgotAbsentArrive::create');
    $routes->get('lupa-absen-pulang/show/(:any)', 'Backend\ForgotAbsentArrive::show/$1');
    $routes->get('lupa-absen-pulang/destroy/(:any)', 'Backend\ForgotAbsentArrive::destroy/$1');
    $routes->get('lupa-absen-pulang/processIt', 'Backend\ForgotAbsentArrive::processIt');

    $routes->add('laporan-potongan-tkh', 'Backend\AllowanceAtt::reportIndex');
    $routes->match(['get', 'post'], 'laporan-potongan-tkh/showAll', 'Backend\AllowanceAtt::reportShowAll');

    $routes->add('karyawan', 'Backend\Employee::index');
    $routes->match(['get', 'post'], 'karyawan/showAll', 'Backend\Employee::showAll');
    $routes->get('karyawan/getDataBy/(:num)', 'Backend\Employee::getBy/$1');
    $routes->match(['get', 'post'], 'karyawan/getDetail', 'Backend\Employee::getDetailEmployee');
    $routes->match(['get', 'post'], 'karyawan/getList', 'Backend\Employee::getList');
    $routes->match(['get', 'post'], 'karyawan/superior', 'Backend\Employee::getSuperior');
    $routes->get('karyawan/destroy/(:any)', 'Backend\Employee::destroy/$1');
    $routes->post('karyawan/create', 'Backend\Employee::create');
    $routes->get('karyawan/show/(:any)', 'Backend\Employee::show/$1');

    $routes->post('keluarga-inti/create', 'Backend\EmpFamilyCore::create');
    $routes->get('keluarga-inti/show', 'Backend\EmpFamilyCore::show');
    $routes->get('keluarga-inti/show/(:any)', 'Backend\EmpFamilyCore::show/$1');
    $routes->post('keluarga-inti/tableLine', 'Backend\EmpFamilyCore::tableLine');

    $routes->post('keluarga/create', 'Backend\EmpFamily::create');
    $routes->get('keluarga/show', 'Backend\EmpFamily::show');
    $routes->get('keluarga/show/(:any)', 'Backend\EmpFamily::show/$1');
    $routes->post('keluarga/tableLine', 'Backend\EmpFamily::tableLine');

    $routes->post('riwayat-pendidikan/create', 'Backend\EmpEducation::create');
    $routes->get('riwayat-pendidikan/show', 'Backend\EmpEducation::show');
    $routes->get('riwayat-pendidikan/show/(:any)', 'Backend\EmpEducation::show/$1');
    $routes->post('riwayat-pendidikan/tableLine', 'Backend\EmpEducation::tableLine');

    $routes->post('riwayat-pekerjaan/create', 'Backend\EmpJob::create');
    $routes->get('riwayat-pekerjaan/show', 'Backend\EmpJob::show');
    $routes->get('riwayat-pekerjaan/show/(:any)', 'Backend\EmpJob::show/$1');
    $routes->post('riwayat-pekerjaan/tableLine', 'Backend\EmpJob::tableLine');

    $routes->post('riwayat-vaksin/tableLine', 'Backend\EmpVaccine::tableLine');
    $routes->post('riwayat-vaksin/create', 'Backend\EmpVaccine::create');
    $routes->get('riwayat-vaksin/show', 'Backend\EmpVaccine::show');
    $routes->get('riwayat-vaksin/show/(:any)', 'Backend\EmpVaccine::show/$1');

    $routes->post('keterampilan/tableLine', 'Backend\EmpSkill::tableLine');
    $routes->post('keterampilan/create', 'Backend\EmpSkill::create');
    $routes->get('keterampilan/show', 'Backend\EmpSkill::show');
    $routes->get('keterampilan/show/(:any)', 'Backend\EmpSkill::show/$1');

    $routes->post('kursus/tableLine', 'Backend\EmpCourse::tableLine');
    $routes->post('kursus/create', 'Backend\EmpCourse::create');
    $routes->get('kursus/show', 'Backend\EmpCourse::show');
    $routes->get('kursus/show/(:any)', 'Backend\EmpCourse::show/$1');

    $routes->post('penguasaan-bahasa/tableLine', 'Backend\EmpLanguage::tableLine');
    $routes->post('penguasaan-bahasa/create', 'Backend\EmpLanguage::create');
    $routes->get('penguasaan-bahasa/show', 'Backend\EmpLanguage::show');
    $routes->get('penguasaan-bahasa/show/(:any)', 'Backend\EmpLanguage::show/$1');

    $routes->post('kontak-darurat/tableLine', 'Backend\EmpContact::tableLine');
    $routes->post('kontak-darurat/create', 'Backend\EmpContact::create');
    $routes->get('kontak-darurat/show', 'Backend\EmpContact::show');
    $routes->get('kontak-darurat/show/(:any)', 'Backend\EmpContact::show/$1');

    $routes->post('sim/tableLine', 'Backend\EmpLicense::tableLine');
    $routes->post('sim/create', 'Backend\EmpLicense::create');
    $routes->get('sim/show', 'Backend\EmpLicense::show');
    $routes->get('sim/show/(:any)', 'Backend\EmpLicense::show/$1');

    $routes->post('kontak-darurat/tableLine', 'Backend\EmergencyContact::tableLine');
    $routes->post('kontak-darurat/create', 'Backend\EmergencyContact::create');
    $routes->get('kontak-darurat/show', 'Backend\EmergencyContact::show');
    $routes->get('kontak-darurat/show/(:any)', 'Backend\EmergencyContact::show/$1');
  
    $routes->add('pulang-cepat', 'Backend\PermissionLeaveEarly::index');
    $routes->match(['get', 'post'], 'pulang-cepat/showAll', 'Backend\PermissionLeaveEarly::showAll');
    $routes->post('pulang-cepat/create', 'Backend\PermissionLeaveEarly::create');
    $routes->get('pulang-cepat/show/(:any)', 'Backend\PermissionLeaveEarly::show/$1');
    $routes->get('pulang-cepat/destroy/(:any)', 'Backend\PermissionLeaveEarly::destroy/$1');
    $routes->get('pulang-cepat/processIt', 'Backend\PermissionLeaveEarly::processIt');

    $routes->add('tugas-kantor', 'Backend\OfficeDuties::index');
    $routes->match(['get', 'post'], 'tugas-kantor/showAll', 'Backend\OfficeDuties::showAll');
    $routes->post('tugas-kantor/create', 'Backend\OfficeDuties::create');
    $routes->get('tugas-kantor/show/(:any)', 'Backend\OfficeDuties::show/$1');
    $routes->get('tugas-kantor/destroy/(:any)', 'Backend\OfficeDuties::destroy/$1');
    $routes->get('tugas-kantor/processIt', 'Backend\OfficeDuties::processIt');

    $routes->add('tugas-kantor-fka', 'Backend\HalfDayOfficeDuties::index');
    $routes->match(['get', 'post'], 'tugas-kantor-fka/showAll', 'Backend\HalfDayOfficeDuties::showAll');
    $routes->post('tugas-kantor-fka/create', 'Backend\HalfDayOfficeDuties::create');
    $routes->get('tugas-kantor-fka/show/(:any)', 'Backend\HalfDayOfficeDuties::show/$1');
    $routes->get('tugas-kantor-fka/destroy/(:any)', 'Backend\HalfDayOfficeDuties::destroy/$1');
    $routes->get('tugas-kantor-fka/processIt', 'Backend\HalfDayOfficeDuties::processIt');

    $routes->add('izin-resmi', 'Backend\OfficialPermission::index');
    $routes->match(['get', 'post'], 'izin-resmi/showAll', 'Backend\OfficialPermission::showAll');
    $routes->post('izin-resmi/create', 'Backend\OfficialPermission::create');
    $routes->get('izin-resmi/show/(:any)', 'Backend\OfficialPermission::show/$1');
    $routes->get('izin-resmi/destroy/(:any)', 'Backend\OfficialPermission::destroy/$1');
    $routes->get('izin-resmi/processIt', 'Backend\OfficialPermission::processIt');

    $routes->add('lain-lain', 'Backend\OtherPermission::index');
    $routes->match(['get', 'post'], 'lain-lain/showAll', 'Backend\OtherPermission::showAll');
    $routes->post('lain-lain/create', 'Backend\OtherPermission::create');
    $routes->get('lain-lain/show/(:any)', 'Backend\OtherPermission::show/$1');
    $routes->get('lain-lain/destroy/(:any)', 'Backend\OtherPermission::destroy/$1');
    $routes->get('lain-lain/processIt', 'Backend\OtherPermission::processIt');

    $routes->add('day', 'Backend\Day::index');
    $routes->match(['get', 'post'], 'day/showAll', 'Backend\Day::showAll');
    $routes->post('day/create', 'Backend\Day::create');
    $routes->get('day/show/(:any)', 'Backend\Day::show/$1');
    $routes->get('day/destroy/(:any)', 'Backend\Day::destroy/$1');
    $routes->get('day/getSeqCode', 'Backend\Day::getSeqCode');
    $routes->match(['get', 'post'], 'day/getList', 'Backend\Day::getList');

    $routes->add('holiday', 'Backend\Holiday::index');
    $routes->match(['get', 'post'], 'holiday/showAll', 'Backend\Holiday::showAll');
    $routes->post('holiday/create', 'Backend\Holiday::create');
    $routes->get('holiday/show/(:any)', 'Backend\Holiday::show/$1');
    $routes->get('holiday/destroy/(:any)', 'Backend\Holiday::destroy/$1');
    $routes->match(['get', 'post'], 'holiday/getList', 'Backend\Holiday::getList');
    $routes->get('holiday/getHolidayDate', 'Backend\Holiday::getHolidayDate');

    $routes->add('leavetype', 'Backend\LeaveType::index');
    $routes->match(['get', 'post'], 'leavetype/showAll', 'Backend\LeaveType::showAll');
    $routes->post('leavetype/create', 'Backend\LeaveType::create');
    $routes->get('leavetype/show/(:any)', 'Backend\LeaveType::show/$1');
    $routes->get('leavetype/destroy/(:any)', 'Backend\LeaveType::destroy/$1');
    $routes->get('leavetype/getSeqCode', 'Backend\LeaveType::getSeqCode');
    $routes->match(['get', 'post'], 'leavetype/getList', 'Backend\LeaveType::getList');

    $routes->add('skill', 'Backend\Skill::index');
    $routes->match(['get', 'post'], 'skill/showAll', 'Backend\Skill::showAll');
    $routes->post('skill/create', 'Backend\Skill::create');
    $routes->get('skill/show/(:any)', 'Backend\Skill::show/$1');
    $routes->get('skill/destroy/(:any)', 'Backend\Skill::destroy/$1');
    $routes->get('skill/getSeqCode', 'Backend\Skill::getSeqCode');
    $routes->match(['get', 'post'], 'skill/getList', 'Backend\Skill::getList');

    $routes->add('massleave', 'Backend\MassLeave::index');
    $routes->match(['get', 'post'], 'massleave/showAll', 'Backend\MassLeave::showAll');
    $routes->post('massleave/create', 'Backend\MassLeave::create');
    $routes->get('massleave/show/(:any)', 'Backend\MassLeave::show/$1');
    $routes->get('massleave/destroy/(:any)', 'Backend\MassLeave::destroy/$1');
    $routes->match(['get', 'post'], 'massleave/getList', 'Backend\MassLeave::getList');

    $routes->add('laporan-tkh', 'Backend\AllowanceAtt::reportIndex');
    $routes->match(['get', 'post'], 'laporan-tkh/showAll', 'Backend\AllowanceAtt::reportShowAll');
});

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}