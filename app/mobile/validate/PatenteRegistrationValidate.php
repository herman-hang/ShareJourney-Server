<?php
/**
 * FileName: 驾驶证行驶证验证器
 * Description: 用于验证驾驶证行驶证字段
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-02-08 23:29
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\validate;


class PatenteRegistrationValidate extends \think\Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'patente_url'      => 'require',
        'registration_url' => 'require',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'patente_url.require'      => '请上传驾驶证！',
        'registration_url.require' => '请上传行驶证！',
    ];

    /**
     * 验证驾驶证
     * @return PatenteRegistrationValidate
     */
    public function scenePatente(): PatenteRegistrationValidate
    {
        return $this->only(['patente_url']);
    }

    /**
     * 验证行驶证
     * @return PatenteRegistrationValidate
     */
    public function sceneRegistration(): PatenteRegistrationValidate
    {
        return $this->only(['registration_url']);
    }
}