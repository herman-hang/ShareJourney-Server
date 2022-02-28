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
    // 不需要检测登录的路由
    'not_login' => [
        'LoginController/login',// 登录
        'LoginController/smsLogin',// 短信登录
        'LoginController/sendLoginCode',// 短信登录发送验证码
        'LoginController/weixinLogin',// 微信授权登录
        'LoginController/bindPhoneSendCode',// 微信授权登录绑定手机发送验证码
        'LoginController/bindPhone',// 微信授权登录绑定手机
        'RegisterController/register',// 注册
        'RegisterController/sendRegisterCode',// 注册发送验证码
        'PayController/wechatPayCallback',// 微信小程序支付回调
        'IndexController/getOwnerIndent',// 获取车主订单数据
        'IndexController/getUserIndent',// 获取用户订单数据
        'PayController/wechatPayCallback',// 微信支付回调
        'PayController/callOwnerWechatPayCallback',// 呼叫车主发起支付回调
    ],
    // 不用检测实名认证的路由
    'not_auth'=>[
        'MineController/getCertificationInfo', // 获取当前用户认证信息
        'MineController/authenticationNext', // 实名认证下一步逻辑处理
        'MineController/authenticationSubmit', // 实名认证提交逻辑处理
        'CommonController/upload' // 上传文件
    ]
];