<?php
/**
 * FileName: ${FileName}
 * Description: ${Description}
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-11-27 21:01
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

use think\facade\Route;


/**
 * 登录相关路由
 */
Route::group('login', function () {
    Route::post('/', 'login');
    Route::get('switch', 'getSwitch');
    Route::get('captcha', 'getCaptcha');
})->prefix('LoginController/');

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
    Route::get(':id', 'query');
    Route::delete('delete', 'delete');
    Route::put('status', 'statusEdit');
})->prefix('AdminController/');

/**
 * 权限组相关路由
 */
Route::group('group',function (){
    Route::get('list', 'list');
    Route::post('add', 'add');
    Route::put('edit', 'edit');
    Route::delete('delete', 'delete');
    Route::get('[:id]', 'query');
    Route::put('status', 'statusEdit');
})->prefix('GroupController/');