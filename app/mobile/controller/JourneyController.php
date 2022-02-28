<?php
/**
 * FileName: 旅途列表控制器
 * Description: 用于处理旅途列表相关业务
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-02-28 17:51
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\controller;


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
        // 更新已过期数据
        $this->editDeadlineData();
        // 接收数据
        $data = Request::only(['per_page', 'current_page', 'keywords']);
        $info = Db::view('journey', 'id,start,sum,end,trip,owner_id,user_id,line,deadline')
            ->view('user', 'photo,name,mobile,sex', 'journey.user_id=user.id')
            ->where(['journey.type' => '1', 'journey.status' => '0'])
            ->whereLike('journey.start|journey.end', "%" . $data['keywords'] . "%")
            ->order('journey.create_time', 'desc')
            ->paginate([
                'list_rows' => $data['per_page'],
                'query'     => request()->param(),
                'var_page'  => 'page',
                'page'      => $data['current_page']
            ])->each(function ($item) {
                $item['name']       = mb_substr($item['name'], 0, 1, 'utf-8');
                $item['date']       = $this->time2string($item['deadline'] - time());
                $item['deadline']   = $item['deadline'] - time();
                $item['indent_sum'] = Db::name('journey')
                    ->where(['owner_id' => $item['owner_id'], 'status' => '6'])
                    ->count();
                $item['number']     = $item['sum'] - $item['trip'];
                return $item;
            });
        show(200, "获取数据成功！", $info->toArray() ?? []);
    }
}