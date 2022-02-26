<?php
/**
 * FileName: 首页控制器
 * Description: 用于移动端首页业务逻辑处理
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-02-18 21:22
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\controller;


use app\mobile\model\JourneyModel;
use app\mobile\model\JourneyPassModel;
use app\mobile\validate\IndexValidate;
use think\App;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;
use Yansongda\Pay\Pay;
use function DI\string;

class IndexController extends CommonController
{
    /**
     * 整理路线出现数据
     */
    public function tripData()
    {
        $data = Request::only(['money', 'km', 'time', 'number', 'type']);
        // 保留两位小数；单位为公里
        $data['km'] = number_format($data['km'] / 1000, 2);
        // 单位为小时
        $data['time'] = number_format($data['time'] / 60, 1) == '0.00' ? '0.1' : number_format($data['time'] / 60, 1);
        if ($data['type'] == '0') {
            // 金额计算
            $data['money'] = $data['number'] > 1 ? $data['money'] = number_format($data['money'] + ($data['money'] * 0.2) * $data['number'], 2) : $data['money'] = number_format($data['money'], 2);
        }
        // 缓存数据
        Cache::set('trip_data_' . request()->uid, $data, 600);
        show(200, "获取数据成功！", $data);
    }

    /**
     * 获取当前用户轨迹线
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLine()
    {
        $journey = Db::name('journey')
            ->where(['user_id' => request()->uid, 'status' => ['0', '1', '2', '3']])
            ->field(['line'])
            ->find();
        show(200, "获取数据成功！", $journey ?? []);
    }

    /**
     * 获取旅途数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getJourney()
    {
        // 查询旅途信息
        $journey = Db::name('journey')
            ->where(['user_id' => request()->uid, 'status' => ['0', '1', '2', '3']])
            ->field(['id', 'user_id', 'owner_id', 'type', 'status', 'start'])
            ->find();
        // 查询当前用户的信息
        $user = Db::name('user')->where('id', request()->uid)->field(['id', 'name', 'sex'])->find();
        // 查询目的地
        $userJourneyPass = Db::name('journey_pass')->where(['journey_id' => $journey['id'], 'status' => '0'])
            ->field(['end', 'end_id'])
            ->order('end_id', 'asc')
            ->find();
        if (empty($journey)) {
            show(403, "当前旅途信息不存在！");
        } else {
            if ($journey['type'] === '0') {// 旅客类型订单
                $data['id']   = $journey['id'];
                $data['type'] = $journey['type'];
                if ($journey['status'] === '0') {// 拼车中
                    $data['start']       = $journey['start'];
                    $data['end']         = $userJourneyPass['end'];
                    $data['end_id']      = $userJourneyPass['end_id'];
                    $data['user_status'] = $journey['status'];
                    $data['owner']       = null;
                    $data['user']        = [['user_id' => $user['id'], 'name' => mb_substr($user['name'], 0, 1, 'utf-8'), 'sex' => $user['sex']]];
                } else if ($journey['status'] === '1') {// 旅途中(旅客已上车)
                    // 查询车主发起的旅途订单
                    $ownerJourney = Db::view('journey_user', 'journey_id')
                        ->view('journey', 'start,type,sum,status,user_id,trip,owner_id', 'journey.id=journey_user.journey_id')
                        ->where('journey_user.user_id', request()->uid)
                        ->find();
                    // 查询目的地
                    $userJourneyPass = Db::name('journey_pass')
                        ->where(['journey_id' => $ownerJourney['journey_id'], 'status' => '0'])
                        ->field(['end', 'end_id'])
                        ->order('end_id', 'asc')
                        ->find();
                    // 查询车主的信息
                    $ownerUser = Db::view('user', 'name,photo,sex')
                        ->view('user_owner', 'plate_number,user_id', 'user_owner.user_id=user.id')
                        ->where('user_owner.user_id', $ownerJourney['user_id'])
                        ->find();
                    // 查询当前司机接单总数
                    $indentSum = Db::name('journey')
                        ->where(['owner_id' => $ownerJourney['owner_id'], 'status' => '6'])
                        ->count();
                    // 查询当前旅途订单的所有旅客
                    $allUser              = Db::view('journey_user', 'user_id')
                        ->view('user', 'name,sex', 'user.id=journey_user.user_id')
                        ->where('journey_user.journey_id', $ownerJourney['journey_id'])
                        ->select();
                    $data['start']        = $journey['start'];
                    $data['end']          = $userJourneyPass['end'];
                    $data['end_id']       = $userJourneyPass['end_id'];
                    $data['owner_status'] = $ownerJourney['status'];
                    $data['owner']        = [
                        'name'         => mb_substr($ownerUser['name'], 0, 1, 'utf-8'),
                        'photo'        => $ownerUser['photo'],
                        'sex'          => $ownerUser['sex'],
                        'user_id'      => $ownerUser['user_id'],
                        'owner_id'     => $ownerJourney['owner_id'],
                        'plate_number' => $ownerUser['plate_number'],
                        'indent_sum'   => $indentSum
                    ];
                    $data['user']         = $this->formatUserName($allUser);
                }
            } else {// 车主类型订单
                // 查询车主的信息
                $ownerUser = Db::view('user', 'name,photo,sex')
                    ->view('user_owner', 'plate_number,user_id,id as owner_id', 'user_owner.user_id=user.id')
                    ->where('user_owner.user_id', request()->uid)
                    ->find();
                // 查询当前司机接单总数
                $indentSum = Db::name('journey')
                    ->where(['owner_id' => $ownerUser['owner_id'], 'status' => '6'])
                    ->count();
                // 查询当前旅途订单的所有旅客
                $allUser              = Db::view('journey_user', 'user_id')
                    ->view('user', 'name,sex', 'user.id=journey_user.user_id')
                    ->where('journey_user.journey_id', $journey['id'])
                    ->select()->toArray();
                $data['id']           = $journey['id'];
                $data['type']         = $journey['type'];
                $data['start']        = $journey['start'];
                $data['end']          = $userJourneyPass['end'];
                $data['end_id']       = $userJourneyPass['end_id'];
                $data['owner_status'] = $journey['status'];
                $data['owner']        = [
                    'name'         => mb_substr($ownerUser['name'], 0, 1, 'utf-8') . '司机',
                    'photo'        => $ownerUser['photo'],
                    'user_id'      => request()->uid,
                    'owner_id'     => $ownerUser['owner_id'],
                    'plate_number' => $ownerUser['plate_number'],
                    'indent_sum'   => $indentSum
                ];
                if (!empty($allUser)) {
                    $data['user'] = $this->formatUserName($allUser);
                } else {
                    $data['user'] = null;
                }
            }
            show(200, "获取数据成功！", $data);
        }

    }

    /**
     * 格式化用户的名称
     * @param array $user 需要格式化的用户
     * @return array
     */
    private function formatUserName(array $user): array
    {
        foreach ($user as $key => $value) {
            $data['user'][$key]['user_id'] = $value['user_id'];
            $data['user'][$key]['name']    = mb_substr($value['name'], 0, 1, 'utf-8');
            $data['user'][$key]['sex']     = $value['sex'];
        }
        return $data ?? [];
    }

    /**
     * 确认订单/取消订单
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editOrder()
    {
        $data = Request::only(['id', 'status']);
        // 验证数据
        $validate = new IndexValidate();
        if (!$validate->sceneEditOrder()->check($data)) {
            show(403, $validate->getError());
        }
        $journeyModel         = JourneyModel::find($data['id']);
        $journeyModel->status = $data['status'];
        if ($journeyModel->save()) {
            if ($data['status'] === '5') {// 取消订单
                JourneyPassModel::where(['user_id' => request()->uid, 'journey_id' => $data['id']])->update(['status' => '3']);
            } else if ($data['status'] === '6') {// 确认订单
                JourneyPassModel::where(['user_id' => request()->uid, 'journey_id' => $data['id']])->update(['status' => '1', 'arrival_time' => time()]);
            }
            show(200, "操作成功！");
        } else {
            show(403, "操作失败！");
        }
    }

    /**
     * 开始出发
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setOut()
    {
        // 接收数据
        $data = Request::only(['owner_id']);
        // 查询旅途信息
        $journey  = Db::name('journey')
            ->where(['user_id' => request()->uid, 'status' => '0', 'owner_id' => $data['owner_id']])
            ->field(['id', 'type', 'status', 'trip'])
            ->find();
        $ownerSum = Db::name('user_owner')->where('id', $data['owner_id'])->field(['capacity'])->find();
        if ($journey['trip'] < $ownerSum['capacity']) {
            $status = '3';
        } else if ($journey['trip'] == $ownerSum) {
            $status = '2';
        }
        $res = Db::name('journey')->where('id', $journey['id'])->update(['status' => $status]);
        if ($res) {
            show(200, "请开始出发！");
        } else {
            show(403, "出发失败！");
        }
    }

}