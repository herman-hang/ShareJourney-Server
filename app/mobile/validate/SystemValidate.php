<?php
/**
 * FileName: 系统设置验证器
 * Description: 用于验证系统设置相关字段格式
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-03-07 15:26
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\validate;


use think\Validate;

class SystemValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'mobile' => 'require|mobile',
        'code'   => 'require',
        'email'  => 'require|email'
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'mobile.require' => '新手机号码不能为空！',
        'mobile.mobile'  => '新手机号码格式不正确！',
        'code.require'   => '验证码不能为空！',
        'email.require'  => '新邮箱地址不能为空!',
        'email.email'    => '新邮箱地址格式不正确！',
        'email.unique'   => '邮箱地址已存在！',
    ];

    /**
     * 换绑手机号码验证
     * @return SystemValidate
     */
    public function sceneChangeMobile(): SystemValidate
    {
        return $this->only(['mobile', 'code']);
    }

    /**
     * 换绑邮箱地址
     * @return SystemValidate
     */
    public function sceneChangeEmail(): SystemValidate
    {
        return $this->only(['email', 'code']);
    }

    /**
     * 绑定邮箱地址发送验证码验证
     * @return SystemValidate
     */
    public function sceneBindEmailSendCode(): SystemValidate
    {
        return $this->only(['email'])->append('email', 'unique:user');
    }

    /**
     * 绑定邮箱地址
     * @return SystemValidate
     */
    public function sceneBindEmail(): SystemValidate
    {
        return $this->only(['code','email'])->append('email', 'unique:user');
    }
}