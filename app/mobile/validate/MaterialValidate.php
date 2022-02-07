<?php
/**
 * FileName: 个人资料验证器
 * Description: 用于验证个人资料字段
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-01-25 22:27
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\validate;


use think\Validate;

class MaterialValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'nickname'     => 'max:20',
        'age'          => 'between:0,120',
        'region'       => 'max:100',
        'qq'           => 'length:5,11',
        'introduction' => 'max:100',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'nickname.max'     => '昵称字数过多！',
        'age.between'      => '年龄只能是0到120岁之间！',
        'region.max'       => '地区字数过多！',
        'qq.length'        => 'QQ号码位数只能在5到11位之间！',
        'introduction.max' => '简介字数过多！',
    ];

    /**
     * 个人资料
     * @return MaterialValidate
     */
    public function sceneMaterial(): MaterialValidate
    {
        return $this->only(['nickname', 'age', 'region', 'qq', 'introduction']);
    }
}