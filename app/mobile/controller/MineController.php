<?php
/**
 * FileName: 我的控制器
 * Description: 处理我的页面的业务逻辑
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-01-19 23:02
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\controller;


use think\facade\Db;

class MineController extends CommonController
{
    /**
     * 我的页面
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $info = Db::name('user')->where('id', request()->uid)->field(['nickname', 'photo', 'is_owner', 'mobile', 'sex', 'money'])->find();
        // 中间四位变*号
        $info['mobile'] = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $info['mobile']);
        if ($info['is_owner'] == 2) {// 是车主
            $ownerId = Db::name('user_owner')->where('user_id', request()->uid)->value('id');
            // 提现金额
            $info['withdraw_money'] = Db::name('owner_withdraw')->where(['status' => 1, 'owner_id' => $ownerId])->sum('money');
            // 保留2位小数
            $info['withdraw_money'] = number_format($info['withdraw_money'], 2);
        }
        show(200, "获取数据成功！", $info);
    }

    /**
     * 我的钱包
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function myMoney()
    {
        $info = Db::name('user')->where('id', request()->uid)->field(['money', 'expenditure', 'is_owner'])->find();
        if ($info['is_owner'] == 2) {// 是车主
            $ownerId = Db::name('user_owner')->where('user_id', request()->uid)->value('id');
            // 提现金额
            $info['withdraw_money'] = Db::name('owner_withdraw')->where(['status' => 1, 'owner_id' => $ownerId])->sum('money');
            // 保留2位小数
            $info['withdraw_money'] = number_format($info['withdraw_money'], 2);
        } else {
            $info['withdraw_money'] = "0.00";
        }
        show(200, "获取数据成功！", $info);
    }
}