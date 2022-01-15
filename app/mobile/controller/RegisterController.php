<?php
/**
 * FileName: 用户注册控制器
 * Description: 处理用户注册业务逻辑
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-01-15 17:41
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\controller;


use app\mobile\model\UserModel;
use app\mobile\validate\LoginValidate;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Request;

class RegisterController extends CommonController
{
    /**
     * 用户注册
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function register()
    {
        // 查询用户注册是否关闭
        $switch = Db::name('switch')->where('id', 1)->field('register_switch')->find();
        if ($switch['register_switch'] === 0) {
            show(403, "注册已关闭！");
        }
        // 接收数据
        $data = Request::only(['password', 'code']);
        // 验证数据
        $validate = new LoginValidate();
        if (!$validate->sceneRegister()->check($data)) {
            show(403, $validate->getError());
        }
        // 验证验证码是否正确
        $codeInfo = Cache::get('send_register_code_' . Request::ip());
        if ($data['code'] !== $codeInfo['code']) {
            show(403, "验证码错误！");
        }
        $data['mobile'] = $codeInfo['mobile'];
        // 生成唯一用户名
        $data['user'] = self::random();
        // 密码加密
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        // 新增
        $res = UserModel::create($data);
        if ($res) {
            // 删除验证码
            Cache::delete('send_register_code_' . Request::ip());
            show(200, "注册成功！");
        } else {
            show(403, "注册失败！");
        }
    }

    /**
     * 注册发送验证码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendRegisterCode()
    {
        // 查询用户注册是否关闭
        $switch = Db::name('switch')->where('id', 1)->field('register_switch')->find();
        if ($switch['register_switch'] === 0) {
            show(403, "注册已关闭！");
        }
        // 接收数据
        $data = Request::only(['mobile']);
        // 验证数据
        $validate = new LoginValidate();
        if (!$validate->sceneSendRegisterCode()->check($data)) {
            show(403, $validate->getError());
        }
        // 进行短信发送
        $system       = Db::name('system')->where('id', '1')->field('name')->find();
        $sms          = Db::name('sms')->where('id', 1)->field('register_id')->find();
        $data['code'] = code_str(2);
        if ($sms['sms_type'] == '0') { // ThinkAPI
            $smsData['temp_id'] = $sms['register_id'];
            $smsData['type']    = 0;
            $smsData['params']  = ['code' => $data['code']];
        } else { // 短信宝
            $smsData['content'] = "【{$system['name']}】您正在注册成为新用户，验证码为{$data['code']}，有效期为5分钟。";
            $smsData['type']    = 1;
        }
        $smsData['mobile'] = $data['mobile'];
        if (!empty($smsData)) {
            Queue::push('app\job\SendSmsJob', $smsData, 'mobile');
        }
        // 对手机号码和验证码进行缓存，方便验证和注册
        Cache::set('send_register_code_' . Request::ip(), $data, 600);
        show(200, "发送成功！");
    }
}