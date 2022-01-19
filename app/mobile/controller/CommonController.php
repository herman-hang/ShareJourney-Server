<?php
/**
 * FileName: 公共类
 * Description: 存放公共方法
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-01-15 10:03
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\controller;


use app\mobile\middleware\AuthMiddleware;
use think\facade\Db;

class CommonController extends \app\BaseController
{
    /**
     * 检测登录中间件调用
     * @var string[]
     */
    protected $middleware = [AuthMiddleware::class];

    /**
     * 上传文件
     * 支持文件name:image或者name:file
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function upload()
    {
        // 获取表单上传文件
        uploadFile(request()->file());
    }

    /**
     * 生成唯一用户名
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected static function random(): int
    {
        $number = rand(10000, 9999999999);
        $user   = Db::name('user')->where('user', $number)->find();
        if (!empty($user)) {
            self::random();
        } else {
            return $number;
        }
    }

}