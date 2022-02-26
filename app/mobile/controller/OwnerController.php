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
use think\facade\Cache;
use think\facade\Db;
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
        $site = $this->getStartEnd($data);
        // 获取缓存数据
        $cacheData                 = Cache::get('trip_data_' . request()->uid);
        $journey['start']          = $site['start'];
        $journey['end']            = $site['end'];
        $journey['type']           = $data['type'];
        $journey['sum']            = $ownerInfo['capacity'];
        $journey['user_id']        = request()->uid;
        $journey['trip']           = $data['trip']['number'];
        $journey['scheduled_time'] = $cacheData['time'];
        $journey['deadline']       = strtotime($data['trip']['deadline']);
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
                show(200, "开始出发！", $info);
            } else {
                show(403, "出发失败！");
            }
        }
    }
}