<?php
/**
 * FileName: 提现信息验证器
 * Description: 用于验证提现信息字段
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-02-08 21:41
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\validate;


use think\Validate;

class WithdrawInfoValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'alipay'         => 'require',
        'alipay_name'    => 'chs|require',
        'wxpay'          => 'require',
        'wxpay_name'     => 'chs|require',
        'bank_card'      => 'require',
        'bank_card_name' => 'chs|require',
        'bank_card_type' => 'require',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'alipay.require'         => '支付宝账户不能为空！',
        'alipay_name.require'    => '支付宝真实姓名不能为空！',
        'wxpay.require'          => '微信账户不能为空！',
        'wxpay_name.require'     => '微信真实姓名不能为空！',
        'bank_card.require'      => '银行卡号不能为空！',
        'bank_card_name.require' => '银行卡姓名不能为空！',
        'bank_card_type.require' => '银行卡类型不能为空！',
    ];

    /**
     * 提现信息验证
     * @return WithdrawInfoValidate
     */
    public function sceneWithdrawInfo(): WithdrawInfoValidate
    {
        return $this->only(['alipay', 'alipay_name', 'wxpay', 'wxpay_name', 'bank_card', 'bank_card_name', 'bank_card_type']);
    }
}