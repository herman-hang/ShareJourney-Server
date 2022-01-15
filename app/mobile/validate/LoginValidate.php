<?php
/**
 * FileName: 登录验证器
 * Description: 用于验证登录字段
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-01-15 16:35
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\validate;

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
        'password' => 'require|length:6,15',
        'email'    => 'require|email',
        'mobile'   => 'require|mobile|unique:user,mobile',
        'accept'   => 'accepted'
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'user.require'     => '用户名不能为空！',
        'password.require' => '密码不能为空！',
        'user.length'      => '用户名只能在5到15位之间！',
        'user.unique'      => '用户名已存在！',
        'password.length'  => '密码只能在6到15位之间！',
        'email.email'      => '邮箱格式不正确！',
        'email.require'    => '邮箱不能为空！',
        'mobile.require'   => '手机号码不能为空！',
        'mobile.mobile'    => '手机号码格式不正确！',
        'mobile.unique'    => '手机号码已注册！',
        'accept.accepted'  => '请同意隐私政策和服务协议！',
    ];

    /**
     * 登录
     * @return LoginValidate
     */
    public function sceneLogin(): LoginValidate
    {
        return $this->only(['user', 'password']);
    }

    /**
     * 注册
     * @return LoginValidate
     */
    public function sceneRegister(): LoginValidate
    {
        return $this->only(['mobile', 'password', 'accept']);
    }

    /**
     * 注册发送短信验证码
     * @return LoginValidate
     */
    public function sceneSendRegisterCode(): LoginValidate
    {
        return $this->only(['mobile']);
    }

    /**
     * 找回密码
     * @return LoginValidate
     */
    public function scenePassword(): LoginValidate
    {
        return $this->only(['password']);
    }
    /**
     * 找回密码发送验证码表单验证
     * @return LoginValidate
     */
    public function scenePassSendCode(): LoginValidate
    {
        return $this->only(['mobile']);
    }
}