<?php
/**
 * FileName: 小程序/H5/APP端路由
 * Description: 管理小程序/H5/APP端API接口
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-01-15 10:27
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

use think\facade\Route;

/**
 * 公共方法相关路由
 */
Route::group('base', function () {
    Route::post('upload', 'upload');
})->prefix('CommonController/');

/**
 * 登录相关路由
 */
Route::group('login', function () {
    Route::post('/', 'login');
    Route::get('/', 'loginOut');
    Route::get('send/code', 'sendLoginCode');
    Route::post('sms', 'smsLogin');
    Route::post('weixin', 'weixinLogin');
    Route::get('bind/send/code', 'bindPhoneSendCode');
    Route::post('bind/phone', 'bindPhone');
})->prefix('LoginController/');

/**
 * 注册相关路由
 */
Route::group('register', function () {
    Route::post('/', 'register');
    Route::get('send/code', 'sendRegisterCode');
})->prefix('RegisterController/');

/**
 * 找回密码相关路由
 */
Route::group('password', function () {
    Route::post('/', 'password');
    Route::get('send/code', 'SendPasswordCode');
})->prefix('PasswordController/');

/**
 * 滑动验证码相关路由
 */
Route::group('captcha', function () {
    Route::post('get', 'get');
    Route::post('check', 'check');
})->prefix('CaptchaController/');