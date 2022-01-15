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


use thans\jwt\facade\JWTAuth;
use thans\jwt\JWT;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\facade\Request;

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
        $menu  = Db::name('menu')->where(['pid' => 0, 'status' => 1])->field('id,name')->order('sort', 'desc')->select()->toArray();
        $admin = Db::name('admin')->where('id', request()->uid)->field('role_id')->find();
        $group = Db::name('group')->where('id', $admin['role_id'])->field('rules')->find();
        //转数组
        $groupArray = explode(',', $group['rules']);
        if (request()->uid !== 1) {
            foreach ($menu as $key => $val) {
                if (in_array($val['id'], $groupArray)) {
                    $subMenu = Db::name('menu')->where(['pid' => $val['id'], 'status' => 1])->field('id,name')->order('sort', 'desc')->select();
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
                $subMenu                = Db::name('menu')->where(['pid' => $val['id'], 'status' => 1])->field('id,name')->order('sort', 'desc')->select();
                $menu[$key]['children'] = $subMenu;
            }
        }
        show(200, '获取菜单成功！', $menu ?? []);
    }

    /**
     * 我的桌面
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function welcome()
    {
        // 我的状态
        $admin          = Db::name('admin')->where('id', request()->uid)->field('user,role_id,lastlog_time,lastlog_ip,login_sum')->find();
        $group          = Db::name('group')->where('id', $admin['role_id'])->field('name')->find();
        $data['status'] = [
            ['key' => '当前登录者', 'value' => $admin['user']],
            ['key' => '所属权限组', 'value' => $group['name']],
            ['key' => '上次登录IP', 'value' => $admin['lastlog_ip']],
            ['key' => '上次登录时间', 'value' => $admin['lastlog_time']],
            ['key' => '登录总次数', 'value' => $admin['login_sum']]
        ];
        // 顶部四大数据栏
        $moneyTotal   = Db::name('user_buylog')->where('status', '1')->sum('money');
        $owner        = Db::name('user_owner')->count();
        $user         = Db::name('user')->count();
        $order        = Db::name('user_buylog')->count();
        $data['head'] = [
            'money_total' => $moneyTotal,
            'owner'       => $owner,
            'user'        => $user,
            'order'       => $order
        ];
        // 近7天报表
        $week = -6;
        while ($week <= 0) {
            // 每日收入统计
            $dayMoney = Db::name('user_buylog')->where('status', '1')->whereDay('create_time', date('Y-m-d', strtotime("{$week} day")))->sum('money');
            // 防止为NULL造成报错
            if (empty($dayMoney)) {
                $dayMoney = 0;
            }
            $moneyWeekData[] = $dayMoney;
            // 每日用户注册统计
            $dayNewUser = Db::name('user')->whereDay('create_time', date('Y-m-d', strtotime("{$week} day")))->count();
            if (empty($dayNewUser)) {
                $dayNewUser = 0;
            }
            $userWeekData[] = $dayNewUser;
            // 每日完成订单
            $dayOrder = Db::name('user_buylog')->where('status', '1')->whereDay('create_time', date('Y-m-d', strtotime("{$week} day")))->count();
            if (empty($dayOrder)) {
                $dayOrder = 0;
            }
            $orderWeekData[] = $dayOrder;
            // 每日注册车主数量
            $dayOwner = Db::name('user_owner')->whereDay('create_time', date('Y-m-d', strtotime("{$week} day")))->count();
            if (empty($dayOwner)) {
                $dayOwner = 0;
            }
            $ownerWeekData[] = $dayOwner;
            // 每日旅途数量
            $dayJourney = Db::name('journey')->whereDay('create_time', date('Y-m-d', strtotime("{$week} day")))->count();
            if (empty($dayJourney)) {
                $dayJourney = 0;
            }
            $journeyWeekData[] = $dayJourney;
            // 每日时间记录
            $weekTime[] = date('Y-m-d', strtotime("{$week} day"));
            $week       = $week + 1;
        }
        $data['option'] = [
            'legend' => [
                'data' => ['收入金额', '用户注册', '完成订单', '车主数量', '旅途数量']
            ],
            'xAxis'  => [
                ['data' => $weekTime ?? []]
            ],
            'series' => [
                ['name' => '收入金额', 'type' => 'line', 'stack' => '总量', 'data' => $moneyWeekData ?? []],
                ['name' => '用户注册', 'type' => 'line', 'stack' => '总量', 'data' => $userWeekData ?? []],
                ['name' => '完成订单', 'type' => 'line', 'stack' => '总量', 'data' => $orderWeekData ?? []],
                ['name' => '车主数量', 'type' => 'line', 'stack' => '总量', 'data' => $ownerWeekData ?? []],
                ['name' => '旅途数量', 'type' => 'line', 'stack' => '总量', 'data' => $journeyWeekData ?? []]
            ]
        ];
        // 消费日志
        $buyLog          = Db::name('user_buylog')->field('uid,indent,money')->order('create_time', 'desc')->limit(5)->select();
        $data['buy_log'] = $buyLog;
        // 待提现订单
        $toAudit          = Db::name('owner_withdraw')->where('status', '0')->field('owner_id,indent,money')->order('create_time', 'desc')->limit(5)->select();
        $data['to_audit'] = $toAudit;
        // 待审核车主
        $toOwner          = Db::view('user', 'id as uid,user')
            ->view('user_owner', 'id,create_time', 'user_owner.user_id=user.id')
            ->where('user.is_owner', '1')
            ->order('user_owner.create_time', 'desc')
            ->limit(5)
            ->select();
        $data['to_owner'] = $toOwner;
        // 新注册用户
        $newUser          = Db::name('user')->field('id,user,create_time')->order('create_time', 'desc')->limit(5)->select();
        $data['new_user'] = $newUser;
        show(200, "获取数据成功！", $data);
    }

    /**
     * 清除缓存
     */
    public function clear()
    {
        // 删除运行目录
        if (delete_dir_file(root_path() . 'runtime/admin') && delete_dir_file(root_path() . 'runtime/mobile') && delete_dir_file(root_path() . 'runtime/log')) {
            show(200, "清除成功！");
        } else {
            show(200, "清除成功！");
        }
    }

    /**
     * 退出登录
     * @throws \think\db\exception\DbException
     */
    public function loginOut()
    {
        // 刷新token
        JWTAuth::refresh();
        // 更新
        $res = Db::name('admin')->where('id', request()->uid)->update(['lastlog_time' => time(), 'lastlog_ip' => Request::ip()]);
        if ($res) {
            show(200, "退出成功！");
        } else {
            show(403, "退出失败！");
        }
    }
}