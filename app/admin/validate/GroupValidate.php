<?php
/**
 * FileName: 权限组验证器
 * Description: 验证权限相关参数
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-05 16:00
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\validate;


class GroupValidate extends \think\Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'name' => 'require',
        'rules' => 'require',
        'status' => 'require'
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'name.require' => '权限组名称不能为空！',
        'rules.require' => '请选择权限！',
        'status.require' => '请选中状态！'
    ];

    /**
     * 权限组添加
     * @return GroupValidate
     */
    public function sceneAdd(): GroupValidate
    {
        return $this->only(['name', 'rules']);
    }

    /**
     * 权限组编辑
     * @return GroupValidate
     */
    public function sceneEdit(): GroupValidate
    {
        return $this->only(['status']);
    }
}