<?php
/**
 * FileName: 后台限制IP访问中间件
 * Description: 用于鉴别指定IP地址访问后台
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-11-27 23:35
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\middleware;


use think\facade\Db;
use think\facade\Request;

class CheckIpMiddleware
{
    /**
     * 处理请求
     * @param $request
     * @param \Closure $next
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function handle($request, \Closure $next)
    {
        //查询系统信息
        $system = Db::name('system')->where('id', 1)->field('ip')->find();
        //判断允许访问的IP是否为空
        if (!empty($system['ip'])) {
            //查询到的ip字段分割成数组的形式
            $pieces = explode("|", $system['ip']);
            //获取当前客户端的IP地址
            $ip = Request::ip();
            //要检测的ip拆分成数组
            $checkIpArr = explode('.', $ip);
            if (!in_array($ip, $pieces)) {
                foreach ($pieces as $val) {
                    //发现有*号替代符
                    if (strpos($val, '*') !== false) {
                        $arr = explode('.', $val);
                        //用于记录循环检测中是否有匹配成功的
                        $res = true;
                        for ($i = 0; $i < count($pieces); $i++) {
                            //不等于*  就要进来检测，如果为*符号替代符就不检查
                            if ($arr[$i] !== '*') {
                                if ($arr[$i] !== $checkIpArr[$i]) {
                                    $res = false;
                                    //终止检查本个ip 继续检查下一个ip
                                    break;
                                }
                            }
                        }
                        //如果是true则找到有一个匹配成功的就返回
                        if ($res) {
                            echo '当前IP地址禁止访问';
                            header('HTTP/1.1 403 Forbidden');
                            die;
                        }
                    }
                }
            }
        }
        return $next($request);
    }
}