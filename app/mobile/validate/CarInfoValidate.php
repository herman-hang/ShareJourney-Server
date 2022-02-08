<?php
/**
 * FileName: 车辆信息验证器
 * Description: 用于验证车辆信息字段
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-02-08 22:31
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\validate;


class CarInfoValidate extends \think\Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'car_url'      => 'require',
        'plate_number' => 'require',
        'capacity'     => 'require',
        'color'        => 'require'
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'car_url.require'      => '请上传车辆图片！',
        'plate_number.require' => '车牌号不能为空！',
        'capacity.require'     => '可载人数不能为空！',
        'color.require'        => '车辆颜色不能为空！'
    ];

    /**
     * 验证车辆信息
     * @return CarInfoValidate
     */
    public function sceneCarInfo(): CarInfoValidate
    {
        return $this->only(['car_url', 'plate_number', 'capacity', 'color']);
    }
}