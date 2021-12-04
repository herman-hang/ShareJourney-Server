<?php
/**
 * FileName: 系统设置验证器
 * Description: 验证系统设置相关参数
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-04 10:38
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\validate;


use think\Validate;

class SystemValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'email' => 'email',
        'qq' => 'length:5,11|number',
        'usergroup' => 'length:6,9|number',
        'max_logerror' => 'number',
        'access' => 'require',
        'file_storage' => 'require',
        'images_storage' => 'require',
        'wxpay_switch' => 'require',
        'alipay_switch' => 'require',
        'qqpay_switch' => 'require',
        'epay_switch' => 'require',
        'qqlogin_switch' => 'require',
        'weixinlogin_switch' => 'require',
        'sinalogin_switch' => 'require',
        'giteelogin_switch' => 'require'
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'email' => '邮箱格式错误！',
        'qq.length' => 'QQ只能在5到11位！',
        'qq.number' => 'QQ号必须纯数字！',
        'usergroup.length' => 'QQ群号只能在6到9位！',
        'usergroup.number' => 'QQ群必须纯数字！',
        'max_logerror.number' => '允许登录错误必须是纯数字！',
        'access.require' => '后台入口不为空！',
        'file_storage.require' => '请选择文件存储区域！',
        'images_storage.require' => '请选择图片存储区域！',
        'wxpay_switch.require' => '请选择微信支付开关！',
        'alipay_switch.require' => '请选择支付宝支付开关！',
        'qqpay_switch.require' => '请选择QQ支付开关！',
        'epay_switch.require' => '请选择易支付支付开关！',
        'qqlogin_switch.require' => '请选择QQ登录开关！',
        'weixinlogin_switch.require' => '请选择微信登录开关！',
        'sinalogin_switch.require' => '请选择微博登录开关！',
        'giteelogin_switch.require' => '请选择Gitee登录开关！'
    ];

    /**
     * 网站设置编辑
     * @return SystemValidate
     */
    public function sceneSystemEdit(): SystemValidate
    {
        return $this->only(['email', 'qq', 'usergroup']);
    }

    /**
     * 安全配置编辑
     * @return SystemValidate
     */
    public function sceneSecurityEdit(): SystemValidate
    {
        return $this->only(['max_logerror', 'file_storage', 'images_storage', 'access']);
    }

    /**
     * 开关管理编辑
     * @return SystemValidate
     */
    public function sceneSwitchEdit(): SystemValidate
    {
        return $this->only(['wxpay_switch', 'alipay_switch', 'qqpay_switch', 'epay_switch', 'qqlogin_switch', 'weixinlogin_switch', 'sinalogin_switch', 'giteelogin_switch']);
    }
}