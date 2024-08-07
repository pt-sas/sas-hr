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

$routes->post('(:any)/accessmenu/getAccess', 'Backend\AccessMenu::getAccess');

$routes->get('cron-not-approved', 'Backend\WActivity::doNotApproved');
$routes->get('/iclock/cdata', 'IclockApi::handshake');
$routes->post('/iclock/cdata', 'IclockApi::receive');

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

    $routes->add('sakit', 'Backend\SickLeave::index');
    $routes->match(['get', 'post'], 'sakit/showAll', 'Backend\SickLeave::showAll');
    $routes->post('sakit/create', 'Backend\SickLeave::create');
    $routes->get('sakit/show/(:any)', 'Backend\SickLeave::show/$1');
    $routes->get('sakit/destroy/(:any)', 'Backend\SickLeave::destroy/$1');
    $routes->get('sakit/processIt', 'Backend\SickLeave::processIt');
    $routes->get('sakit/print/(:any)', 'Backend\SickLeave::exportPDF/$1');

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
    $routes->post('alpa/generate', 'Backend\Alpha::generateAlpa');

    $routes->add('datang-terlambat', 'Backend\PermissionArrived::index');
    $routes->match(['get', 'post'], 'datang-terlambat/showAll', 'Backend\PermissionArrived::showAll');
    $routes->post('datang-terlambat/create', 'Backend\PermissionArrived::create');
    $routes->get('datang-terlambat/show/(:any)', 'Backend\PermissionArrived::show/$1');
    $routes->get('datang-terlambat/destroy/(:any)', 'Backend\PermissionArrived::destroy/$1');
    $routes->get('datang-terlambat/processIt', 'Backend\PermissionArrived::processIt');
    $routes->get('datang-terlambat/print/(:any)', 'Backend\PermissionArrived::exportPDF/$1');

    $routes->add('lupa-absen-masuk', 'Backend\ForgotAbsentArrive::index');
    $routes->match(['get', 'post'], 'lupa-absen-masuk/showAll', 'Backend\ForgotAbsentArrive::showAll');
    $routes->post('lupa-absen-masuk/create', 'Backend\ForgotAbsentArrive::create');
    $routes->get('lupa-absen-masuk/show/(:any)', 'Backend\ForgotAbsentArrive::show/$1');
    $routes->get('lupa-absen-masuk/destroy/(:any)', 'Backend\ForgotAbsentArrive::destroy/$1');
    $routes->get('lupa-absen-masuk/processIt', 'Backend\ForgotAbsentArrive::processIt');
    $routes->get('lupa-absen-masuk/print/(:any)', 'Backend\ForgotAbsentArrive::exportPDF/$1');

    $routes->add('lupa-absen-pulang', 'Backend\ForgotAbsentLeave::index');
    $routes->match(['get', 'post'], 'lupa-absen-pulang/showAll', 'Backend\ForgotAbsentLeave::showAll');
    $routes->post('lupa-absen-pulang/create', 'Backend\ForgotAbsentLeave::create');
    $routes->get('lupa-absen-pulang/show/(:any)', 'Backend\ForgotAbsentLeave::show/$1');
    $routes->get('lupa-absen-pulang/destroy/(:any)', 'Backend\ForgotAbsentLeave::destroy/$1');
    $routes->get('lupa-absen-pulang/processIt', 'Backend\ForgotAbsentLeave::processIt');
    $routes->get('lupa-absen-pulang/print/(:any)', 'Backend\ForgotAbsentLeave::exportPDF/$1');

    $routes->add('laporan-potongan-tkh', 'Backend\AllowanceAtt::reportIndex');
    $routes->match(['get', 'post'], 'laporan-potongan-tkh/showAll', 'Backend\AllowanceAtt::reportShowAll');

    $routes->add('karyawan', 'Backend\Employee::index');
    $routes->match(['get', 'post'], 'karyawan/showAll', 'Backend\Employee::showAll');
    $routes->get('karyawan/getDataBy/(:num)', 'Backend\Employee::getBy/$1');
    $routes->match(['get', 'post'], 'karyawan/getDetail', 'Backend\Employee::getDetailEmployee');
    $routes->match(['get', 'post'], 'employee/getList', 'Backend\Employee::getList');
    $routes->match(['get', 'post'], 'karyawan/getList', 'Backend\Employee::getList');
    $routes->match(['get', 'post'], 'karyawan/superior', 'Backend\Employee::getSuperior');
    $routes->get('karyawan/destroy/(:any)', 'Backend\Employee::destroy/$1');
    $routes->post('karyawan/create', 'Backend\Employee::create');
    $routes->get('karyawan/show/(:any)', 'Backend\Employee::show/$1');
    $routes->get('karyawan/get-nik', 'Backend\Employee::getNik');

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
    $routes->get('pulang-cepat/print/(:any)', 'Backend\PermissionLeaveEarly::exportPDF/$1');

    $routes->add('tugas-kantor', 'Backend\OfficeDuties::index');
    $routes->match(['get', 'post'], 'tugas-kantor/showAll', 'Backend\OfficeDuties::showAll');
    $routes->post('tugas-kantor/create', 'Backend\OfficeDuties::create');
    $routes->get('tugas-kantor/show/(:any)', 'Backend\OfficeDuties::show/$1');
    $routes->get('tugas-kantor/destroy/(:any)', 'Backend\OfficeDuties::destroy/$1');
    $routes->get('tugas-kantor/processIt', 'Backend\OfficeDuties::processIt');
    $routes->get('tugas-kantor/print/(:any)', 'Backend\OfficeDuties::exportPDF/$1');

    $routes->add('tugas-kantor-fka', 'Backend\HalfDayOfficeDuties::index');
    $routes->match(['get', 'post'], 'tugas-kantor-fka/showAll', 'Backend\HalfDayOfficeDuties::showAll');
    $routes->post('tugas-kantor-fka/create', 'Backend\HalfDayOfficeDuties::create');
    $routes->get('tugas-kantor-fka/show/(:any)', 'Backend\HalfDayOfficeDuties::show/$1');
    $routes->get('tugas-kantor-fka/destroy/(:any)', 'Backend\HalfDayOfficeDuties::destroy/$1');
    $routes->get('tugas-kantor-fka/processIt', 'Backend\HalfDayOfficeDuties::processIt');
    $routes->get('tugas-kantor-fka/print/(:any)', 'Backend\HalfDayOfficeDuties::exportPDF/$1');

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
    $routes->get('holiday/get-holiday', 'Backend\Holiday::getHolidayDate');

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

    $routes->add('ijin-keluar-kantor', 'Backend\PermissionLeaveOffice::index');
    $routes->match(['get', 'post'], 'ijin-keluar-kantor/showAll', 'Backend\PermissionLeaveOffice::showAll');
    $routes->post('ijin-keluar-kantor/create', 'Backend\PermissionLeaveOffice::create');
    $routes->get('ijin-keluar-kantor/show/(:any)', 'Backend\PermissionLeaveOffice::show/$1');
    $routes->get('ijin-keluar-kantor/destroy/(:any)', 'Backend\PermissionLeaveOffice::destroy/$1');
    $routes->match(['get', 'post'], 'ijin-keluar-kantor/getList', 'Backend\PermissionLeaveOffice::getList');
    $routes->get('ijin-keluar-kantor/print/(:any)', 'Backend\PermissionLeaveOffice::exportPDF/$1');
    $routes->get('ijin-keluar-kantor/processIt', 'Backend\PermissionLeaveOffice::processIt');


    $routes->add('ijin', 'Backend\Permission::index');
    $routes->match(['get', 'post'], 'ijin/showAll', 'Backend\Permission::showAll');
    $routes->post('ijin/create', 'Backend\Permission::create');
    $routes->get('ijin/show/(:any)', 'Backend\Permission::show/$1');
    $routes->get('ijin/destroy/(:any)', 'Backend\Permission::destroy/$1');
    $routes->match(['get', 'post'], 'ijin/getList', 'Backend\Permission::getList');
    $routes->get('ijin/processIt', 'Backend\Permission::processIt');
    $routes->get('ijin/print/(:any)', 'Backend\Permission::exportPDF/$1');

    $routes->add('laporan-tkh', 'Backend\AllowanceAtt::reportIndex');
    $routes->match(['get', 'post'], 'laporan-tkh/showAll', 'Backend\AllowanceAtt::reportShowAll');

    $routes->add('rule-inti', 'Backend\Rule::index');
    $routes->match(['get', 'post'], 'rule-inti/showAll', 'Backend\Rule::showAll');
    $routes->post('rule-inti/create', 'Backend\Rule::create');
    $routes->get('rule-inti/show/(:any)', 'Backend\Rule::show/$1');
    $routes->get('rule-inti/destroy/(:any)', 'Backend\Rule::destroy/$1');
    $routes->get('rule-inti/getDataBy/(:num)', 'Backend\Rule::getBy/$1');

    $routes->post('rule-detail/create', 'Backend\RuleDetail::create');
    $routes->get('rule-detail/show', 'Backend\RuleDetail::show');
    $routes->get('rule-detail/show/(:any)', 'Backend\RuleDetail::show/$1');
    $routes->post('rule-detail/tableLine', 'Backend\RuleDetail::tableLine');
    $routes->get('rule-detail/getDataBy/(:num)', 'Backend\RuleDetail::getBy/$1');

    $routes->post('rule-value/create', 'Backend\RuleValue::create');
    $routes->get('rule-value/show', 'Backend\RuleValue::show');
    $routes->get('rule-value/show/(:any)', 'Backend\RuleValue::show/$1');
    $routes->post('rule-value/tableLine', 'Backend\RuleValue::tableLine');

    $routes->add('responsible', 'Backend\Responsible::index');
    $routes->match(['get', 'post'], 'responsible/showAll', 'Backend\Responsible::showAll');
    $routes->post('responsible/create', 'Backend\Responsible::create');
    $routes->get('responsible/show/(:any)', 'Backend\Responsible::show/$1');
    $routes->get('responsible/destroy/(:any)', 'Backend\Responsible::destroy/$1');

    $routes->add('mail', 'Backend\Mail::index');
    $routes->match(['get', 'post'], 'mail/showAll', 'Backend\Mail::showAll');
    $routes->post('mail/create', 'Backend\Mail::create');
    $routes->post('mail/createTestEmail', 'Backend\Mail::createTestEmail');

    $routes->add('notifikasi-text', 'Backend\NotificationText::index');
    $routes->match(['get', 'post'], 'notifikasi-text/showAll', 'Backend\NotificationText::showAll');
    $routes->post('notifikasi-text/create', 'Backend\NotificationText::create');
    $routes->get('notifikasi-text/show/(:any)', 'Backend\NotificationText::show/$1');
    $routes->get('notifikasi-text/destroy/(:any)', 'Backend\NotificationText::destroy/$1');

    $routes->add('wscenario', 'Backend\WScenario::index');
    $routes->match(['get', 'post'], 'wscenario/showAll', 'Backend\WScenario::showAll');
    $routes->post('wscenario/create', 'Backend\WScenario::create');
    $routes->get('wscenario/show/(:any)', 'Backend\WScenario::show/$1');
    $routes->get('wscenario/destroy/(:any)', 'Backend\WScenario::destroy/$1');
    $routes->post('wscenario/tableLine', 'Backend\WScenario::tableLine');
    $routes->get('wscenario/destroyLine/(:any)', 'Backend\WScenario::destroyLine/$1');

    $routes->add('lembur', 'Backend\Overtime::index');
    $routes->match(['get', 'post'], 'lembur/showAll', 'Backend\Overtime::showAll');
    $routes->post('lembur/create', 'Backend\Overtime::create');
    $routes->get('lembur/show/(:any)', 'Backend\Overtime::show/$1');
    $routes->get('lembur/destroy/(:any)', 'Backend\Overtime::destroy/$1');
    $routes->get('lembur/processIt', 'Backend\Overtime::processIt');
    $routes->post('lembur/tableLine', 'Backend\Overtime::tableLine');
    $routes->match(['get', 'post'], 'lembur/print/(:any)', 'Backend\Overtime::exportPDF/$1');

    $routes->get('wactivity/showNotif', 'Backend\WActivity::showNotif');
    $routes->post('wactivity/create', 'Backend\WActivity::create');
    $routes->match(['get', 'post'], 'wactivity/showActivityInfo', 'Backend\WActivity::showActivityInfo');

    $routes->add('laporan-absensi', 'Backend\Rpt_AbsentSummary::reportIndex');
    $routes->match(['get', 'post'], 'laporan-absensi/showAll', 'Backend\Rpt_AbsentSummary::reportShowAll');

    $routes->add('laporan-kehadiran', 'Backend\Attendance::reportIndex');
    $routes->match(['get', 'post'], 'laporan-kehadiran/showAll', 'Backend\Attendance::reportShowAll');
    $routes->match(['get', 'post'], 'Kehadiran/getJamAbsen', 'Backend\Attendance::getClockInOut');

    $routes->add('laporan-kehadiran-new', 'Backend\AttendanceNew::reportIndex');
    $routes->match(['get', 'post'], 'laporan-kehadiran-new/showAll', 'Backend\AttendanceNew::reportShowAll');

    $routes->add('import-kehadiran', 'Backend\ImportAttendance::index');
    $routes->post('import-kehadiran/import', 'Backend\ImportAttendance::import');

    $routes->add('realisasi', 'Backend\Realization::index');
    $routes->match(['get', 'post'], 'realisasi/showAll', 'Backend\Realization::showAll');
    $routes->post('realisasi/create', 'Backend\Realization::create');
    $routes->match(['get', 'post'], 'realisasi/getList', 'Backend\Realization::getList');
    $routes->get('realisasi/show-image/(:any)', 'Backend\Realization::getImage/$1');

    $routes->add('list-absent', 'Backend\ListAbsent::index');
    $routes->post('list-absent/showAll', 'Backend\ListAbsent::showAll');
    $routes->post('list-absent/create', 'Backend\ListAbsent::create');

    $routes->add('laporan-saldo-tkh', 'Backend\AllowanceAtt::index');
    $routes->match(['get', 'post'], 'laporan-saldo-tkh/reportAll', 'Backend\AllowanceAtt::reportAll');

    $routes->add('realisasi-lembur', 'Backend\Realization::indexOvertime');
    $routes->match(['get', 'post'], 'realisasi-lembur/showAll', 'Backend\Realization::showAllOvertime');
    $routes->post('realisasi-lembur/create', 'Backend\Realization::createOvertime');

    $routes->add('realisasi-kehadiran', 'Backend\Realization::indexAttendance');
    $routes->match(['get', 'post'], 'realisasi-kehadiran/showAll', 'Backend\Realization::showAllAttendance');
    $routes->post('realisasi-kehadiran/create', 'Backend\Realization::createAttendance');

    $routes->add('hari-kerja', 'Backend\Work::index');
    $routes->match(['get', 'post'], 'hari-kerja/showAll', 'Backend\Work::showAll');
    $routes->post('hari-kerja/create', 'Backend\Work::create');
    $routes->get('hari-kerja/show/(:any)', 'Backend\Work::show/$1');
    $routes->get('hari-kerja/destroy/(:any)', 'Backend\Work::destroy/$1');
    $routes->post('hari-kerja/tableLine', 'Backend\Work::tableLine');
    $routes->get('work/get-days-off/(:any)', 'Backend\Work::daysOff/$1');

    $routes->post('hari-kerja-karyawan/tableLine', 'Backend\EmpWorkDay::tableLine');
    $routes->post('hari-kerja-karyawan/create', 'Backend\EmpWorkDay::create');
    $routes->get('hari-kerja-karyawan/show', 'Backend\EmpWorkDay::show');
    $routes->get('hari-kerja-karyawan/show/(:any)', 'Backend\EmpWorkDay::show/$1');

    $routes->add('tugas-kantor-khusus', 'Backend\SpecialOfficeDuties::index');
    $routes->match(['get', 'post'], 'tugas-kantor-khusus/showAll', 'Backend\SpecialOfficeDuties::showAll');
    $routes->post('tugas-kantor-khusus/create', 'Backend\SpecialOfficeDuties::create');
    $routes->get('tugas-kantor-khusus/show/(:any)', 'Backend\SpecialOfficeDuties::show/$1');
    $routes->get('tugas-kantor-khusus/destroy/(:any)', 'Backend\SpecialOfficeDuties::destroy/$1');
    $routes->get('tugas-kantor-khusus/processIt', 'Backend\SpecialOfficeDuties::processIt');
    $routes->get('tugas-kantor-khusus/print/(:any)', 'Backend\SpecialOfficeDuties::exportPDF/$1');

    $routes->add('document-type', 'Backend\DocumentType::index');
    $routes->match(['get', 'post'], 'document-type/showAll', 'Backend\DocumentType::showAll');
    $routes->post('document-type/create', 'Backend\DocumentType::create');
    $routes->get('document-type/show/(:any)', 'Backend\DocumentType::show/$1');
    $routes->get('document-type/destroy/(:any)', 'Backend\DocumentType::destroy/$1');
    $routes->match(['get', 'post'], 'document-type/getList', 'Backend\DocumentType::getList');

    $routes->add('request-anulir', 'Backend\RequestAnulir::index');
    $routes->post('request-anulir/create', 'Backend\RequestAnulir::create');

    $routes->add('laporan-saldo-cuti', 'Backend\Rpt_LeaveBalance::index');
    $routes->match(['get', 'post'], 'laporan-saldo-cuti/showAll', 'Backend\Rpt_LeaveBalance::showAll');

    $routes->add('laporan-saldo-cuti-summary', 'Backend\Rpt_LeaveBalance::indexSummary');
    $routes->match(['get', 'post'], 'laporan-saldo-cuti-summary/showAll', 'Backend\Rpt_LeaveBalance::showAllSummary');

    $routes->add('laporan-lembur', 'Backend\Rpt_Overtime::index');
    $routes->match(['get', 'post'], 'laporan-lembur/showAll', 'Backend\Rpt_Overtime::showAll');

    $routes->add('laporan-lembur-mingguan', 'Backend\Rpt_Overtime::indexWeekly');
    $routes->match(['get', 'post'], 'laporan-lembur-mingguan/showAll', 'Backend\Rpt_Overtime::showAllWeekly');

    $routes->add('calendar', 'Backend\Year::index');
    $routes->match(['get', 'post'], 'calendar/showAll', 'Backend\Year::showAll');
    $routes->post('calendar/create', 'Backend\Year::create');
    $routes->get('calendar/show/(:any)', 'Backend\Year::show/$1');
    $routes->get('calendar/destroy/(:any)', 'Backend\Year::destroy/$1');

    $routes->add('supplier', 'Backend\Supplier::index');
    $routes->match(['get', 'post'], 'supplier/showAll', 'Backend\Supplier::showAll');
    $routes->post('supplier/create', 'Backend\Supplier::create');
    $routes->get('supplier/show/(:any)', 'Backend\Supplier::show/$1');
    $routes->get('supplier/destroy/(:any)', 'Backend\Supplier::destroy/$1');
    $routes->get('supplier/getSeqCode', 'Backend\Supplier::getSeqCode');
    $routes->get('supplier/getList', 'Backend\Supplier::getList');

    $routes->add('outsource', 'Backend\Outsourcing::index');
    $routes->match(['get', 'post'], 'outsource/showAll', 'Backend\Outsourcing::showAll');
    $routes->get('outsource/getDataBy/(:num)', 'Backend\Outsourcing::getBy/$1');
    $routes->match(['get', 'post'], 'outsource/getDetail', 'Backend\Outsourcing::getDetailEmployee');
    $routes->match(['get', 'post'], 'outsource/getList', 'Backend\Outsourcing::getList');
    $routes->match(['get', 'post'], 'outsource/superior', 'Backend\Outsourcing::getSuperior');
    $routes->get('outsource/destroy/(:any)', 'Backend\Outsourcing::destroy/$1');
    $routes->post('outsource/create', 'Backend\Outsourcing::create');
    $routes->get('outsource/show/(:any)', 'Backend\Outsourcing::show/$1');
    $routes->get('outsource/get-nik', 'Backend\Outsourcing::getNik');

    // $routes->post('rule-detail/create', 'Backend\RuleDetail::create');
    // $routes->get('rule-detail/show', 'Backend\RuleDetail::show');
    // $routes->get('rule-detail/show/(:any)', 'Backend\RuleDetail::show/$1');
    // $routes->post('rule-detail/tableLine', 'Backend\RuleDetail::tableLine');
    // $routes->get('rule-detail/getDataBy/(:num)', 'Backend\RuleDetail::getBy/$1');

    // $routes->post('rule-value/create', 'Backend\RuleValue::create');
    // $routes->get('rule-value/show', 'Backend\RuleValue::show');
    // $routes->get('rule-value/show/(:any)', 'Backend\RuleValue::show/$1');
    // $routes->post('rule-value/tableLine', 'Backend\RuleValue::tableLine');
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