<?php
/**
 * FileName: 路由鉴权
 * Description: 用于存放那些路由不需要鉴权
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-01-15 11:04
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */
return [
    'not_login' => [
        'LoginController/login',// 登录
        'RegisterController/register',// 注册
        'RegisterController/sendRegisterCode',// 注册发送验证码
    ]
];