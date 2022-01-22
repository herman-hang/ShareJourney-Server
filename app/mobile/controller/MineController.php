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


use app\mobile\model\OwnerWithdrawModel;
use app\mobile\validate\MyMoneyValidate;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Request;

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

    /**
     * 获取提现页面数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function withdraw()
    {
        $info          = Db::name('user')->where('id', request()->uid)->field(['money', 'is_owner'])->find();
        $data['money'] = $info['money'];
        if ($info['is_owner'] !== '2') {// 不是车主
            show(403, "您不是车主，无法提现！");
        } else {
            $owner           = Db::name('user_owner')->where('user_id', request()->uid)->field(['bank_card_type', 'bank_card'])->find();
            $number          = substr($owner['bank_card'], -4);
            $banType         = $owner['bank_card_type'];
            $data['actions'] = [
                ['name' => '微信', 'withdraw_account' => '0'],
                ['name' => '支付宝', 'withdraw_account' => '1'],
                ['name' => "{$banType}（{$number}）", 'withdraw_account' => '2']
            ];
            show(200, "获取数据成功！", $data);
        }
    }

    /**
     * 我的钱包发送提现验证码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function withdrawSendCode()
    {
        // 接收数据
        $data = Request::only(['withdraw_money', 'withdraw_account', 'withdraw_way']);
        // 查询用户信息
        $info = Db::name('user')->where('id', request()->uid)->field(['mobile', 'is_owner', 'money'])->find();
        // 验证数据
        $validate = new MyMoneyValidate();
        if (!$validate->sceneMyMoneySendCode()->check($data)) {
            show(403, $validate->getError());
        }
        if ($info['is_owner'] !== '2') {
            show(403, "您不是车主，无法提现");
        }
        if (bccomp($data['withdraw_money'], $info['money'], 2) == 1) {
            show(403, "钱包余额不足！");
        }
        // 进行短信发送
        $system = Db::name('system')->where('id', '1')->field('name')->find();
        $sms    = Db::name('sms')->where('id', 1)->field('withdraw_code_id,sms_type')->find();
        // 保留2位小数
        $data['withdraw_money'] = number_format($data['withdraw_money'], 2);
        $data['code']           = code_str(2);
        if ($sms['sms_type'] == '0') { // ThinkAPI
            $smsData['temp_id'] = $sms['withdraw_code_id'];
            $smsData['type']    = 0;
            $smsData['params']  = ['code' => $data['code'], 'money' => $data['withdraw_money'], 'way' => $data['withdraw_way']];
        } else { // 短信宝
            $smsData['content'] = "【{$system['name']}】您正在申请提现{$data['withdraw_money']}元到{$data['withdraw_way']}账户，验证码为{$data['code']}，有效期为5分钟。";
            $smsData['type']    = 1;
        }
        $smsData['mobile'] = $info['mobile'];
        if (!empty($smsData)) {
            Queue::push('app\job\SendSmsJob', $smsData, 'mobile');
        }
        // 对手机号码和验证码进行缓存，方便验证和注册
        Cache::set('send_withdraw_code_' . request()->uid, $data, 600);
        show(200, "发送成功！");
    }

    /**
     * 提交提现订单
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function withdrawAudit()
    {
        // 接收验证码
        $data = Request::only(['code']);
        $info = Cache::get('send_withdraw_code_' . request()->uid);
        if ($data['code'] !== $info['code']) {
            show(403, "验证码错误！");
        }
        // 查询用户信息
        $user = Db::name('user')->where('id', request()->uid)->field(['is_owner'])->find();
        // 查询车主信息
        $owner = Db::name('user_owner')->where('user_id', request()->uid)->find();
        if ($user['is_owner'] !== '2') {
            show(403, "您不是车主，无法提现");
        }
        $res = OwnerWithdrawModel::create([
            'money'            => $info['withdraw_money'],
            'indent'           => trade_no(),
            'withdraw_account' => $info['withdraw_account'],
            'user_id'          => request()->uid,
            'owner_id'         => $owner['id']
        ]);
        if ($res) {
            // 删除缓冲
            Cache::delete('send_withdraw_code_' . request()->uid);
            show(200, "提交成功，待审核中");
        } else {
            show(403, "提现失败！");
        }
    }
}