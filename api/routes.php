<?php

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ProductController;
use App\Controllers\OrderController;
use App\Controllers\CartController;
use App\Controllers\OperatorController;

/**
 * [method, path, controllerClass, action, auth]
 * auth: false — публичный
 *       true  — любой авторизованный
 *       ['role1','role2'] — с указанными ролями
 */
return [
    // Авторизация
    ['GET',  '/welcome',        AuthController::class,      'welcome',      false], // — проверка доступности api
    ['POST', '/auth/login',     AuthController::class,      'login',        false], // — логин
    //['POST', '/auth/register',  AuthController::class,      'register',     false], // — тестовая регистрация
    ['POST', '/auth/refresh',   AuthController::class,      'refresh',      false], // — рефреш access-токена
    ['POST', '/auth/logout',    AuthController::class,      'logout',       false], // — логаут

    // Авторизованные
    ['GET',    '/auth/me',           AuthController::class,      'me',                true], // — данные о себе
    ['PATCH',  '/account/password',  AuthController::class,      'changePassword',    true], // — изменить пароль
    ['POST',   '/auth/logoutAll',    AuthController::class,      'logoutAll',         true], // — логаут со всех устройств

    // Категории товаров/Товары/Предложения
    ['GET',     '/groups',                     ProductController::class,   'getGroups',    ['seller', 'operator']], // — список категорий
    ['GET',     '/groups/{groupId}/products',  ProductController::class,   'getProducts',  ['seller', 'operator']], // — товары в категории (добавить пагинацию)
    ['GET',     '/products',                   ProductController::class,   'getProducts',  ['seller', 'operator']], // — все товары (фильтры, поиск, пагинация)
    ['GET',     '/products/{productId}',       ProductController::class,   'getProduct',   ['seller', 'operator']], // — один товар (подробно со всеми предложениями)
    ['PATCH',   '/products/{productId}',       ProductController::class,   'changeAlias',  ['seller', 'operator']], // — задать/поменять алиас в номенклатуре
    ['GET',     '/offer/{offerId}',            ProductController::class,   'getOffer',     ['seller', 'operator']], // — одно конкретное предложение

    // Корзина для продавца
    ['GET',     '/cart',                       CartController::class,       'getCart',           ['seller']], // — посмотреть корзину
    ['POST',    '/addToCart',                  CartController::class,       'addToCart',         ['seller']], // — добавить в корзину
    ['PATCH',   '/removeFromCart',             CartController::class,       'removeFromCart',    ['seller']], // — удалить из корзины
    ['DELETE',  '/cart',                       CartController::class,       'clearCart',         ['seller']], // — очистить корзину

    // Оформление заказа продавцом
    //['POST',    '/order',                      OrderController::class,          'makeAnOrder',       ['seller']], // — создание заказа в БД
    //['POST',    '/order/qr',                   OrderController::class,          'QRpayment',         ['seller']], // — создание и получение QR для оплаты
    //['POST',    '/webhooks/bank',              OrderController::class,          'webhook',           false], // — роут для банка

    // Оператор
    ['GET',    '/operator/sellers',                             OperatorController::class,   'getSellers',              ['operator']], // — получить всех продавцов
    ['GET',    '/operator/sellers/{sellerId}',                  OperatorController::class,   'getSeller',               ['operator']], // — получить продавца
    ['POST',   '/operator/sellers',                             OperatorController::class,   'registerSeller',          ['operator']], // — создать продавца
    ['PATCH',  '/operator/changeSellerPassword/{sellerId}',     OperatorController::class,   'changeSellerPassword',    ['operator']], // — поменять продавцу пароль (возможно делать логаут)
    ['PATCH',  '/operator/products/{productId}',                ProductController::class,    'updateProduct',           ['operator']], // — изменить товар
    //['GET',    '/operator/sales',                               OperatorController::class,   'getSales',                ['operator']], // — все продажи
    //['GET',    '/operator/salesReport',                         OperatorController::class,   'salesReport',             ['operator']], // — отчёт по продажам

    // admin
    ['POST',        '/admin/registerEmployer',               AdminController::class,     'registerEmployer',        ['admin', 'superadmin']], // — создать сотрудника
    ['PATCH',       '/admin/blockEmployers',                 AdminController::class,     'blockEmployer',           ['admin', 'superadmin']], // — заблокировать сотрудника
    ['PATCH',       '/admin/restoreEmployers',               AdminController::class,     'restoreEmployer',         ['admin', 'superadmin']], // — восстановить сотрудника
    ['POST',        '/admin/deleteEmployers',                AdminController::class,     'removeEmployer',          ['admin', 'superadmin']], // — удалить сотрудника
    ['PATCH',       '/admin/updateEmployer/{employerId}',    AdminController::class,     'updateEmployer',          ['admin', 'superadmin']], // — обновить сотрудника
    ['GET',         '/admin/getEmployers',                   AdminController::class,     'listOfEmployers',         ['admin', 'superadmin']], // — получить сотрудников
    ['GET',         '/admin/getEmployer/{employerId}',       AdminController::class,     'getEmployer',             ['admin', 'superadmin']], // — получить сотрудника
    //['GET',     '/admin/getReport',         AdminController::class,     'report',               ['admin']],
];
