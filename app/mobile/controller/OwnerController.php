<?php
/**
 * FileName: 车主控制器
 * Description: 处理车主相关业务逻辑
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-02-25 19:11
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\controller;


use app\mobile\model\JourneyModel;
use app\mobile\model\JourneyPassModel;
use app\mobile\validate\PayValidate;
use think\App;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Request;

class OwnerController extends CommonController
{
    /**
     * 车主发起旅途
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function start()
    {
        // 检测当前提交的用户是否为车主
        $this->checkUser('1');
        // 检测是否有进行的订单有直接返回
        $this->checkIndentStatus();
        // 接收参数
        $data = Request::only(['site', 'type', 'trip', 'line']);
        // 验证数据
        $validate = new PayValidate();
        if (!$validate->sceneOwnerStart()->check($data)) {
            show(403, $validate->getError());
        }
        // 查询车主信息
        $ownerInfo = Db::name('user_owner')->where('user_id', request()->uid)->field(['id', 'capacity', 'status'])->find();
        if ($ownerInfo['status'] === '0') {
            show(403, "您的车主身份已被封禁！");
        }
        if ($data['trip']['number'] > $ownerInfo['capacity']) {
            show(403, "同行人数已大于车辆可载人数！");
        }
        $site     = $this->getStartEnd($data);
        $deadline = strtotime($data['trip']['deadline']);
        // 获取缓存数据
        $cacheData                 = Cache::get('trip_data_' . request()->uid);
        $journey['start']          = $site['start'];
        $journey['end']            = $site['end'];
        $journey['type']           = $data['type'];
        $journey['sum']            = $ownerInfo['capacity'];
        $journey['user_id']        = request()->uid;
        $journey['trip']           = $data['trip']['number'];
        $journey['scheduled_time'] = $cacheData['time'];
        $journey['deadline']       = $deadline;
        $journey['owner_id']       = $ownerInfo['id'];
        $journey['line']           = $data['line'];
        // 插入旅途表
        $journeyResult = JourneyModel::create($journey);
        if ($journeyResult) {
            if (count($data['site']) == 2) {
                $journeyPass['journey_id'] = $journeyResult->id;
                $journeyPass['end']        = $data['site'][1]['destination'];
                $journeyPass['end_id']     = $data['site'][1]['id'];
                $journeyPass['status']     = '0';
                $journeyPass['user_id']    = request()->uid;
                $journeyPassResult         = JourneyPassModel::create($journeyPass ?? []);
            } else {
                foreach ($data['site'] as $key => $value) {
                    if ($value['id'] !== 0) {
                        $journeyPass[$key]['journey_id'] = $journeyResult->id;
                        $journeyPass[$key]['end']        = $value['destination'];
                        $journeyPass[$key]['end_id']     = $value['id'];
                        $journeyPass[$key]['status']     = '0';
                        $journeyPass[$key]['user_id']    = request()->uid;
                    }
                }
                // 插入旅途附属表
                $journeyPassResult = (new JourneyPassModel)->saveAll($journeyPass ?? []);
            }
            if ($journeyPassResult) {
                Cache::delete('trip_data_' . request()->uid);
                $info['journey_id'] = $journeyResult->id;
                // 延迟秒数
                $delay = $deadline - time();
                // 推送到消息队列
                Queue::later($delay, 'app\mobile\job\RefreshJourneyJob', [], 'refresh_journey_info');
                show(200, "开始出发！", $info);
            } else {
                show(403, "出发失败！");
            }
        }
    }

    /**
     * 邀请旅客上车
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function invitationUser()
    {
        // 接收当前用户的旅途ID和用户ID
        $data = Request::only(['id', 'user_id', 'trip']);
        if ($data['user_id'] === request()->uid) {
            show(403, "无法邀请自己！");
        }
        // 查询旅途信息
        $journey = Db::name('journey')
            ->where(['user_id' => request()->uid, 'status' => ['0', '1', '2', '3']])
            ->field(['id', 'trip', 'sum'])
            ->find();
        if (empty($journey)) {
            show(403, "请先发布旅途信息！");
        }
        if ($journey['sum'] - $journey['trip'] < $data['trip']) {
            show(403, "旅客过多，车辆已超载！");
        }
        // 将当前用户关联车主旅途订单
        $journeyUser = Db::name('journey_user')->insert(['journey_id' => $journey['id'], 'user_id' => $data['user_id']]);
        if ($journeyUser) {
            // 判断车辆是否载满人
            if ($journey['sum'] == $journey['trip'] + $data['trip']) {
                // 更新位已满人
                (new JourneyModel())->saveAll([
                    ['id' => $data['id'], 'status' => '1'],
                    ['id' => $journey['id'], 'trip' => $journey['trip'] + $data['trip'], 'status' => '2']
                ]);
            } else {
                // 正常增加人数
                (new JourneyModel())->saveAll([
                    ['id' => $data['id'], 'status' => '1'],
                    ['id' => $journey['id'], 'trip' => $journey['trip'] + $data['trip']]
                ]);
            }
            show(200, "邀请成功！");
        } else {
            show(403, "邀请失败！");
        }
    }

    /**
     * 呼叫车主金额数据计算
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function indentCompute()
    {
        // 接收数据
        $data = Request::only(['km', 'owner_id', 'time', 'number']);
        // 查询车主信息
        $ownerInfo = Db::name('user_owner')
            ->where('id', $data['owner_id'])
            ->field(['km'])
            ->find();
        // 金额计算
        $data['money'] = $data['number'] > 1 ? number_format($data['km'] * $ownerInfo['km'] + (($data['km'] * $ownerInfo['km']) * 0.2) * $data['number']) : number_format($data['km'] * $ownerInfo['km'], 2);
        // 缓存数据
        Cache::set('call_trip_data_' . request()->uid, $data, 600);
        show(200, "获取数据成功！", $data);
    }
}