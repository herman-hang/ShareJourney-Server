<?php
/**
 * FileName: 设置跨域中间件
 * Description: 处理跨域问题
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-25 11:35
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\middleware;


use think\Response;

class CrossDomain
{
    /**
     * 设置跨域
     * @param $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Max-Age: 1800');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With');
        if (strtoupper($request->method()) == "OPTIONS") {
            throw new \think\exception\HttpResponseException(Response::create());
        }
        return $next($request);
    }
}