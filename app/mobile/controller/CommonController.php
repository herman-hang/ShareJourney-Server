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

    /**
     * HTTP请求
     * @param string $url
     * @param array $param
     * @param string $method
     * @return false|string
     */
    public function http(string $url, array $param = [], string $method = 'GET')
    {
        $opts = ['http' => [
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($param)
        ]];
        return json_decode(file_get_contents($url, false, stream_context_create($opts)));
    }

    /**
     * 检测当前用户/车主是否有正在进行的订单
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkIndentStatus()
    {
        $oneself = Db::view('journey', 'user_id')
            ->view('journey_pass', 'status', 'journey_pass.journey_id=journey.id')
            ->where(['journey.user_id' => request()->uid, 'journey_pass.status' => '0'])
            ->find();
        $group   = Db::view('journey_user', 'user_id')
            ->view('journey_pass', 'status', 'journey_pass.journey_id=journey_user.journey_id')
            ->where(['journey_user.user_id' => request()->uid, 'journey_pass.status' => '0'])
            ->find();
        if (!empty($oneself) || !empty($group)) {
            show(403, "您有一个订单正在进行中！");
        }
    }

    /**
     * 获取出行的出发地和最终目的地
     * @param array $data 旅行路线数据
     * @return array
     */
    protected function getStartEnd(array $data): array
    {
        $siteCount = count($data['site']);
        if ($siteCount == 2) {
            $journey['start'] = $data['site'][0]['destination'];
            $journey['end']   = $data['site'][1]['destination'];
        } else {
            foreach ($data['site'] as $key => $val) {
                if ($val['id'] == 0) {
                    $journey['start'] = $val['destination'];
                } else if ($val['id'] == 11) {
                    $journey['end'] = $val['destination'];
                }
            }
        }
        return $journey ?? [];
    }

    /**
     * 验证用户是否为车主
     * @param string $type 0为前端调用，1为后端调用
     */
    public function checkUser(string $type = '0')
    {
        $isOwner = Db::name('user')->where('id', request()->uid)->value('is_owner');
        if ($type === '0') {
            $isOwner !== '2' ? show(403, "您不是车主！") : show(200, "您是车主！");
        } else {
            if ($isOwner !== '2') {
                show(403, "您不是车主！");
            }
        }
    }

    /**
     * 验证用户是否实名认证
     * @param string $type 0为前端调用，1为后端调用
     */
    public function checkUserAuthentication(string $type = '0')
    {
        $user = Db::name('user')->where('id', request()->uid)->field(['name', 'card'])->find();
        if ($type === '0') {
            empty($user['name']) || empty($user['card']) ? show(403, "抱歉！您还未实名！") : show(200, "您已通过实名认证！");
        } else {
            if (empty($user['name']) || empty($user['card'])) {
                show(403, "抱歉！您还未实名！");
            }
        }
    }

}