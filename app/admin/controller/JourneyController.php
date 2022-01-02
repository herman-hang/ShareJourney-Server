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
            ->whereLike('id|start|end|uid', "%" . $data['keywords'] . "%")
            ->order('create_time', 'desc')
            ->paginate([
                'list_rows' => $data['per_page'],
                'query' => request()->param(),
                'var_page' => 'page',
                'page' => $data['current_page']
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
        $id = Request::param('id');
        $info = Db::name('journey')->where('id', $id)->find();
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
        // 旅途ID
        $id = Request::param('id');
        $info = Db::name('journey_pass')->where('journey_id', $id)->order('scheduled_time', 'asc')->select();
        show(200, "获取数据成功！", $info->toArray() ?? []);
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
            Db::name('journey_pass')->whereIn('journey_id',$array)->delete();
            show(200, "删除成功！");
        } else {
            show(200, "删除失败！");
        }
    }
}