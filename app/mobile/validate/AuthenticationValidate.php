<?php
/**
 * FileName: 实名认证验证器
 * Description: 用于验证实名认证相关字段
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-02-07 22:40
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\validate;

use think\Validate;

class AuthenticationValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'card_front' => 'require',
        'card_verso' => 'require',
        'card'       => 'idCard|require',
        'name'       => 'chs|require'
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'card_front.require' => '请上传身份证正面！',
        'card_verso.require' => '请上传身份证反面！',
        'name.require'       => '真实姓名不能为空！',
        'card.require'       => '身份证号码不能为空！',
        'card.idCard'        => '身份证号码格式错误！',
    ];

    /**
     * 实名认证下一步验证
     * @return AuthenticationValidate
     */
    public function sceneAuthNext(): AuthenticationValidate
    {
        return $this->only(['card_front', 'card_verso']);
    }

    /**
     * 实名认证提交验证
     * @return AuthenticationValidate
     */
    public function sceneAuthSubmit(): AuthenticationValidate
    {
        return $this->only(['name', 'card']);
    }
}