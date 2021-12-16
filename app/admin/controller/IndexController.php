<?php
/**
 * FileName: 后台首页控制器
 * Description: 用于处理后台首页逻辑业务
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-11-27 11:28
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\controller;


use think\exception\HttpResponseException;
use think\facade\Db;

class IndexController extends CommonController
{
    /**
     * 后台首页
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function home()
    {
        //菜单查询
        $menu = Db::name('menu')->where(['pid' => 0, 'status' => 1])->field('id,name,url')->order('sort', 'desc')->select()->toArray();
        $admin = Db::name('admin')->where('id', request()->uid)->field('role_id')->find();
        $group = Db::name('group')->where('id', $admin['role_id'])->field('rules')->find();
        //转数组
        $groupArray = explode(',', $group['rules']);
        if (request()->uid !== 1) {
            foreach ($menu as $key => $val) {
                if (in_array($val['id'], $groupArray)) {
                    $subMenu = Db::name('menu')->where(['pid' => $val['id'], 'status' => 1])->field('id,name,url')->order('sort', 'desc')->select();
                    foreach ($subMenu as $k => $va) {
                        if (in_array($va['id'], $groupArray)) {
                            $menu[$key]['children'][$k] = $va;
                        }
                    }
                } else {
                    //遍历删除无权限的规则，即不渲染
                    unset($menu[$key]);
                }
            }
        } else {//超级管理员
            foreach ($menu as $key => $val) {
                $subMenu = Db::name('menu')->where(['pid' => $val['id'], 'status' => 1])->field('name,url')->order('sort', 'desc')->select();
                $menu[$key]['children'] = $subMenu;
            }
        }
        show(200, '获取菜单成功！', $menu ?? []);
    }
}