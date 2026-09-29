<?php

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ProductController;
use App\Controllers\OrderController;

/**
 * [method, path, controllerClass, action, auth]
 * auth: false — публичный
 *       true  — любой авторизованный
 *       ['role1','role2'] — с указанными ролями
 */
return [
    // Авторизация
    ['GET', '/welcome',         AuthController::class,      'welcome',      false], // — проверка доступности api
    ['POST', '/auth/login',     AuthController::class,      'login',        false], // — логин
    ['POST', '/auth/register',  AuthController::class,      'register',     false], // — тестовая регистрация

    // Авторизованные
    ['GET',  '/auth/me',        AuthController::class,      'me',           true], // — данные о себе

    // Продажа
    ['GET',     '/groups',                     ProductController::class,        'getGroups',         true], // — список категорий
    ['GET',     '/groups/{groupId}/products',  ProductController::class,        'getProducts',       true], // — товары в категории (добавить пагинацию)
    ['GET',     '/products',                   ProductController::class,        'getProducts',       true], // — все товары (фильтры, поиск, пагинация)
    ['GET',     '/products/{productId}',       ProductController::class,        'getProduct',        true], // — один товар
    //['GET',     '/clients',                    ProductController::class,        'ListOfClients',     true], // — список клиентов (обавить поиск, фильтры)
    //['GET',     '/cart/{clientId}',            ProductController::class,        'GetCart',           true], // — посмотреть корзину
    //['POST',    '/addToCart',                  ProductController::class,        'AddToCart',         true], // — добавить в корзину
    //['POST',    '/removeFromCart',             ProductController::class,        'RemoveFromCart',    true], // — удалить из корзины
    //['POST',    '/clearCart',                  ProductController::class,        'ClearCart',         true], // — удалить из корзины
    //['POST',    '/order',                      OrderController::class,          'makeAnOrder',       true], // — создание заказа в БД
    //['POST',    '/order/qr',                   OrderController::class,          'QRpayment',         true], // — создание и получение QR для оплаты
    //['POST',    '/webhooks/bank',              OrderController::class,          'webhook',           false], // — роут для банка

    // admin
    ['POST',        '/admin/registerEmployer',               AdminController::class,     'registerEmployer',        ['admin']], // — создать сотрудника
    ['PATCH',       '/admin/blockEmployers',                 AdminController::class,     'blockEmployer',           ['admin']], // — заблокировать сотрудника
    ['PATCH',       '/admin/restoreEmployers',               AdminController::class,     'restoreEmployer',         ['admin']], // — восстановить сотрудника
    ['POST',        '/admin/deleteEmployers',                AdminController::class,     'removeEmployer',          ['admin']], // — удалить сотрудника
    ['PATCH',       '/admin/updateEmployer/{employerId}',    AdminController::class,     'updateEmployer',          ['admin']], // — обновить сотрудника
    ['GET',         '/admin/getEmployers',                   AdminController::class,     'listOfEmployers',         ['admin']], // — получить сотрудников
    ['GET',         '/admin/getEmployer/{employerId}',       AdminController::class,     'getEmployer',             ['admin']], // — получить сотрудника
    //['POST',  '/admin/order',                          AdminController::class,     'delete_order',         ['admin']], // — удаление заказа из БД
    //['GET',     '/admin/getReport',         AdminController::class,     'report',               ['admin']],
];
