<?php
/**
 * FileName: 权限配置信息
 * Description: 用于配置权限相关信息
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-11-28 10:08
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */
return [
    // 后台不需要验证登录，不需要验证权限的路由（控制器/方法）
    'not_auth' => [
        'LoginController/login',// 登录
        'LoginController/captcha', // 登录验证码
        'LoginController/getCaptcha',//获取验证码
        'LoginController/oauth', // 第三方登录绑定
        'LoginController/getSwitch', // 获取快捷登录开关
        'LoginController/system', // 获取快捷登录开关
        'OauthController/login', // 第三方登录
        'OauthController/callback', // 第三方登录回调地址
    ],
    // 后台需要登录，但是不需要验证权限的路由（控制器/方法）
    'is_login' => [
        'CommonController/upload', // 文件上传
        'CommonController/log', // 日志记录
        'Index/home',// 后台首页
        'Index/welcome',// 我的桌面
        'Index/clear',// 清除缓存
        'Index/loginOut'// 退出登录
    ]
];