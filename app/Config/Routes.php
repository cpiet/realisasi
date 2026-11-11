<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Public / Pimpinan (Read-Only)
$routes->get('/', 'Dashboard::index');
$routes->get('dashboard', 'Dashboard::index');
$routes->get('api/dashboard-data', 'Dashboard::getData');
$routes->get('set-year/(:num)', 'Dashboard::switchYear/$1');
$routes->match(['get', 'post'], 'api/set-year', 'Dashboard::apiSetYear');

// Standar Biaya SBM (publik, hanya baca)
$routes->get('sbm', 'Sbm::index');
$routes->get('sbm/cari', 'Sbm::cari');
$routes->get('sbm/item/(:num)', 'Sbm::item/$1');

// Data Kegiatan & Uraian Kegiatan (Akses Khusus: Administrator & Operator)
$routes->get('kegiatan', 'Activities::index', ['filter' => ['auth', 'role:admin,operator']]);
$routes->get('kegiatan/detail/(:num)', 'Activities::detail/$1', ['filter' => ['auth', 'role:admin,operator']]);
$routes->get('kegiatan/uraian', 'ActivityDescriptions::index', ['filter' => ['auth', 'role:admin,operator']]);
$routes->get('kegiatan/uraian/detail/(:num)', 'ActivityDescriptions::detail/$1', ['filter' => ['auth', 'role:admin,operator']]);
$routes->get('api/kegiatan/search', 'Activities::search', ['filter' => ['auth', 'role:admin,operator']]);

// Authentication
$routes->get('login', 'Auth::login');
$routes->post('auth/process', 'Auth::process');
$routes->get('logout', 'Auth::logout');
$routes->group('account', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Account::index');
    $routes->post('change-password', 'Account::changePassword');
});

// Operational routes: Administrator and Operator.
$routes->group('admin', ['filter' => 'auth'], static function ($routes) {
    $routes->group('', ['filter' => 'role:admin,operator'], static function ($routes) {
        $routes->get('realisasi', 'Admin\Realizations::index');
        $routes->post('realisasi/update-cell', 'Admin\Realizations::updateCell');
        $routes->get('import', 'Admin\Imports::index');
        $routes->post('import/preview', 'Admin\Imports::preview');
        $routes->post('import/commit', 'Admin\Imports::commit');
        $routes->get('import/history', 'Admin\Imports::history');
        $routes->get('import/template', 'Admin\Imports::downloadTemplate');
        $routes->get('rpa', 'Admin\Rpa::index');
        $routes->get('rpa/detail/(:num)', 'Admin\Rpa::detail/$1');
        $routes->post('rpa/import', 'Admin\Rpa::import');
        $routes->get('ikpa', 'Admin\Ikpa::index');
        $routes->post('ikpa/save', 'Admin\Ikpa::save');
        $routes->post('ikpa/import-pdf', 'Admin\Ikpa::importPdf');
        $routes->get('pagu', 'Admin\Settings::paguDipa');
        $routes->post('settings/save-targets', 'Admin\Settings::saveTargets');
        $routes->post('settings/save-pagu-dipa', 'Admin\Settings::savePaguDipa');
        $routes->get('settings/export-excel', 'Admin\Settings::exportExcel');
    });

    $routes->group('', ['filter' => 'role:admin'], static function ($routes) {
        $routes->get('settings', 'Admin\Settings::index');
        $routes->post('settings/add-year', 'Admin\Settings::addYear');
        $routes->post('settings/set-active-year', 'Admin\Settings::setActiveYear');
        $routes->post('settings/save-general', 'Admin\Settings::saveGeneral');
        $routes->get('settings/backup', 'Admin\Settings::backupDatabase');
        $routes->get('settings/backup-sbm', 'Admin\Settings::backupSbm');
        $routes->get('settings/backup-all', 'Admin\Settings::backupAll');
        $routes->get('users', 'Admin\Users::index');
        $routes->post('users/create', 'Admin\Users::create');
        $routes->post('users/update/(:num)', 'Admin\Users::update/$1');
        $routes->post('users/update-pin/(:num)', 'Admin\Users::updatePin/$1');
        $routes->post('users/generate-pin/(:num)', 'Admin\Users::generatePin/$1');
        $routes->post('users/reset-password/(:num)', 'Admin\Users::resetPassword/$1');
        $routes->post('users/generate-token/(:num)', 'Admin\Users::generateToken/$1');
        $routes->post('users/delete/(:num)', 'Admin\Users::delete/$1');

        // Kelola Standar Biaya SBM
        $routes->get('sbm', 'Admin\Sbm::index');
        $routes->post('sbm/regulasi/create', 'Admin\Sbm::createRegulasi');
        $routes->get('sbm/regulasi/(:num)', 'Admin\Sbm::regulasi/$1');
        $routes->post('sbm/regulasi/update/(:num)', 'Admin\Sbm::updateRegulasi/$1');
        $routes->post('sbm/regulasi/delete/(:num)', 'Admin\Sbm::deleteRegulasi/$1');
        $routes->post('sbm/regulasi/(:num)/item/create', 'Admin\Sbm::createItem/$1');
        $routes->get('sbm/item/(:num)', 'Admin\Sbm::item/$1');
        $routes->post('sbm/item/update/(:num)', 'Admin\Sbm::updateItem/$1');
        $routes->post('sbm/item/delete/(:num)', 'Admin\Sbm::deleteItem/$1');
        $routes->post('sbm/item/(:num)/harga/create', 'Admin\Sbm::createHarga/$1');
        $routes->post('sbm/harga/update/(:num)', 'Admin\Sbm::updateHarga/$1');
        $routes->post('sbm/harga/delete/(:num)', 'Admin\Sbm::deleteHarga/$1');
    });
});
