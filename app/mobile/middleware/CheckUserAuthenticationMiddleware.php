<?php
/**
 * FileName: 实名认证验证中间件
 * Description: 用于验证当前用户是否已经实名
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-02-26 15:30
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\middleware;


use think\facade\Db;

class CheckUserAuthenticationMiddleware
{
    /**
     * 验证当前用户是否已经实名
     * @param $request
     * @param \Closure $next
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function handle($request, \Closure $next)
    {
        $user = Db::name('user')->where('id', $request->uid)->field(['name', 'card'])->find();
        if (empty($user['name']) || empty($user['card'])) {
            show(403, "抱歉！您还未实名！");
        }
        return $next($request);
    }
}