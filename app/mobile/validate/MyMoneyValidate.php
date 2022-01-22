<?php
/**
 * FileName: 我的钱包验证码器
 * Description: 用于验证我的钱包相关字段
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-01-22 23:15
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\validate;


class MyMoneyValidate extends \think\Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'withdraw_money'   => 'require',
        'withdraw_account' => 'require',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'withdraw_money.require'   => '请填写金额',
        'withdraw_account.require' => '请选择提现方式'
    ];

    /**
     * 我的钱包发送验证码验证
     */
    public function sceneMyMoneySendCode(): MyMoneyValidate
    {
        return $this->only(['withdraw_money', 'withdraw_account']);
    }
}