<?php
/**
 * FileName: 登录验证器
 * Description: 验证登录相关规则
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-11-28 11:56
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\validate;


class LoginValidate extends \think\Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'user'     => 'require|length:5,15',
        'password' => 'require|length:6,15'
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'user.require'     => '用户名不能为空',
        'password.require' => '密码不能为空',
        'user.length'      => '用户名只能在5到15位之间',
        'password.length'  => '密码只能在6到15位之间'
    ];
}