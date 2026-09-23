<?php

//use App\Controllers\AdminController;
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
    /*['GET',  '/seller/dashboard', SellerController::class, 'dashboard', true],
    ['GET',  '/seller/products',  SellerController::class, 'products',  true],

    // Только admin
    ['GET',    '/admin/users',       AdminController::class, 'users',       ['admin']],
    ['POST',   '/admin/users',       AdminController::class, 'createUser',  ['admin']],
    ['PATCH',  '/admin/users/{id}',  AdminController::class, 'updateUser',  ['admin']],
    ['DELETE', '/admin/users/{id}',  AdminController::class, 'deleteUser',  ['admin']],*/
];
