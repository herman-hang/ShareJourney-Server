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
    Route::get('check/user', 'checkUser');
    Route::get('check/indent/status', 'checkIndentStatus');
    Route::get('check/authentication', 'checkUserAuthentication');
})->prefix('CommonController/');

/**
 * 登录相关路由
 */
Route::group('login', function () {
    Route::post('/', 'login');
    Route::get('out', 'loginOut');
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

/**
 * 我的相关路由
 */
Route::group('mine', function () {
    Route::get('index', 'index');
    Route::get('money', 'myMoney');
    Route::post('withdraw/send/code', 'withdrawSendCode');
    Route::post('withdraw/audit', 'withdrawAudit');
    Route::get('material', 'getMaterial');
    Route::post('material', 'materialSave');
    Route::get('certification', 'getCertificationInfo');
    Route::post('authentication/next', 'authenticationNext');
    Route::post('authentication/submit', 'authenticationSubmit');
    Route::post('withdraw/info/submit', 'submitWithdrawInfo');
    Route::post('car/info/submit', 'submitCarInfo');
    Route::post('patente/submit', 'submitPatente');
    Route::post('registration/submit', 'submitRegistration');
    Route::get('indent/list', 'indentList');
    Route::get('withdraw/detail', 'withdrawDetail');
    Route::get('withdraw/resubmit/audit', 'resubmitAudit');
    Route::get('bill/income', 'income');
    Route::get('withdraw', 'withdraw');
    Route::get('user/owner/auth', 'authSubmitAudit');
})->prefix('MineController/');

/**
 * 首页相关路由
 */
Route::group('index', function () {
    Route::get('trip/compute', 'tripData');
    Route::get('user/line', 'getLine');
    Route::get('navigation/journey', 'getJourney');
    Route::post('edit/order', 'editOrder');
    Route::post('indent/start', 'setOut');
    Route::get('owner/indent', 'getOwnerIndent');
    Route::get('user/indent', 'getUserIndent');
    Route::get('path/line', 'getPathLineData');
    Route::get('query/line', 'queryLine');
})->prefix('IndexController/');

/**
 * 支付类相关路由
 */
Route::group('pay', function () {
    Route::post('wechat', 'wechatPay');
    Route::post('call/wechat', 'callOwnerWechatPay');
    Route::get('wechat/callback', 'wechatPayCallback');
    Route::get('call/wechat/callback', 'callOwnerWechatPayCallback');
})->prefix('PayController/');

/**
 * 车主相关路由
 */
Route::group('owner', function () {
    Route::post('start', 'start');
    Route::post('invitation/user', 'invitationUser');
    Route::get('trip/compute', 'indentCompute');
})->prefix('OwnerController/');

/**
 * 旅途列表相关路由
 */
Route::group('journey', function () {
    Route::get('owner/indent/list', 'list');
})->prefix('JourneyController/');

/**
 * 旅行记录相关路由
 */
Route::group('record', function () {
    Route::get('list', 'list');
})->prefix('RecordController/');

/**
 * 系统设置相关路由
 */
Route::group('system', function () {
    Route::get('send/mobile/code', 'sendChangeMobileCode');
    Route::get('send/email/code', 'sendChangeEmailCode');
    Route::get('send/bind/email/code', 'sendBindEmailCode');
    Route::get('query/email', 'queryEmail');
    Route::post('change/mobile', 'changeMobile');
    Route::post('change/email', 'changeEmail');
    Route::post('bind/email', 'bindEmail');
})->prefix('SystemController/');