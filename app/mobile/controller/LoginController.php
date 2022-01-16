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
use think\facade\Request;

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
        // 刷新token
        JWTAuth::refresh();
        // 更新
        $res = Db::name('user')->where('id', request()->uid)->update(['lastlog_time' => time(), 'lastlog_ip' => Request::ip()]);
        if ($res) {
            show(200, "退出成功！");
        } else {
            show(403, "退出失败！");
        }
    }
}