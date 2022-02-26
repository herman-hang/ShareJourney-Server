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
use app\mobile\model\UserModel;
use app\mobile\model\UserOwnerModel;
use app\mobile\validate\AuthenticationValidate;
use app\mobile\validate\CarInfoValidate;
use app\mobile\validate\MaterialValidate;
use app\mobile\validate\MyMoneyValidate;
use app\mobile\validate\PatenteRegistrationValidate;
use app\mobile\validate\WithdrawInfoValidate;
use think\api\Client;
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
        if (!empty($info)) {
            // 中间四位变*号
            $info['mobile'] = $this->strReplace($info['mobile']);
            if ($info['is_owner'] == 2) {// 是车主
                $ownerId = Db::name('user_owner')->where('user_id', request()->uid)->value('id');
                // 提现金额
                $info['withdraw_money'] = Db::name('owner_withdraw')->where(['status' => 1, 'owner_id' => $ownerId])->sum('money');
                // 保留2位小数
                $info['withdraw_money'] = number_format($info['withdraw_money'], 2);
            }
        }
        show(200, "获取数据成功！", $info ?? []);
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
            $number          = substr($owner['bank_card'], -4); // 截取最后四位
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

    /**
     * 获取个人资料
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMaterial()
    {
        $info = Db::name('user')->where('id', request()->uid)->field([
            'nickname',
            'sex',
            'user',
            'age',
            'region',
            'qq',
            'introduction',
            'create_time',
            'photo'
        ])->find();
        show(200, "获取数据成功！", $info);
    }

    /**
     * 个人资料保存
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function materialSave()
    {
        // 接收数据
        $data = Request::only(['nickname', 'sex', 'age', 'region', 'qq', 'introduction', 'photo']);
        // 验证数据
        $validate = new MaterialValidate();
        if (!$validate->sceneMaterial()->check($data)) {
            show(403, $validate->getError());
        }
        $user = UserModel::find(request()->uid);
        $res  = $user->save($data);
        if ($res) {
            show(200, "保存成功！");
        } else {
            show(403, "保存失败！");
        }
    }

    /**
     * 获取当前用户认证信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCertificationInfo()
    {
        $info = Db::name('user')->where('id', request()->uid)->field(['is_owner', 'name', 'card', 'cause'])->find();
        if (!empty($info['card'])) {
            // 银行卡号中间位数变*
            $info['card'] = $this->strReplace($info['card']);
        }
        if ($info['is_owner'] == '0') {
            $owner                    = Db::name('user_owner')->where('user_id', request()->uid)->find();
            $info['patente_url']      = $owner['patente_url'] ? '1' : '0';
            $info['registration_url'] = $owner['registration_url'] ? '1' : '0';
            $info['car_url']          = $owner['car_url']
            && $owner['plate_number']
            && $owner['capacity']
            && $owner['color'] ? '1' : '0';
            $info['withdraw_info']    = $owner['alipay']
            && $owner['alipay_name']
            && $owner['wxpay']
            && $owner['wxpay_name']
            && $owner['bank_card']
            && $owner['bank_card_name']
            && $owner['bank_card_type'] ? '1' : '0';
        }
        show(200, "获取数据成功！", $info);
    }

    /**
     * 实名认证下一步逻辑处理
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function authenticationNext()
    {
        // 接收数据
        $data = Request::only(['card_front', 'card_verso']);
        // 查询是否已经上传身份证照片
        $info = Db::name('user')->where('id', request()->uid)->field(['card_front', 'card_verso', 'is_owner'])->find();
        if (!empty($info['card_front']) && !empty($info['card_verso'])) {
            if ($info['is_owner'] !== '1' && $info['is_owner'] !== '2') {
                show(403, "请勿重复实名认证！");
            }
        }
        // 验证数据
        $validate = new AuthenticationValidate();
        if (!$validate->sceneAuthNext()->check($data)) {
            show(403, $validate->getError());
        }
        // 更新数据
        $user = UserModel::find(request()->uid);
        $res  = $user->save($data);
        if ($res) {
            show(200, "上传成功！");
        } else {
            show(403, "上传失败！");
        }
    }

    /**
     * 实名认证提交逻辑处理
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function authenticationSubmit()
    {
        // 接收数据
        $data = Request::only(['card', 'name']);
        // 查询是否已经上传身份证照片
        $info = Db::name('user')->where('id', request()->uid)->field(['card_front', 'card_verso', 'card', 'name', 'is_owner'])->find();
        if (empty($info['card_front']) || empty($info['card_verso'])) {
            show(403, "请先上传身份证正反面照片！");
        }
        if (!empty($info['card']) && !empty($info['name'])) {
            if ($info['is_owner'] !== '1' && $info['is_owner'] !== '2') {
                show(403, "请勿重复实名认证！");
            }
        }
        // 验证数据
        $validate = new AuthenticationValidate();
        if (!$validate->sceneAuthSubmit()->check($data)) {
            show(403, $validate->getError());
        }
        // 更新数据
        $user = UserModel::find(request()->uid);
        $res  = $user->save($data);
        if ($res) {
            show(200, "实名成功！");
        } else {
            show(403, "实名失败！");
        }
    }

    /**
     * 提交提现信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function submitWithdrawInfo()
    {
        // 接收数据
        $data = Request::only(['alipay', 'alipay_name', 'wxpay', 'wxpay_name', 'bank_card', 'bank_card_name', 'bank_card_type']);
        // 验证数据
        $validate = new WithdrawInfoValidate();
        if (!$validate->sceneWithdrawInfo()->check($data)) {
            show(403, $validate->getError());
        }
        $info  = Db::name('user')->where('id', request()->uid)->field(['is_owner'])->find();
        $owner = Db::name('user_owner')->where('user_id', request()->uid)->field([
            'alipay',
            'alipay_name',
            'wxpay',
            'wxpay_name',
            'bank_card',
            'bank_card_name',
            'bank_card_type'
        ])->find();
        if (!empty($owner)
            && !empty($owner['alipay'])
            && !empty($owner['alipay_name'])
            && !empty($owner['wxpay'])
            && !empty($owner['wxpay_name'])
            && !empty($owner['bank_card'])
            && !empty($owner['bank_card_name'])
            && !empty($owner['bank_card_type'])) {
            if ($info['is_owner'] !== '1' && $info['is_owner'] !== '2') {
                show(403, "请勿重复填写！");
            }
        }
        $this->authBase($owner, $data);
    }

    /**
     * 车辆信息提交
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function submitCarInfo()
    {
        // 接收数据
        $data = Request::only(['plate_number', 'capacity', 'color', 'car_url']);
        // 验证数据
        $validate = new CarInfoValidate();
        if (!$validate->sceneCarInfo()->check($data)) {
            show(403, $validate->getError());
        }
        $info  = Db::name('user')->where('id', request()->uid)->field(['is_owner'])->find();
        $owner = Db::name('user_owner')->where('user_id', request()->uid)->field([
            'plate_number',
            'capacity',
            'color',
            'car_url'
        ])->find();
        if (!empty($owner)
            && !empty($owner['plate_number'])
            && !empty($owner['capacity'])
            && !empty($owner['color'])
            && !empty($owner['car_url'])) {
            if ($info['is_owner'] !== '1' && $info['is_owner'] !== '2') {
                show(403, "请勿重复提交！");
            }
        }
        $this->authBase($owner, $data);
    }

    /**
     * 提交驾驶证
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function submitPatente()
    {
        // 接收数据
        $data = Request::only(['patente_url']);
        // 验证数据
        $validate = new PatenteRegistrationValidate();
        if (!$validate->scenePatente()->check($data)) {
            show(403, $validate->getError());
        }
        $info  = Db::name('user')->where('id', request()->uid)->field(['is_owner'])->find();
        $owner = Db::name('user_owner')->where('user_id', request()->uid)->field(['patente_url'])->find();
        if (!empty($owner) && !empty($owner['patente_url'])) {
            if ($info['is_owner'] !== '1' && $info['is_owner'] !== '2') {
                show(403, "请勿重复提交！");
            }
        }
        $this->authBase($owner, $data);
    }

    /**
     * 提交行驶证
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function submitRegistration()
    {
        // 接收数据
        $data = Request::only(['registration_url']);
        // 验证数据
        $validate = new PatenteRegistrationValidate();
        if (!$validate->sceneRegistration()->check($data)) {
            show(403, $validate->getError());
        }
        $info  = Db::name('user')->where('id', request()->uid)->field(['is_owner'])->find();
        $owner = Db::name('user_owner')->where('user_id', request()->uid)->field(['registration_url'])->find();
        if (!empty($owner) && !empty($owner['registration_url'])) {
            if ($info['is_owner'] !== '1' && $info['is_owner'] !== '2') {
                show(403, "请勿重复提交！");
            }
        }
        $this->authBase($owner, $data);
    }

    /**
     * 车主认证公共代码
     * @param array $owner 车主信息
     * @param array $data 提交信息
     */
    private function authBase(array $owner, array $data)
    {
        if (!empty($owner)) {
            // 存在信息则更新
            $res = UserOwnerModel::where('user_id', request()->uid)->save($data);
        } else {
            // 添加
            $data['user_id'] = request()->uid;
            $res             = UserOwnerModel::create($data);
        }
        if ($res) {
            show(200, "提交成功！");
        } else {
            show(403, "提交失败！");
        }
    }

    /**
     * 我的订单列表
     * @throws \think\db\exception\DbException
     */
    public function indentList()
    {
        // 接收数据
        $data = Request::only(['per_page', 'current_page']);
        $info = Db::name('user_buylog')->where('user_id', request()->uid)
            ->order('create_time', 'desc')
            ->paginate([
                'list_rows' => $data['per_page'],
                'query'     => request()->param(),
                'var_page'  => 'page',
                'page'      => $data['current_page']
            ]);
        show(200, "获取数据成功！", $info->toArray() ?? []);
    }
}