<?php
/**
 * FileName: 系统设置控制器
 * Description: 用于处理系统设置的相关业务逻辑
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-03-07 14:39
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\controller;


use app\mobile\validate\SystemValidate;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Request;

class SystemController extends CommonController
{
    /**
     * 换绑手机号码发送验证码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendChangeMobileCode()
    {
        // 查询当前用户的手机号码
        $user = Db::name('user')->where('id', request()->uid)->field(['mobile'])->find();
        if (empty($user['mobile'])) {
            show(403, "绑定的手机号码为空！");
        }
        // 滑动验证码二次验证
        $captchaData = Cache::get('slider_captcha_' . Request::ip());
        (new CaptchaController())->verification($captchaData);
        // 进行短信发送
        $system       = Db::name('system')->where('id', '1')->field('name')->find();
        $sms          = Db::name('sms')->where('id', 1)->field('change_mobile_id,sms_type')->find();
        $data['code'] = code_str(2);
        if ($sms['sms_type'] == '0') { // ThinkAPI
            $smsData['temp_id'] = $sms['change_mobile_id'];
            $smsData['type']    = 0;
            $smsData['params']  = ['code' => $data['code']];
        } else { // 短信宝
            $smsData['content'] = "【{$system['name']}】您正在换绑手机号码，验证码为{$data['code']}，有效期为5分钟。";
            $smsData['type']    = 1;
        }
        $smsData['mobile'] = $user['mobile'];
        if (!empty($smsData)) {
            Queue::push('app\job\SendSmsJob', $smsData, 'mobile');
        }
        Cache::set('send_change_mobile_code_' . Request::ip(), $data, 600);
        show(200, "发送成功！");
    }

    /**
     * 换绑手机号码提交
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeMobile()
    {
        // 接收数据
        $data = Request::only(['mobile', 'code', 'verification']);
        // 验证数据
        $validate = new SystemValidate();
        if (!$validate->sceneChangeMobile()->check($data)) {
            show(403, $validate->getError());
        }
        // 验证码
        $codeInfo = Cache::get('send_change_mobile_code_' . Request::ip());
        if (empty($codeInfo)) {
            show(403, "验证码已过期！");
        }
        if ($data['code'] !== $codeInfo['code']) {
            show(403, "验证码错误！");
        }
        // 查询换绑的手机号码是否已经存在
        $user = Db::name('user')->where('mobile', $data['mobile'])->field(['id'])->find();
        if (!empty($user)) {
            if ($user['id'] !== request()->uid) {
                show(403, "手机号码已存在！");
            } else {
                show(403, "新手机号码即为绑定的手机号码");
            }
        }
        // 滑动验证码最终验证
        (new CaptchaController())->checkParam($data['verification']);
        // 进行换绑
        $res = Db::name('user')->where('id', request()->uid)->update(['mobile' => $data['mobile']]);
        if ($res) {
            // 删除滑块验证码缓存信息
            Cache::delete('slider_captcha_' . Request::ip());
            // 删除验证码
            Cache::delete('send_change_mobile_code_' . Request::ip());
            show(200, '换绑成功！');
        } else {
            show(403, "换绑失败！");
        }
    }

    /**
     * 查询邮箱地址
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function queryEmail()
    {
        $user = Db::name('user')->where('id', request()->uid)->field(['email'])->find();
        if (!empty($user)) {
            $explodeEmail = explode("@", $user['email']);
            //邮箱前缀
            $prefix        = (strlen($explodeEmail[0]) < 4) ? "" : substr($user['email'], 0, 3);
            $count         = 0;
            $str           = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $user['email'], -1, $count);
            $user['email'] = $prefix . $str;
        } else {
            $user['email'] = null;
        }
        show(200, "获取数据成功！", $user);
    }

    /**
     * 邮箱换绑发送验证码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendChangeEmailCode()
    {
        // 查询当前用户的信息
        $user = Db::name('user')->where('id', request()->uid)->field('email,nickname')->find();
        if (empty($user['email'])) {
            show(403, "绑定的邮箱地址为空！");
        }
        // 滑动验证码二次验证
        $captchaData = Cache::get('slider_captcha_' . Request::ip());
        (new CaptchaController())->verification($captchaData);
        // 进行邮件发送
        $system               = Db::name('system')->where('id', 1)->field('name')->find();
        $data['code']         = code_str();
        $emailData['title']   = "邮箱换绑验证码";
        $emailData['email']   = $user['email'];
        $emailData['user']    = $user['nickname'];
        $emailData['content'] = "您正在<strong>{$system['name']}</strong>用户中心进行邮箱换绑，验证码为<h2>{$data['code']}</h2>验证码在五分钟内有效。如非本人操作，请忽略本邮件";
        // 发送通知邮件
        if (!empty($user['email'])) {
            Queue::push('app\job\SendEmailJob', $emailData, 'mobile');
        }
        Cache::set('send_change_email_code_' . Request::ip(), $data, 600);
        show(200, "发送成功！");
    }

    /**
     * 邮箱换绑提交
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeEmail()
    {
        // 接收数据
        $data = Request::only(['email', 'code', 'verification']);
        // 验证数据
        $validate = new SystemValidate();
        if (!$validate->sceneChangeEmail()->check($data)) {
            show(403, $validate->getError());
        }
        // 验证码
        $codeInfo = Cache::get('send_change_email_code_' . Request::ip());
        if (empty($codeInfo)) {
            show(403, "验证码已过期！");
        }
        if ($data['code'] !== $codeInfo['code']) {
            show(403, "验证码错误！");
        }
        // 查询换绑的邮箱的地址是否已经存在
        $user = Db::name('user')->where('email', $data['email'])->field(['id'])->find();
        if (!empty($user)) {
            if ($user['id'] !== request()->uid) {
                show(403, "邮箱地址已存在！");
            } else {
                show(403, "新邮箱即为绑定的邮箱地址");
            }
        }
        // 滑动验证码最终验证
        (new CaptchaController())->checkParam($data['verification']);
        // 进行换绑
        $res = Db::name('user')->where('id', request()->uid)->update(['email' => $data['email']]);
        if ($res) {
            // 删除滑块验证码缓存信息
            Cache::delete('slider_captcha_' . Request::ip());
            // 删除验证码
            Cache::delete('send_change_email_code_' . Request::ip());
            show(200, '换绑成功！');
        } else {
            show(403, "换绑失败！");
        }
    }

    /**
     * 邮箱绑定验证码发送
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendBindEmailCode()
    {
        // 接收数据
        $data = Request::only(['email']);
        // 验证数据
        $validate = new SystemValidate();
        if (!$validate->sceneBindEmailSendCode()->check($data)) {
            show(403, $validate->getError());
        }
        // 滑动验证码二次验证
        $captchaData = Cache::get('slider_captcha_' . Request::ip());
        (new CaptchaController())->verification($captchaData);
        // 查询当前用户的信息
        $user = Db::name('user')->where('id', request()->uid)->field('nickname')->find();
        // 进行邮件发送
        $system               = Db::name('system')->where('id', 1)->field('name')->find();
        $data['code']         = code_str();
        $emailData['title']   = "邮箱绑定验证码";
        $emailData['email']   = $data['email'];
        $emailData['user']    = $user['nickname'];
        $emailData['content'] = "您正在<strong>{$system['name']}</strong>用户中心进行邮箱绑定，验证码为<h2>{$data['code']}</h2>验证码在五分钟内有效。如非本人操作，请忽略本邮件";
        // 发送通知邮件
        if (!empty($data['email'])) {
            Queue::push('app\job\SendEmailJob', $emailData, 'mobile');
        }
        Cache::set('send_bind_email_code_' . Request::ip(), $data, 600);
        show(200, "发送成功！");
    }

    /**
     * 绑定邮箱地址
     * @throws \think\db\exception\DbException
     */
    public function bindEmail()
    {
        // 接收数据
        $data = Request::only(['email', 'code', 'verification']);
        // 验证数据
        $validate = new SystemValidate();
        if (!$validate->sceneBindEmail()->check($data)) {
            show(403, $validate->getError());
        }
        // 验证码
        $codeInfo = Cache::get('send_bind_email_code_' . Request::ip());
        if (empty($codeInfo)) {
            show(403, "验证码已过期！");
        }
        // 统一将字母转为小写再判断
        if (strtolower($data['code']) !== strtolower($codeInfo['code'])) {
            show(403, "验证码错误！");
        }
        // 滑动验证码最终验证
        (new CaptchaController())->checkParam($data['verification']);
        // 进行绑定
        $res = Db::name('user')->where('id', request()->uid)->update(['email' => $data['email']]);
        if ($res) {
            // 删除滑块验证码缓存信息
            Cache::delete('slider_captcha_' . Request::ip());
            // 删除验证码
            Cache::delete('send_bind_email_code_' . Request::ip());
            show(200, "绑定成功！");
        } else {
            show(403, "绑定失败！");
        }
    }
}