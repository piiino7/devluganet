<?php

use App\Controllers\AdminController;
use App\Controllers\AuthController;
//use App\Controllers\SellerController;

/**
 * [method, path, controllerClass, action, auth]
 * auth: false — публичный
 *       true  — любой авторизованный
 *       ['role1','role2'] — с указанными ролями
 */
return [
    // Публичные
    ['GET', '/welcome', AuthController::class, 'welcome', false],
    ['POST', '/auth/login', AuthController::class, 'login', false],
    ['POST', '/auth/register', AuthController::class, 'register', false],

    // Авторизованные
    ['GET',  '/auth/me',          AuthController::class, 'me',        true],
    //['GET',  '/seller/dashboard', SellerController::class, 'dashboard', true],
    //['GET',  '/seller/products',  SellerController::class, 'products',  true],

    // Только admin
    ['GET',     '/admin/getMe',             AdminController::class,     'me',                   ['admin']],
    ['POST',    '/admin/registerEmployer',  AdminController::class,     'register_employer',    ['admin']],
    ['POST',    '/admin/blockEmployers',    AdminController::class,     'block_employer',       ['admin']],
    ['POST',    '/admin/restoreEmployers',  AdminController::class,     'restore_employer',     ['admin']],
    ['POST',    '/admin/deleteEmployers',   AdminController::class,     'remove_employer',      ['admin']],
    ['GET',     '/admin/getEmployers',      AdminController::class,     'list_of_employers',    ['admin']],
];
