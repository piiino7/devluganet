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
    //['POST', '/auth/register',  AuthController::class,      'register',     false], // — тестовая регистрация
    ['POST', '/auth/refresh',   AuthController::class,      'refresh',      false], // — рефреш access-токена
    ['POST',  '/auth/logout',    AuthController::class,      'logout',      false], // — логаут

    // Авторизованные
    ['GET',  '/auth/me',        AuthController::class,      'me',           true], // — данные о себе
    ['GET',  '/auth/devices',   AuthController::class,      'devices',      true], // — данные о залогиненных устройствах
    ['POST', '/auth/logoutAll', AuthController::class,      'logoutAll',    true], // — логаут со всех устройств


    // Продажа
    ['GET',     '/groups',                     ProductController::class,   'getGroups',    true], // — список категорий
    ['GET',     '/groups/{groupId}/products',  ProductController::class,   'getProducts',  true], // — товары в категории (добавить пагинацию)
    ['GET',     '/products',                   ProductController::class,   'getProducts',  true], // — все товары (фильтры, поиск, пагинация)
    ['GET',     '/products/{productId}',       ProductController::class,   'getProduct',   true], // — один товар (подробно со всеми предложениями)
    ['GET',     '/offer/{offerId}',            ProductController::class,   'getOffer',     true], // — одно конкретное предложение

    ['GET',     '/cart',                       ProductController::class,        'getCart',           true], // — посмотреть корзину
    ['POST',    '/addToCart',                  ProductController::class,        'addToCart',         true], // — добавить в корзину
    ['POST',    '/removeFromCart',             ProductController::class,        'removeFromCart',    true], // — удалить из корзины
    ['DELETE',  '/cart',                       ProductController::class,        'clearCart',         true], // — удалить из корзины
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
