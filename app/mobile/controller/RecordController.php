<?php
/**
 * FileName: 旅行记录控制器
 * Description: 用于处理旅行记录相关逻辑
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-03-01 17:52
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\controller;


use think\facade\Db;
use think\facade\Request;

class RecordController extends CommonController
{
    /**
     * 旅行记录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function list()
    {
        // 接收数据
        $data = Request::only(['per_page', 'current_page']);
        $info = Db::name('journey')->where('user_id', request()->uid)
            ->field(['id', 'start', 'end', 'status'])
            ->order('create_time', 'desc')
            ->paginate([
                'list_rows' => $data['per_page'],
                'query'     => request()->param(),
                'var_page'  => 'page',
                'page'      => $data['current_page']
            ])->each(function ($item) {
                $item['pass'] = Db::name('journey_pass')
                    ->where(['journey_id' => $item['id'], 'user_id' => request()->uid])
                    ->field(['end', 'end_id', 'status', 'arrival_time'])
                    ->select()->each(function ($value) {
                        $value['arrival_time'] = $value['arrival_time'] ? '　' . date('Y-m-d', $value['arrival_time']) : '　未达到';
                        return $value;
                    });
                return $item;
            });
        show(200, "获取数据成功！", $info->toArray() ?? []);
    }
}