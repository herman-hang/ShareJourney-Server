<?php
/**
 * FileName: 首页验证器
 * Description: 处理首页业务逻辑
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-02-26 21:14
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\validate;


use think\Validate;

class IndexValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'id'     => 'require',
        'status' => 'require|in:5,6',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'id.require'     => '旅途信息ID不能为空！',
        'status.require' => '变更状态不能为空！',
        'status.in'      => '变更状态只能是5和6！'
    ];

    /**
     * 确认订单/取消订单验证
     * @return IndexValidate
     */
    public function sceneEditOrder(): IndexValidate
    {
        return $this->only(['id','status']);
    }
}