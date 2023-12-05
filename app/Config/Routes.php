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

    $routes->add('country', 'Backend\Country::index');
    $routes->match(['get', 'post'], 'country/showAll', 'Backend\Country::showAll');
    $routes->post('country/create', 'Backend\Country::create');
    $routes->get('country/show/(:any)', 'Backend\Country::show/$1');
    $routes->get('country/destroy/(:any)', 'Backend\Country::destroy/$1');
    $routes->get('country/getSeqCode', 'Backend\Country::getSeqCode');

    $routes->add('bloodtype', 'Backend\BloodType::index');
    $routes->match(['get', 'post'], 'bloodtype/showAll', 'Backend\BloodType::showAll');
    $routes->post('bloodtype/create', 'Backend\BloodType::create');
    $routes->get('bloodtype/show/(:any)', 'Backend\BloodType::show/$1');
    $routes->get('bloodtype/destroy/(:any)', 'Backend\BloodType::destroy/$1');
    $routes->get('bloodtype/getSeqCode', 'Backend\BloodType::getSeqCode');

    $routes->add('position', 'Backend\Position::index');
    $routes->match(['get', 'post'], 'position/showAll', 'Backend\Position::showAll');
    $routes->post('position/create', 'Backend\Position::create');
    $routes->get('position/show/(:any)', 'Backend\Position::show/$1');
    $routes->get('position/destroy/(:any)', 'Backend\Position::destroy/$1');
    $routes->get('position/getSeqCode', 'Backend\Position::getSeqCode');

    $routes->add('status', 'Backend\Status::index');
    $routes->match(['get', 'post'], 'status/showAll', 'Backend\Status::showAll');
    $routes->post('status/create', 'Backend\Status::create');
    $routes->get('status/show/(:any)', 'Backend\Status::show/$1');
    $routes->get('status/destroy/(:any)', 'Backend\Status::destroy/$1');
    $routes->get('status/getSeqCode', 'Backend\Status::getSeqCode');
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
