<?php
/**
 * FileName: 登录控制器
 * Description: 处理相关业务
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-01-15 10:37
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\controller;


use app\admin\model\SystemModel;
use app\admin\model\UserModel;
use app\mobile\validate\LoginValidate;
use thans\jwt\facade\JWTAuth;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Request;
use wlt\wxmini\WXBizDataCrypt;
use wlt\wxmini\WXLoginHelper;

class LoginController extends CommonController
{
    /**
     * 登录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login()
    {
        //接收数据
        $data = Request::only(['user', 'password', 'platform']);
        //验证数据
        $validate = new LoginValidate();
        if (!$validate->sceneLogin()->check($data)) {
            show(403, $validate->getError());
        }
        // 查询当前用户
        $user = UserModel::where('user|email|mobile', $data['user'])->field('id,password,status,login_sum,error_time,login_error,ban_time')->find();
        //查询系统信息
        $system = SystemModel::where('id', 1)->field('max_logerror')->find();
        //记录登录错误时间,1800秒后所有的记录清零
        $errorTime = time() + 1800;
        //封禁时间常量，单位：分钟
        $BAN = 30;
        //登录错误次数到达后的封禁时间
        $banTime = time() + $BAN * 60;
        // 记录IP地址
        $ip = Request::ip();
        if (empty($user)) {//用户不存在
            show(401, '用户不存在！');
        } else {//管理员存在
            if ($user['login_error'] == 0) {//不存在密码登录错误的情况
                if ($user['status'] == 0) {//用户已停用
                    show(403, '用户已停用！');
                } else {//管理员状态正常（已启用状态）
                    //哈希加密
                    if (password_verify($data['password'], $user['password'])) {//密码正确
                        //登录总次数自增1
                        $user->inc('login_sum')->update();
                        $tokenInfo = ['uid' => $user['id'], 'key' => base64_encode(time()), 'platform' => $data['platform'], 'ip' => $ip];
                        //参数为用户认证的信息，请自行添加
                        $token = JWTAuth::builder($tokenInfo);
                        // 缓存key，限制每个账号在每个终端只允许登录一个
                        Cache::set("user:{$user['id']}-platform:{$data['platform']}", $tokenInfo);
                        show(200, '登录成功！', ['Authorization' => 'bearer ' . $token]);
                    } else {
                        //记录密码登录错误时间,便于$errorTime分钟后对登录错误次数清零
                        $user->save(['error_time' => $errorTime]);
                        //密码登录错误次数自增1
                        $user->inc('login_error')->update();
                        show(403, '密码错误！');
                    }
                }
            } else {//存在密码登录错误情况
                //解除登录错误的计算时间，恢复初始化
                if ($user->getData('error_time') <= time()) {
                    //将登录错误错误时间,登录错误次数清零
                    $user->save(['error_time' => NULL, 'login_error' => 0]);
                    //判断防止封禁时间大于等于登录错误时间出现的BUG
                    if ($user->getData('ban_time') == NULL || $user->getData('ban_time') <= time()) {
                        //将封禁时间设置为空
                        $user->save(['ban_time' => NULL]);
                        if ($user['status'] == 0) {
                            show(403, '用户已停用！');
                        } else {
                            if (password_verify($data['password'], $user['password'])) {//密码正确
                                //登录总次数自增1
                                $user->inc('login_sum')->update();
                                $tokenInfo = ['uid' => $user['id'], 'key' => base64_encode(time()), 'platform' => $data['platform'], 'ip' => $ip];
                                //参数为用户认证的信息，请自行添加
                                $token = JWTAuth::builder($tokenInfo);
                                // 缓存key，限制每个账号在每个终端只允许登录一个
                                Cache::set("user:{$user['id']}-platform:{$data['platform']}", $tokenInfo);
                                show(200, '登录成功！', ['Authorization' => 'bearer ' . $token]);
                            } else {//密码错误
                                //记录密码登录错误时间,便于$errorTime分钟后对登录错误次数清零
                                $user->save(['error_time' => $errorTime]);
                                //登录密码错误次数自增1
                                $user->inc('login_error')->update();
                                //获取当前登录错误次数
                                $errorCount = $user->getData('login_error');
                                //获取允许登录错误的最大次数
                                $maxError = $system->getData('max_logerror');
                                $count    = $maxError - $errorCount;
                                show(403, "登录密码错误，还有{$count}次机会！");
                            }
                        }
                    } else {
                        //计算剩余多少分钟解封，这里强制转为int类型
                        $time = (int)(($user->getData('ban_time') - time()) / 60);
                        show(403, "登录错误过多，请{$time}分钟后再试！");
                    }
                } else {
                    //判断当前的封禁时间是否为空
                    if ($user->getData('ban_time') == NULL || $user->getData('ban_time') <= time()) {
                        //将封禁时间设置为空
                        $user->save(['ban_time' => NULL]);
                        if ($user['status'] == 0) {
                            show(403, '用户已停用！');
                        } else {
                            //判断登录错误次数是否大于或等于指定登录错误次数
                            if ($user->getData('login_error') >= $system->getData('max_logerror')) {
                                //封禁时间写入
                                $user->save(['ban_time' => $banTime]);
                                show(403, "登录错误过多，请{$BAN}分钟后再试！");
                            } else {
                                if (password_verify($data['password'], $user['password'])) {
                                    //登录总次数自增1
                                    $user->inc('login_sum')->update();
                                    //登录错误次数清零,登录错误时间清空
                                    $user->save(['login_error' => 0, 'error_time' => NULL]);
                                    $tokenInfo = ['uid' => $user['id'], 'key' => base64_encode(time()), 'platform' => $data['platform'], 'ip' => $ip];
                                    //参数为用户认证的信息，请自行添加
                                    $token = JWTAuth::builder($tokenInfo);
                                    // 缓存key，限制每个账号在每个终端只允许登录一个
                                    Cache::set("user:{$user['id']}-platform:{$data['platform']}", $tokenInfo);
                                    show(200, '登录成功！', ['Authorization' => 'bearer ' . $token]);
                                } else {
                                    //记录密码登录错误时间,便于$errorTime分钟后对登录错误次数清零
                                    $user->save(['error_time' => $errorTime]);
                                    //登录密码错误次数自增1
                                    $user->inc('login_error')->update();
                                    //获取当前登录错误次数
                                    $errorCount = $user->getData('login_error');
                                    //获取允许登录错误最大次数
                                    $maxError = $system->getData('max_logerror');
                                    $count    = $maxError - $errorCount;
                                    show(403, "密码错误，还有{$count}次机会！");
                                }
                            }
                        }
                    } else {
                        //计算剩余多少分钟解封，这里强制转为int类型
                        $time = (int)(($user->getData('ban_time') - time()) / 60);
                        show(403, "登录错误过多，请{$time}分钟后再试！");
                    }
                }
            }
        }
    }

    /**
     * 退出登录
     * @throws \think\db\exception\DbException
     */
    public function loginOut()
    {
        // 更新
        $res = Db::name('user')->where('id', request()->uid)->update(['lastlog_time' => time(), 'lastlog_ip' => Request::ip()]);
        if ($res) {
            // 刷新token
            JWTAuth::refresh();
            show(200, "退出成功！");
        } else {
            show(403, "退出失败！");
        }
    }

    /**
     * 短信登录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function smsLogin()
    {
        // 接收数据
        $data = Request::only(['mobile', 'code', 'verification', 'platform']);
        // 滑动验证码最终验证
        (new CaptchaController())->checkParam($data['verification']);
        // 验证数据
        $validate = new LoginValidate();
        if (!$validate->sceneSmsLogin()->check($data)) {
            show(403, $validate->getError());
        }
        // 验证码
        $codeInfo = Cache::get('send_login_code_' . Request::ip());
        if ($data['code'] !== $codeInfo['code']) {
            show(403, "验证码错误！");
        }
        $user = UserModel::where('mobile', $data['mobile'])->field('id,password,status,login_sum,error_time,login_error,ban_time')->find();
        if (empty($user)) {
            show(403, "用户不存在！");
        } else if ($user['status'] == 0) {
            show(403, "用户已停用！");
        } else {
            //登录总次数自增1
            $user->inc('login_sum')->update();
            $tokenInfo = ['uid' => $user['id'], 'key' => base64_encode(time()), 'platform' => $data['platform'], 'ip' => Request::ip()];
            //参数为用户认证的信息，请自行添加
            $token = JWTAuth::builder($tokenInfo);
            // 缓存key，限制每个账号在每个终端只允许登录一个
            Cache::set("user:{$user['id']}-platform:{$data['platform']}", $tokenInfo);
            show(200, '登录成功！', ['Authorization' => 'bearer ' . $token]);
        }
    }

    /**
     * 短信登录发送验证码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendLoginCode()
    {
        // 接收数据
        $data = Request::only(['mobile']);
        // 滑动验证码二次验证
        $captchaData = Cache::get('slider_captcha_' . Request::ip());
        (new CaptchaController())->verification($captchaData);
        // 验证数据
        $validate = new LoginValidate();
        if (!$validate->sceneLoginSendCode()->check($data)) {
            show(403, $validate->getError());
        }
        $isExist = Db::name('user')->where('mobile', $data['mobile'])->find();
        if (empty($isExist)) {
            show(403, "当前用户不存在！");
        }
        // 进行短信发送
        $system       = Db::name('system')->where('id', '1')->field('name')->find();
        $sms          = Db::name('sms')->where('id', 1)->field('sms_login_id,sms_type')->find();
        $data['code'] = code_str(2);
        if ($sms['sms_type'] == '0') { // ThinkAPI
            $smsData['temp_id'] = $sms['sms_login_id'];
            $smsData['type']    = 0;
            $smsData['params']  = ['code' => $data['code']];
        } else { // 短信宝
            $smsData['content'] = "【{$system['name']}】您正在使用短信登录，验证码为{$data['code']}，有效期为5分钟。";
            $smsData['type']    = 1;
        }
        $smsData['mobile'] = $data['mobile'];
        if (!empty($smsData)) {
            Queue::push('app\job\SendSmsJob', $smsData, 'mobile');
        }
        // 对手机号码和验证码进行缓存，方便验证和注册
        Cache::set('send_login_code_' . Request::ip(), $data, 600);
        show(200, "发送成功！");
    }

    /**
     * 微信授权登录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function weixinLogin()
    {
        // 接收数据
        $data = Request::only(['code', 'param', 'platform']);
        // 查询小程序密钥
        $mini          = Db::name('thirdparty')->where('id', 1)->field('wx_mini_appid,wx_mini_secret')->find();
        $key['appid']  = $mini['wx_mini_appid'];
        $key['secret'] = $mini['wx_mini_secret'];
        $wxHelper      = new WXLoginHelper($key);
        $result        = $wxHelper->checkLogin($data['code'], $data['param']['rawData'], $data['param']['signature'], $data['param']['encryptedData'], $data['param']['iv']);
        // 根据openid查询用户是否存在
        $user = UserModel::where('weixin_openid', $result['openId'])->find();
        if (!empty($user)) {
            //登录总次数自增1
            $user->inc('login_sum')->update();
            $tokenInfo = ['uid' => $user['id'], 'key' => base64_encode(time()), 'platform' => $data['platform'], 'ip' => Request::ip()];
            //参数为用户认证的信息，请自行添加
            $token = JWTAuth::builder($tokenInfo);
            // 缓存key，限制每个账号在每个终端只允许登录一个
            Cache::set("user:{$user['id']}-platform:{$data['platform']}", $tokenInfo);
            show(200, '授权登录成功！', ['Authorization' => 'bearer ' . $token, 'session3rd' => $result['session3rd']]);
        } else {
            $data['open_id'] = $result['openId'];
            Cache::set('bind_weixin_login_' . Request::ip(), $data, 600);
            show(401, "请绑定手机号码！");
        }
    }

    /**
     * 微信登录授权绑定手机发送验证码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function bindPhoneSendCode()
    {
        // 接收数据
        $data = Request::only(['mobile']);
        // 滑动验证码二次验证
        $captchaData = Cache::get('slider_captcha_' . Request::ip());
        (new CaptchaController())->verification($captchaData);
        // 验证数据
        $validate = new LoginValidate();
        if (!$validate->sceneWeixinLoginBindPhone()->check($data)) {
            show(403, $validate->getError());
        }
        // 进行短信发送
        $system       = Db::name('system')->where('id', '1')->field('name')->find();
        $sms          = Db::name('sms')->where('id', 1)->field('bind_id,sms_type')->find();
        $data['code'] = code_str(2);
        if ($sms['sms_type'] == '0') { // ThinkAPI
            $smsData['temp_id'] = $sms['bind_id'];
            $smsData['type']    = 0;
            $smsData['params']  = ['code' => $data['code']];
        } else { // 短信宝
            $smsData['content'] = "【{$system['name']}】您正在绑定手机号码，验证码为{$data['code']}，有效期为5分钟。";
            $smsData['type']    = 1;
        }
        $smsData['mobile'] = $data['mobile'];
        if (!empty($smsData)) {
            Queue::push('app\job\SendSmsJob', $smsData, 'mobile');
        }
        // 对手机号码和验证码进行缓存，方便验证和注册
        Cache::set('send_weixin_login_bind_code_' . Request::ip(), $data, 600);
        show(200, "发送成功！");
    }

    /**
     * 微信授权绑定手机并登录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function bindPhone()
    {
        $code = Request::only(['verification', 'code']);
        // 获取手机号码数据
        $data = Cache::get('send_weixin_login_bind_code_' . Request::ip());
        if ($code['code'] !== $data['code']) {
            show(403, "手机验证码错误！");
        }
        // 滑动验证码最终验证
        (new CaptchaController())->checkParam($code['verification']);
        // 获取微信授权的数据
        $weixin  = Cache::get('bind_weixin_login_' . Request::ip());
        $isExist = Db::name('user')->where('mobile', $data['mobile'])->find();
        if (!empty($isExist)) {
            show(403, "手机号码已注册！");
        }
        // 生成唯一用户名
        $user                  = self::random();
        $data['user']          = $user;
        $data['nickname']      = $weixin['param']['userInfo']['nickName'];
        $data['photo']         = $weixin['param']['userInfo']['avatarUrl'];
        $data['weixin_openid'] = $weixin['open_id'];
        switch ($weixin['param']['userInfo']['gender']) {
            case 0:
                $data['sex'] = 2;
                break;
            case 1:
                $data['sex'] = 1;
                break;
            case 2:
                $data['sex'] = 0;
                break;
            default :
                break;
        }
        // 密码加密
        $data['password'] = password_hash($weixin['code'], PASSWORD_BCRYPT);
        // 新增
        $res = UserModel::create($data);
        if ($res) {
            // 根据openid查询用户是否存在
            $user = UserModel::where('id', $res->id)->find();
            //登录总次数自增1
            $user->inc('login_sum')->update();
            $tokenInfo = ['uid' => $user['id'], 'key' => base64_encode(time()), 'platform' => $weixin['platform'], 'ip' => Request::ip()];
            //参数为用户认证的信息，请自行添加
            $token = JWTAuth::builder($tokenInfo);
            // 缓存key，限制每个账号在每个终端只允许登录一个
            Cache::set("user:{$user['id']}-platform:{$weixin['platform']}", $tokenInfo);
            // 删除缓存
            Cache::delete('send_weixin_login_bind_code_' . Request::ip());
            Cache::delete('bind_weixin_login_' . Request::ip());
            show(200, "绑定成功！", ['Authorization' => 'bearer ' . $token]);
        } else {
            show(403, "绑定失败！");
        }
    }
}