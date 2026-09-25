<?php

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\SellerController;

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

    // Только seller
    ['GET',     '/getGroups',               SellerController::class,    'getGroups',            true],
    //['GET',     '/getGroup',                SellerController::class,    'getGroup',             true],
    ['GET',     '/getProducts',             SellerController::class,    'getProducts',          true],
    //['GET',     '/getProduct',              SellerController::class,    'getProduct',           true],
    //GET /seller/categories                       — список категорий
    //GET /seller/categories/{id}                  — одна категория
    //GET /seller/categories/{id}/products         — товары в категории (пагинация)
    //GET /seller/products                         — все товары (фильтры, поиск, пагинация)
    //GET /seller/products/{id}                    — карточка товара

    // Только admin
    ['GET',     '/admin/getMe',             AdminController::class,     'me',                   ['admin']],
    ['POST',    '/admin/registerEmployer',  AdminController::class,     'register_employer',    ['admin']],
    ['POST',    '/admin/blockEmployers',    AdminController::class,     'block_employer',       ['admin']],
    ['POST',    '/admin/restoreEmployers',  AdminController::class,     'restore_employer',     ['admin']],
    ['POST',    '/admin/deleteEmployers',   AdminController::class,     'remove_employer',      ['admin']],
    ['POST',    '/admin/updateEmployer',    AdminController::class,     'update_employer',      ['admin']],
    ['GET',     '/admin/getEmployers',      AdminController::class,     'list_of_employers',    ['admin']],
    ['GET',     '/admin/getEmployer',       AdminController::class,     'get_employer',         ['admin']],
    //['GET',     '/admin/getReport',         AdminController::class,     'report',               ['admin']],
];
