<?php
/**
 * FileName: 旅途控制器
 * Description: 处理旅途逻辑
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-01-02 16:29
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\controller;


use think\facade\Db;
use think\facade\Request;

class JourneyController extends CommonController
{
    /**
     * 旅途列表
     * @throws \think\db\exception\DbException
     */
    public function list()
    {
        // 接收数据
        $data = Request::only(['keywords', 'per_page', 'current_page']);
        // 查询所有旅途信息
        $info = Db::name('journey')
            ->whereLike('id|start|end|user_id', "%" . $data['keywords'] . "%")
            ->order('create_time', 'desc')
            ->paginate([
                'list_rows' => $data['per_page'],
                'query'     => request()->param(),
                'var_page'  => 'page',
                'page'      => $data['current_page']
            ]);
        show(200, "获取数据成功！", $info->toArray() ?? []);
    }

    /**
     * 根据旅途ID获取详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function query()
    {
        // 旅途ID
        $id              = Request::param('id');
        $info            = Db::name('journey')->where('id', $id)->find();
        $info['current'] = Db::name('journey_user')->where('journey_id', $id)->column('user_id');
        $info['current'] = implode(',', $info['current']);
        show(200, "获取数据成功！", $info);
    }

    /**
     * 根据旅途ID获取轨迹线
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function timeLine()
    {
        // 接收数据
        $data        = Request::only(['id']);
        $journey     = Db::name('journey')
            ->where('id', $data['id'])
            ->field(['start'])->find();
        $journeyPass = Db::name('journey_pass')
            ->where(['journey_id' => $data['id']])
            ->field(['end', 'end_id', 'status', 'arrival_time'])
            ->order('end_id', 'asc')
            ->select();
        $info        = [['id' => 0, 'content' => $journey['start'], 'type' => 'success', 'icon' => 'el-icon-check']];
        if (!empty($journeyPass)) {
            foreach ($journeyPass as $key => $val) {
                if ($val['status'] === '1') {
                    $info[$val['end_id']]['type']         = 'success';
                    $info[$val['end_id']]['icon']         = 'el-icon-check';
                    $info[$val['end_id']]['arrival_time'] = date('Y-m-d H:i:s', $val['arrival_time']);
                    $info[$val['end_id']]['status']       = $val['status'];
                }
                $info[$val['end_id']]['id']      = $val['end_id'];
                $info[$val['end_id']]['content'] = $val['end'];
                $info[$val['end_id']]['status']  = $val['status'];
            }
        }
        show(200, "获取数据成功！", ['data' => $info]);
    }

    /**
     * 删除旅途信息
     * @throws \think\db\exception\DbException
     */
    public function delete()
    {
        $id = Request::param('id');
        if (!strpos($id, ',')) {
            $array = array($id);
        } else {
            //转为数组
            $array = explode(',', $id);
            // 删除数组中空元素
            $array = array_filter($array);
        }
        // 删除操作
        $res = Db::name('journey')->delete($array);
        if ($res) {
            Db::name('journey_pass')->whereIn('journey_id', $array)->delete();
            show(200, "删除成功！");
        } else {
            show(200, "删除失败！");
        }
    }
}