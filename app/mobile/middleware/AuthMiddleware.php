<?php
/**
 * FileName: 登录鉴权中间件
 * Description: 用于控制接口权限
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-01-15 20:04
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\middleware;


use thans\jwt\exception\JWTException;
use thans\jwt\facade\JWTAuth;
use think\facade\Config;
use think\facade\Db;
use think\facade\Request;
use think\facade\Cache;

class AuthMiddleware
{
    /**
     * 检测登录中间件
     * @param $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // 当前访问的控制器
        $controller = Request::controller();
        // 当前访问的方法
        $action = Request::action();
        // 拼接url
        $url = strtolower($controller . '/' . $action);
        //获取不需要验证登录和权限的路由
        $notAuthRoute = Config::get('auth');
        // 将数组中的每一项全部转为小写
        $ontLogin = array_map('strtolower', $notAuthRoute['not_login']);
        // $url存在数组则跳过，不存在开始检测登录
        if (!in_array($url, $ontLogin)) {
            try {
                // 验证token, 并获取token中的payload部分
                $token = JWTAuth::auth();
                //获取token的有效时间
                $expTime = $token['exp']->getValue();
                //如果JWT的有效时间小于一天则刷新token并返回给客户端
                if ($expTime - time() < 86400) {
                    //刷新token，会将旧token加入黑名单
                    $newToken = JWTAuth::refresh();
                    header('Access-Control-Expose-Headers:Authorization');
                    header('Authorization:bearer ' . $newToken);
                }
                //向控制器传当前管理员的ID
                $request->uid = $token['uid']->getValue();
                // 检测当前用户在当前终端是否已经在线
                $this->checkLogin($token);
            } catch (JWTException $e) {
                // 状态码-1为token在黑名单宽限期列表中，这是应该继续放行
                if ($e->getCode() !== -1) {
                    try {
                        // 记录退出的时间和IP地址
                        $expToken = JWTAuth::auth(false);
                        $id       = $expToken['uid']->getValue();
                        Db::name('user')->where('id', $id)->update(['lastlog_time' => time(), 'lastlog_ip' => Request::ip()]);
                    } catch (\Exception $exception) {
                        show(403, $exception->getMessage());
                    }
                    show(0, "登录超时！");
                }
            }
        }
        return $next($request);
    }

    /**
     * 检测当前用户在当前终端是否已经在线
     * @param array $token
     */
    protected function checkLogin(array $token)
    {
        // 获取缓存
        $data = Cache::get("user:{$token['uid']->getValue()}-platform:{$token['platform']->getValue()}");
        if (!empty($data) && $token['key']->getValue() !== $data['key']) {
            // 将token拉入黑名单
            JWTAuth::refresh();
            // base64解码
            $time = date("Y-m-d :H:i:s", base64_decode($data['key']));
            show(0, "当前账号 {$time} 正在另一台设备进行登录，记录IP地址：{$data['ip']}，非本人请尽快修改密码！");
        } else if (empty($data)) {
            // 将token拉入黑名单
            JWTAuth::refresh();
            show(0, "登录异常！");
        }
    }
}