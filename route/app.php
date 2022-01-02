<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

/*// 批量填充用户
Route::get('addUserData','\app\seed\Data@addUserDataSeed');
// 批量填充车主
Route::get('addOwnerData','\app\seed\Data@addOwnerDataSeed');
// 批量填充用户支付订单
Route::get('addUserBuyData','\app\seed\Data@addUserBuyDataSeed');
// 批量填充提现订单
Route::get('addOwnerWithdrawData','\app\seed\Data@addOwnerWithdrawDataSeed');
// 批量填充旅途数据
Route::get('addJourneyData','\app\seed\Data@addJourneyDataSeed');*/