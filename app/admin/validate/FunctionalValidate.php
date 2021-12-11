<?php
/**
 * FileName: 功能配置验证器
 * Description: 验证配置参数
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-11 15:03
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\validate;


class FunctionalValidate extends \think\Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'sms_type' => 'require',
        'bind_mobile'=>'number',
        'relieve_mobile'=>'number'
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'alipay_type.require' => '请选择支付宝支付接口类型！',
        'sms_type.require' => '请选择短信接口类型！',
        'bind_mobile.number'=>'绑定手机号码模板ID必须是数字！',
        'relieve_mobile.number'=>'解除手机号码模板ID必须是数字！'
    ];

    /**
     * 编辑短信
     * @return FunctionalValidate
     */
    public function sceneSmsEdit(): FunctionalValidate
    {
        return $this->only(['sms_type','bind_mobile','relieve_mobile']);
    }
}