<?php
/**
 * FileName: 用户验证器
 * Description: 验证用户相关参数
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-05 17:48
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\validate;


class UserValidate extends \think\Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'user' => 'require|length:5,15|alphaNum|unique:user,user',
        'password' => 'require|length:6,15',
        'passwords' => 'require|confirm:password',
        'card' => 'idCard',
        'age' => 'number|between:0,120',
        'status' => 'require',
        'sex' => 'require',
        'is_owner' => 'require',
        'email' => 'email|unique:user,email',
        'mobile' => 'mobile|unique:user,mobile',
        'qq' => 'length:5,11',
        'service'=>'require|integer',
        'km'=>'require',
        'patente_url'=>'require',
        'registration_url'=>'require',
        'car_url'=>'require',
        'plate_number'=>'require',
        'capacity'=>'require|number',
        'color'=>'require|chs',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'user.require' => '用户名不能为空！',
        'user.length' => '用户名只能在5到15位之间！',
        'user.alphaNum' => '用户名只能是字母和数字组成！',
        'user.unique' => '用户名已存在！',
        'password.require' => '密码不能为空！',
        'password.length' => '密码只能在6到15位之间！',
        'passwords.require' => '确认密码不能为空！',
        'passwords.confirm' => '两次密码不一致！',
        'card.idCard' => '身份证格式错误！',
        'status.require' => '请选择用户状态！',
        'sex.require' => '请选择性别！',
        'is_owner.require' => '请选择是否为车主！',
        'age.between' => '年龄只能在1到120岁之间！',
        'age.number' => '年龄必须是数字！',
        'email.email' => '邮箱格式错误！',
        'email.unique' => '邮箱已存在！',
        'mobile.unique' => '手机号码已存在！',
        'mobile.mobile' => '手机号码格式错误！',
        'qq.length' => 'QQ号码只能是5到11位之间！',
        'service.require' => '平台服务费不能为空！',
        'service.integer' => '平台服务费只能是整数！',
        'km.require'=>'每公里收费不能为空！',
        'patente_url.require'=>'请上传驾驶证！',
        'registration_url.require'=>'请上传行驶证！',
        'car_url.require'=>'请上传车辆图片！',
        'plate_number.require'=>'请填写车牌号！',
        'capacity.require'=>'请填写可载人数！',
        'capacity.number'=>'可载人数只能是数字！',
        'color.require'=>'车辆颜色不能为空！',
        'color.chs'=>'车辆颜色只能是汉字！'
    ];

    /**
     * 用户添加
     * @return UserValidate
     */
    public function sceneAdd(): UserValidate
    {
        return $this->only(['user', 'password', 'passwords', 'card', 'email', 'mobile', 'age', 'sex', 'status', 'is_developer', 'qq']);
    }

    /**
     * 用户编辑
     * @return UserValidate
     */
    public function sceneEdit(): UserValidate
    {
        return $this->only(['user', 'password', 'card', 'age', 'email', 'mobile', 'sex', 'is_developer', 'qq'])->remove('password', 'require');
    }

    /**
     * 车主相关参数验证
     * @return UserValidate
     */
    public function sceneCheckOwner(): UserValidate
    {
        return $this->only(['service','km','patente_url','registration_url','car_url','plate_number','capacity','color']);
    }
}