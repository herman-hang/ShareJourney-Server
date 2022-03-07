<?php
/**
 * FileName: 密码找回控制器
 * Description: 用于处理用户密码找回业务逻辑
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-01-15 19:38
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\controller;


use app\mobile\validate\LoginValidate;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Request;

class PasswordController
{
    /**
     * 找回密码
     * @throws \think\db\exception\DbException
     */
    public function password()
    {
        // 接收数据
        $data = Request::only(['mobile', 'password', 'code', 'verification']);
        // 验证数据
        $validate = new LoginValidate();
        if (!$validate->scenePassword()->check($data)) {
            show(403, $validate->getError());
        }
        // 验证码
        $codeInfo = Cache::get('send_password_code_' . Request::ip());
        if (empty($codeInfo)) {
            show(403, "验证码已过期！");
        }
        if ($data['code'] !== $codeInfo['code']) {
            show(403, "验证码错误！");
        }
        // 滑动验证码最终验证
        (new CaptchaController())->checkParam($data['verification']);
        // 加密
        $password = password_hash($data['password'], PASSWORD_BCRYPT);
        $res      = Db::name('user')->where('mobile', $codeInfo['mobile'])->update(['password' => $password]);
        if ($res) {
            // 删除滑块验证码缓存信息
            Cache::delete('slider_captcha_' . Request::ip());
            // 删除验证码
            Cache::delete('send_password_code_' . Request::ip());
            show(200, "找回成功！");
        } else {
            show(403, "找回失败！");
        }
    }

    /**
     * 找回密码发送验证码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function SendPasswordCode()
    {
        // 接收数据
        $data = Request::only(['mobile']);
        // 滑动验证码二次验证
        $captchaData = Cache::get('slider_captcha_' . Request::ip());
        (new CaptchaController())->verification($captchaData);
        // 验证数据
        $validate = new LoginValidate();
        if (!$validate->scenePassSendCode()->check($data)) {
            show(403, $validate->getError());
        }
        $isExist = Db::name('user')->where('mobile', $data['mobile'])->find();
        if (empty($isExist)) {
            show(403, "当前手机号码不存在！");
        }
        // 进行短信发送
        $system       = Db::name('system')->where('id', '1')->field('name')->find();
        $sms          = Db::name('sms')->where('id', 1)->field('password_id,sms_type')->find();
        $data['code'] = code_str(2);
        if ($sms['sms_type'] == '0') { // ThinkAPI
            $smsData['temp_id'] = $sms['password_id'];
            $smsData['type']    = 0;
            $smsData['params']  = ['code' => $data['code']];
        } else { // 短信宝
            $smsData['content'] = "【{$system['name']}】您正在找回密码，验证码为{$data['code']}，有效期为5分钟。";
            $smsData['type']    = 1;
        }
        $smsData['mobile'] = $data['mobile'];
        if (!empty($smsData)) {
            Queue::push('app\job\SendSmsJob', $smsData, 'mobile');
        }
        // 对手机号码和验证码进行缓存，方便验证和注册
        Cache::set('send_password_code_' . Request::ip(), $data, 600);
        show(200, "发送成功！");
    }
}