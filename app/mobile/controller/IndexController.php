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
use app\mobile\model\UserModel;
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
        if ($data['type'] == '0') {// 旅客
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
            ->field(['line', 'type', 'status'])
            ->find();
        if ($journey['type'] === '0' && $journey['status'] === '1') {
            // 查询车主发起的旅途订单
            $journey = Db::view('journey_user', 'journey_id')
                ->view('journey', 'line', 'journey.id=journey_user.journey_id')
                ->where('journey_user.user_id', request()->uid)
                ->order('journey_user.id', 'desc')
                ->find();
        }
        // 字符串解压
        $journey['line'] = gzuncompress(base64_decode($journey['line']));
        show(200, "获取数据成功！", $journey ?? []);
    }

    /**
     * 获取首页旅途列表数据
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
                        ->order('journey_user.id', 'desc')
                        ->find();
                    // 查询目的地
                    if ($ownerJourney['status'] === '6') {
                        $condition = ['journey_id' => $ownerJourney['journey_id'], 'status' => '1'];
                    } else {
                        $condition = ['journey_id' => $ownerJourney['journey_id'], 'status' => '0'];
                    }
                    $userJourneyPass = Db::name('journey_pass')
                        ->where($condition)
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
                        ->distinct(true)
                        ->select()->toArray();
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
                    ->distinct(true)
                    ->select()->toArray();
                $data['id']           = $journey['id'];
                $data['type']         = $journey['type'];
                $data['start']        = $journey['start'];
                $data['end']          = $userJourneyPass['end'];
                $data['end_id']       = $userJourneyPass['end_id'];
                $data['owner_status'] = $journey['status'];
                $data['owner']        = [
                    'name'         => mb_substr($ownerUser['name'], 0, 1, 'utf-8'),
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
     * @return array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function formatUserName(array $user)
    {
        foreach ($user as $key => $value) {
            $journeyPass = Db::name('journey_pass')->where(['user_id' => $value['user_id'], 'status' => '0'])->find();
            if (!empty($journeyPass)) {
                $data[$key]['user_id'] = $value['user_id'];
                $data[$key]['name']    = mb_substr($value['name'], 0, 1, 'utf-8');
                $data[$key]['sex']     = $value['sex'];
            }
        }
        return $data ?? null;
    }

    /**
     * 确认订单/取消订单
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editOrder()
    {
        $data = Request::only(['id', 'status', 'owner']);
        // 验证数据
        $validate = new IndexValidate();
        if (!$validate->sceneEditOrder()->check($data)) {
            show(403, $validate->getError());
        }
        // 查询当前的旅途订单类型
        $journey              = Db::name('journey')->where('id', $data['id'])->field(['type', 'trip'])->find();
        $journeyModel         = JourneyModel::find($data['id']);
        $journeyModel->status = $data['status'];
        if ($journeyModel->save()) {
            if (!empty($data['owner'])) {
                // 正在进行的车主信息
                $userDown = Db::name('journey')
                    ->where([
                        'owner_id' => $data['owner']['owner_id'],
                        'user_id'  => $data['owner']['user_id'],
                        'status'   => ['0', '2', '3']
                    ])->field(['id', 'trip', 'status'])->find();
            }
            if ($data['status'] === '5') {// 取消订单
                JourneyPassModel::where(['user_id' => request()->uid, 'journey_id' => $data['id']])->update(['status' => '3']);
                Db::name('user_buylog')
                    ->where('journey_id', $data['id'])
                    ->update(['status' => '2']);
                if (!empty($data['owner']) && $journey['type'] === '0') {// 判断旅客是否已经上车
                    if ($userDown) {
                        if ($userDown['status'] === '2') {
                            $condition = ['status' => '3', 'trip' => $userDown['trip'] - $journey['trip']];
                        } else {
                            $condition = ['trip' => $userDown['trip'] - $journey['trip']];
                        }
                        // 旅客下车，可载人数自增，并且对已满座状态改为未满座
                        Db::name('journey')->where('id', $userDown['id'])->update($condition);
                    }
                }
            } else if ($data['status'] === '6') {// 确认订单
                $journeyUser = Db::name('journey_user')->where('journey_id', $data['id'])->find();
                if (empty($journeyUser) && $journey['type'] === '1') {// 车主订单没有旅客时不能确认订单，防止刷单行为
                    show(403, "该订单只允许取消！");
                } else if (!empty($data['owner']) && $journey['type'] === '0') {// 打款给车主
                    // 查询车主的平台服务费
                    $ownerInfo = Db::name('user_owner')
                        ->where('id', $data['owner']['owner_id'])
                        ->field(['service'])
                        ->find();
                    // 查询当前用户旅途订单的信息
                    $userBuyLog = Db::name('user_buylog')->where('journey_id', $data['id'])
                        ->field(['money', 'user_id', 'id'])
                        ->find();
                    if (request()->uid !== $userBuyLog['user_id']) {
                        show(403, "旅途信息不存在！");
                    }
                    // 计算金额，保留两位小数
                    $collectionMoney = number_format(($ownerInfo['service'] / 100) * $userBuyLog['money'], 2);
                    Db::name('user_buylog')
                        ->where('id', $userBuyLog['id'])
                        ->update([
                            'owner_id'         => $data['owner']['owner_id'],
                            'collection_money' => $collectionMoney
                        ]);
                    // 车主钱包金额增加
                    Db::name('user')->where('id', $data['owner']['user_id'])
                        ->inc('money', $collectionMoney)
                        ->update();
                    // 当前旅客总消费增加
                    Db::name('user')->where('id', request()->uid)
                        ->inc('expenditure', $userBuyLog['money'])
                        ->update();
                    // 旅客下车
                    if (!empty($userDown)) {
                        // 可载人数自增，并且对已满座状态改为未满座
                        if ($userDown) {
                            if ($userDown['status'] === '2') {
                                $condition = ['status' => '3', 'trip' => $userDown['trip'] - $journey['trip']];
                            } else {
                                $condition = ['trip' => $userDown['trip'] - $journey['trip']];
                            }
                            // 旅客下车，可载人数自增，并且对已满座状态改为未满座
                            Db::name('journey')->where('id', $userDown['id'])->update($condition);
                        }
                    }
                }
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
            show(200, "系好安全带，开始出发！");
        } else {
            show(403, "出发失败！");
        }
    }

    /**
     * 获取车主订单数据
     * @throws \think\db\exception\DbException
     */
    public function getOwnerIndent()
    {
        // 更新已过期数据
        $this->editDeadlineData();
        // 接收数据
        $data = Request::only(['per_page', 'current_page']);
        $info = Db::view('journey', 'id,start,sum,end,trip,owner_id,user_id')
            ->view('user', 'photo,name,mobile,sex', 'journey.user_id=user.id')
            ->where(['journey.type' => '1', 'journey.status' => '3'])
            ->order('journey.create_time', 'desc')
            ->paginate([
                'list_rows' => $data['per_page'],
                'query'     => request()->param(),
                'var_page'  => 'page',
                'page'      => $data['current_page']
            ])->each(function ($item) {
                $item['name']       = mb_substr($item['name'], 0, 1, 'utf-8');
                $item['indent_sum'] = Db::name('journey')
                    ->where(['owner_id' => $item['owner_id'], 'status' => '6'])
                    ->count();
                $item['number']     = $item['sum'] - $item['trip'];
                return $item;
            });
        show(200, "获取数据成功！", $info->toArray() ?? []);
    }

    /**
     * 获取旅客订单数据
     * @throws \think\db\exception\DbException
     */
    public function getUserIndent()
    {
        // 更新已过期数据
        $this->editDeadlineData();
        // 接收数据
        $data = Request::only(['per_page', 'current_page']);
        $info = Db::view('journey', 'id,start,end,trip,deadline,user_id')
            ->view('user', 'photo,name,mobile,sex', 'journey.user_id=user.id')
            ->where(['journey.type' => '0', 'journey.status' => '0'])
            ->paginate([
                'list_rows' => $data['per_page'],
                'query'     => request()->param(),
                'var_page'  => 'page',
                'page'      => $data['current_page']
            ])->each(function ($item) {
                $item['name']     = mb_substr($item['name'], 0, 1, 'utf-8');
                $item['date']     = $this->time2string($item['deadline'] - time());
                $item['deadline'] = $item['deadline'] - time();
                return $item;
            });
        show(200, "获取数据成功！", $info->toArray() ?? []);
    }

    /**
     * 获取轨迹线数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPathLineData()
    {
        // 接收数据
        $data        = Request::only(['id']);
        $journey     = Db::name('journey')->where('id', $data['id'])
            ->field(['start'])->find();
        $journeyPass = Db::name('journey_pass')->where(['journey_id' => $data['id'], 'status' => ['0', '1']])
            ->field(['end', 'end_id', 'status', 'arrival_time'])->order('end_id', 'asc')->select();
        $info        = [['id' => 0, 'title' => $journey['start'], 'status' => null]];
        if (!empty($journeyPass)) {
            foreach ($journeyPass as $key => $val) {
                if ($val['status'] === '0') {
                    $sort[] = $key + 1;
                }
                $info[$val['end_id']]['id']           = $val['end_id'];
                $info[$val['end_id']]['title']        = $val['end'];
                $info[$val['end_id']]['status']       = $val['status'];
                $info[$val['end_id']]['arrival_time'] = date("Y-m-d H:i:s", $val['arrival_time']);
            }
        }
        show(200, "获取数据成功！", ['data' => $info, 'sort' => $sort[0] ?? '1']);
    }

    /**
     * 根据旅途ID获取轨迹线
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function queryLine()
    {
        // 接收旅途ID
        $id = Request::param('id');
        // 查询轨迹线
        $info = Db::name('journey')->where('id', $id)->field('line')->find();
        $info['line'] = gzuncompress(base64_decode($info['line']));
        show(200, "获取数据成功！", $info ?? []);
    }

}