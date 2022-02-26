<?php
/**
 * FileName: 支付验证器
 * Description: 验证提交的支付参数
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-02-23 21:09
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\validate;


use think\Validate;

class PayValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'code' => 'require',
        'site' => 'require',
        'type' => 'require',
        'trip' => 'require',
        'line' => 'require'
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'code.require' => '微信登录临时凭证不能为空！',
        'site.require' => '旅途地址不能为空！',
        'type.require' => '订单类型不能为空！',
        'trip.require' => '出行信息不能为空！',
        'line.require' => '出行轨迹线数据不能为空！',
    ];

    /**
     * 微信小程序发起支付
     * @return PayValidate
     */
    public function sceneWechatPay(): PayValidate
    {
        return $this->only(['code', 'site', 'type', 'trip', 'line']);
    }

    /**
     * 车主出发参数验证
     * @return PayValidate
     */
    public function sceneOwnerStart(): PayValidate
    {
        return $this->only(['site', 'type', 'trip', 'line']);
    }
}