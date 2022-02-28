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
use app\mobile\model\JourneyModel;
use app\mobile\model\JourneyPassModel;
use app\mobile\model\UserBuyLogModel;
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
        $oneself           = Db::view('journey', 'user_id')
            ->view('journey_pass', 'status', 'journey_pass.journey_id=journey.id')
            ->where(['journey.user_id' => request()->uid, 'journey_pass.status' => '0'])
            ->find();
        $journeyUserColumn = Db::name('journey_user')
            ->where('user_id', request()->uid)
            ->column('journey_id');
        $group             = Db::name('journey_pass')
            ->whereIn('journey_id', $journeyUserColumn)
            ->where(['status' => '0', 'user_id' => request()->uid])
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
     * 验证用户是否实名认证（前端调用）
     */
    public function checkUserAuthentication()
    {
        $user = Db::name('user')->where('id', request()->uid)->field(['name', 'card'])->find();
        empty($user['name']) || empty($user['card']) ? show(403, "抱歉！您还未实名！") : show(200, "您已通过实名认证！");

    }


    /**
     * 更新列表中时间已截止的数据
     */
    public function editDeadlineData()
    {
        $journey = JourneyModel::where('status', '0')
            ->whereTime('deadline', '<=', time())
            ->column('id');
        // 更新已过期的数据的状态为出发失败
        $journeyResult = JourneyModel::whereIn('id', $journey)->where('status', '0')->update(['status' => '4']);
        if ($journeyResult) {
            JourneyPassModel::whereIn('journey_id', $journey)->where('status', '0')->update(['status' => '2']);
            UserBuyLogModel::whereIn('journey_id', $journey)->update(['status' => '3']);
        }
    }

    /**
     * 计算距离指定日期还有多少天/小时/分钟/秒数
     * @param int $second 时间戳
     * @return array
     */
    public function time2string(int $second): array
    {
        $day    = floor($second / (3600 * 24));
        $second = $second % (3600 * 24);//除去整天之后剩余的时间
        $hour   = floor($second / 3600);
        $second = $second % 3600;//除去整小时之后剩余的时间
        $minute = floor($second / 60);
        $second = $second % 60;//除去整分钟之后剩余的时间
        //返回字符串
        return ['days' => $day, 'hours' => $hour, 'minutes' => $minute, 'seconds' => $second];
    }

}