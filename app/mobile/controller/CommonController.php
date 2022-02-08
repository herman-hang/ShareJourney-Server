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
use app\mobile\model\UserOwnerModel;
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

    /**
     * PHP手机号或银行卡号保留前四位和后四位，其余替换成*号
     * @param string $str 待加密字符串
     * @param int $startLen 前几位
     * @param int $endLen 后几位
     * @return string|string[]|null
     */
    public function strReplace(string $str, int $startLen = 4, int $endLen = 4)
    {
        $repStr = "";
        if (strlen($str) < ($startLen + $endLen + 1)) {
            return $str;
        }
        $count = strlen($str) - $startLen - $endLen;
        for ($i = 0; $i < $count; $i++) {
            $repStr .= "*";
        }
        return preg_replace('/(\d{' . $startLen . '})\d+(\d{' . $endLen . '})/', '${1}' . $repStr . '${2}', $str);
    }

}