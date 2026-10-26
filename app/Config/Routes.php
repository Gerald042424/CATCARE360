<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Public\LandingController::index');
$routes->addRedirect('/home', 'adopter/home');
$routes->get('/application-status', 'Public\ApplicationFormController::status');
$routes->post('/auth/adopter-login', 'Public\AuthController::adopterLogin');
$routes->post('/auth/login', 'Public\AuthController::login');
$routes->post('/auth/adopter-forgot-password/send-code', 'Public\AuthController::sendPasswordResetCode');
$routes->post('/auth/adopter-forgot-password/verify-code', 'Public\AuthController::verifyPasswordResetCode');
$routes->post('/auth/adopter-forgot-password/reset-password', 'Public\AuthController::resetPassword');
$routes->post('/auth/adopter-register', 'Public\AuthController::register');
$routes->post('/auth/adopter-register/send-code', 'Public\AuthController::sendRegistrationCode');
$routes->post('/auth/adopter-register/verify-code', 'Public\AuthController::verifyRegistrationCode');
$routes->post('/auth/volunteer-login', 'Public\AuthController::volunteerLogin');
$routes->get('/auth/logout', 'Public\AuthController::logout');

$routes->get('adopter/register', 'Adopter\AuthController::registerForm');
$routes->post('adopter/register', 'Public\AuthController::register');
$routes->get('adopter/logout', 'Adopter\AuthController::logout');

$routes->group('adopter', ['filter' => 'adopterAuth'], static function (RouteCollection $routes): void {
    $routes->get('home', 'Adopter\PortalController::home');
    $routes->get('cats/(:num)', 'Adopter\PortalController::catProfile/$1');
    $routes->get('apply', 'Adopter\ApplicationController::index');
    $routes->get('apply/success', 'Adopter\ApplicationController::success');
    $routes->get('apply/(:num)', 'Adopter\ApplicationController::index/$1');
    $routes->post('apply', 'Adopter\ApplicationController::submit');
    $routes->get('portal', 'Adopter\PortalController::index');
    $routes->get('portal/(:num)', 'Adopter\PortalController::index/$1');
    $routes->get('application', 'Adopter\PortalController::application');
    $routes->get('application/(:num)', 'Adopter\PortalController::applicationProgress/$1');
    $routes->get('application/(:num)/cat/(:num)', 'Adopter\PortalController::applicationProgress/$1/$2');
    $routes->post('updates/submit', 'Adopter\PortalController::submitUpdate');
    $routes->post('profile', 'Adopter\PortalController::updateProfile');
});

$routes->get('admin/login', 'Admin\AuthController::loginForm');
$routes->post('admin/login', 'Public\AuthController::login');
$routes->get('admin/logout', 'Admin\AuthController::logout');
$routes->addRedirect('admin', 'admin/dashboard');

$routes->group('admin', ['filter' => 'staffAuth'], static function (RouteCollection $routes): void {
    $routes->get('dashboard', 'Admin\DashboardController::index');
    $routes->get('analytics', 'Admin\AnalyticsController::index');
    $routes->get('reports', 'Admin\ReportsController::index');
    $routes->get('reports/export/(:segment)', 'Admin\ReportsController::export/$1');

    $routes->match(['get', 'post'], 'animals', 'Admin\AnimalsController::index');
    $routes->match(['get', 'post'], 'animals/new', 'Admin\AnimalsController::form');
    $routes->match(['get', 'post'], 'animals/(:num)', 'Admin\AnimalsController::detail/$1');
    $routes->post('animals/(:num)/delete', 'Admin\AnimalsController::delete/$1');

    $routes->get('applications', 'Admin\ApplicationsController::index');
    $routes->match(['get', 'post'], 'applications/(:num)', 'Admin\ApplicationsController::detail/$1');
    $routes->post('applications/(:num)/cats/(:num)', 'Admin\ApplicationsController::catStatus/$1/$2');
    $routes->post('applications/(:num)/documents/(:num)', 'Admin\ApplicationsController::document/$1/$2');
    $routes->post('applications/(:num)/resend-decision', 'Admin\ApplicationsController::resendDecision/$1');

    $routes->get('adopters', 'Admin\AdoptersController::index');
    $routes->match(['get', 'post'], 'adopters/(:num)', 'Admin\AdoptersController::profile/$1');

    $routes->match(['get', 'post'], 'monitoring', 'Admin\MonitoringController::index');
    $routes->post('monitoring/(:num)/verify-gps', 'Admin\MonitoringController::verify/$1');
    $routes->post('monitoring/(:num)/review', 'Admin\MonitoringController::review/$1');
    $routes->get('home-visits', 'Admin\HomeVisitsController::index');
    $routes->post('home-visits', 'Admin\HomeVisitsController::save');
    $routes->post('home-visits/(:num)', 'Admin\HomeVisitsController::update/$1');

    $routes->match(['get', 'post'], 'volunteers', 'Admin\VolunteersController::index');
    $routes->match(['get', 'post'], 'volunteers/new', 'Admin\VolunteersController::form');
    $routes->match(['get', 'post'], 'volunteers/(:num)', 'Admin\VolunteersController::detail/$1');
    $routes->post('volunteers/(:num)/delete', 'Admin\VolunteersController::delete/$1');

    $routes->match(['get', 'post'], 'tasks', 'Admin\TasksController::index');
    $routes->match(['get', 'post'], 'tasks/new', 'Admin\TasksController::form');
    $routes->match(['get', 'post'], 'tasks/(:num)', 'Admin\TasksController::form/$1');
    $routes->post('tasks/(:num)/delete', 'Admin\TasksController::delete/$1');
    $routes->post('tasks/reassignment/(:num)', 'Admin\TasksController::resolveReassignment/$1');
});

$routes->get('volunteer/login', 'Volunteer\AuthController::loginPage');
$routes->post('volunteer/login', 'Public\AuthController::login');
$routes->get('volunteer/apply', 'Volunteer\ApplicationController::form');
$routes->post('volunteer/apply', 'Volunteer\ApplicationController::submit');
$routes->get('/volunteer/logout', 'Volunteer\AuthController::logout');
$routes->group('volunteer', ['filter' => 'volunteerAuth'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Volunteer\TasksController::index');
    $routes->get('schedule', 'Volunteer\TasksController::schedule');
    $routes->match(['get', 'post'], 'task/(:num)', 'Volunteer\TasksController::detail/$1');
    $routes->post('task/(:num)/report', 'Volunteer\TasksController::submitTaskReport/$1');
    $routes->post('task/(:num)/draft', 'Volunteer\TasksController::saveDraft/$1');
    $routes->post('task/(:num)/reassign', 'Volunteer\TasksController::requestReassignment/$1');
    $routes->post('settings', 'Volunteer\AuthController::updateSettings');
});
