<?php
/**
 * FileName: 后台路由
 * Description: 管理后台API接口
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-11-27 21:01
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

use think\facade\Route;

/**
 * 后台首页相关路由
 */
Route::group('index', function () {
    Route::get('home', 'home');
    Route::get('welcome', 'welcome');
    Route::get('clear', 'clear');
    Route::get('loginOut', 'loginOut');
})->prefix('IndexController/');

/**
 * 登录相关路由
 */
Route::group('login', function () {
    Route::post('/', 'login');
    Route::get('switch', 'getSwitch');
    Route::get('captcha', 'getCaptcha');
    Route::get('system', 'system');
})->prefix('LoginController/');

/**
 * 第三方登录相关路由
 */
Route::group('oauth', function () {
    Route::get('login/:type', 'login');
    Route::get('callback/:type', 'callback');
})->prefix('OauthController/');

/**
 * 公共方法相关路由
 */
Route::group('base', function () {
    Route::post('upload', 'upload');
})->prefix('CommonController/');

/**
 * 系统管理相关路由
 */
Route::group('system', function () {
    Route::get('/', 'system');
    Route::put('/', 'systemEdit');
    Route::get('security', 'security');
    Route::put('security', 'securityEdit');
    Route::get('switch', 'switch');
    Route::put('switch', 'switchEdit');
    Route::get('pass', 'pass');
    Route::put('pass', 'passEdit');
})->prefix('SystemController/');

/**
 * 管理员管理相关路由
 */
Route::group('admin', function () {
    Route::get('list', 'list');
    Route::post('add', 'add');
    Route::put('edit', 'edit');
    Route::delete('delete', 'delete');
    Route::put('status', 'statusEdit');
    Route::get('log', 'logList');
    Route::get('query/:id', 'query');
})->prefix('AdminController/');

/**
 * 权限组相关路由
 */
Route::group('group', function () {
    Route::get('list', 'list');
    Route::post('add', 'add');
    Route::put('edit', 'edit');
    Route::delete('delete', 'delete');
    Route::get('query/[:id]', 'query');
    Route::put('status', 'statusEdit');
})->prefix('GroupController/');

/**
 * 用户相关路由
 */
Route::group('user', function () {
    Route::get('list', 'list');
    Route::post('add', 'add');
    Route::put('edit', 'edit');
    Route::get('query/:id', 'query');
    Route::delete('delete', 'delete');
    Route::put('status', 'statusEdit');
    Route::get('buylog', 'buylog');
})->prefix('UserController/');

/**
 * 车主相关路由
 */
Route::group('owner', function () {
    Route::get('list', 'list');
    Route::put('status', 'statusEdit');
    Route::put('edit', 'edit');
    Route::get('query/:id', 'query');
    Route::get('withdraw/list', 'withdrawList');
    Route::put('withdraw/pass', 'pass');
    Route::put('withdraw/reject', 'reject');
    Route::get('withdraw/query/:id', 'withdrawQuery');
    Route::get('audit/list', 'auditList');
    Route::get('audit/query/:id', 'auditQuery');
    Route::put('audit/pass', 'auditPass');
    Route::put('audit/reject', 'auditReject');
})->prefix('OwnerController/');

/**
 * 功能配置相关路由
 */
Route::group('functional', function () {
    Route::get('pay', 'pay');
    Route::put('pay', 'payEdit');
    Route::get('sms', 'sms');
    Route::put('sms', 'smsEdit');
    Route::post('sms', 'testSms');
    Route::get('email', 'email');
    Route::put('email', 'emailEdit');
    Route::post('email', 'testEmail');
    Route::get('thirdparty', 'thirdparty');
    Route::put('thirdparty', 'thirdpartyEdit');
})->prefix('FunctionalController/');

/**
 * 车主管理相关路由
 */
Route::group('journey', function () {
    Route::get('list', 'list');
    Route::get('query/:id', 'query');
    Route::get('timeLine/:id', 'timeLine');
    Route::delete('delete', 'delete');
})->prefix('JourneyController/');