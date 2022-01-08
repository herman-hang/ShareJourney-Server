<?php
/**
 * FileName: 车主验证器
 * Description: 验证车主相关参数
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-14 21:13
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\validate;


class OwnerValidate extends \think\Validate
{
    protected $rule = [
        'service'          => 'require|number',
        'km'               => 'require',
        'patente_url'      => 'require',
        'registration_url' => 'require',
        'car_url'          => 'require',
        'plate_number'     => 'require',
        'capacity'         => 'require|number',
        'color'            => 'require'
    ];

    protected $message = [
        'service.require'          => '平台服务费不能为空！',
        'service.number'           => '平台服务费只能填写数字！',
        'km.require'               => '请填写每公里收费！',
        'patente_url.require'      => '请上传驾驶证！',
        'registration_url.require' => '请上传行驶证！',
        'car_url.require'          => '请上传车辆图片！',
        'plate_number.require'     => '车辆号不能为空！',
        'capacity.require'         => '请填写可载人数！',
        'capacity.number'          => '可载人数只能是数字！',
        'color.require'            => '车辆颜色不能为空'
    ];

    /**
     * 编辑车主
     * @return OwnerValidate
     */
    public function sceneEdit(): OwnerValidate
    {
        return $this->only([
            'service',
            'km',
            'patente_url',
            'registration_url',
            'car_url',
            'plate_number',
            'capacity',
            'color'
        ]);
    }
}