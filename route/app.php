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
use think\facade\Db;
use think\facade\Route;

Route::get('/', function () {
    return 'hello,共享旅途!';
});

Route::get('test',function (){
    $data = Db::view('user','user,nickname,is_owner')
        ->view('user_owner','*','user_owner.user_id=user.id')
        ->where('user_owner.status','1')
        ->where('user.is_owner','2')
        ->where('user.status','1')
        ->select()->toArray();
    dd($data);
});