<?php
/**
 * FileName: 车主控制器
 * Description: 处理车主逻辑业务
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-14 20:39
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\controller;


use app\admin\model\UserOwnerModel;
use app\admin\validate\OwnerValidate;
use think\facade\Db;
use think\facade\Request;

class OwnerController extends CommonController
{
    /**
     * 车主列表
     * @throws \think\db\exception\DbException
     */
    public function list()
    {
        // 接收数据
        $data = Request::only(['keywords', 'per_page', 'current_page']);
        //查询所有车主
        $info = Db::view('user', 'user,name,money,mobile,age,sex')
            ->view('user_owner', 'id,service,km,status', 'user_owner.user_id=user.id')
            ->whereLike('name|user|mobile', "%" . $data['keywords'] . "%")
            ->where('is_owner', '2')
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
     * 编辑车主状态
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function statusEdit()
    {
        // 接收ID
        $data = Request::only(['id', 'status']);
        // 执行更新
        $user = UserOwnerModel::find($data['id']);
        $res = $user->save($data);
        if ($res) {
            show(200, "修改成功！");
        } else {
            show(403, "修改失败！");
        }
    }

    /**
     * 编辑车主
     * @throws \think\db\exception\DbException
     */
    public function edit()
    {
        // 接收数据
        $data = Request::only(['id', 'service', 'km', 'patente_url', 'registration_url', 'car_url', 'plate_number', 'capacity', 'color', 'alipay', 'alipay_name', 'wxpay', 'wxpay_name', 'bank_card', 'bank_card_name', 'bank_card_type']);
        // 验证数据
        $validate = new OwnerValidate();
        if (!$validate->sceneEdit()->check($data)) {
            show(403, $validate->getError());
        }
        // 更新数据
        $res = Db::name('user_owner')->where('id', $data['id'])->update($data);
        if ($res) {
            show(200, "修改成功！");
        } else {
            show(403, "修改失败！");
        }
    }

    /**
     * 根据ID查询车主信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function query()
    {
        $id = Request::param('id');
        // 查询车主信息
        $info = Db::name('user_owner')->where('id', $id)->field(['id', 'service', 'km', 'patente_url', 'registration_url', 'car_url', 'plate_number', 'capacity', 'color', 'alipay', 'alipay_name', 'wxpay', 'wxpay_name', 'bank_card', 'bank_card_name', 'bank_card_type'])->find();
        show(200, "获取数据成功！", $info ?? []);
    }

    /**
     * 车主提现审核列表
     * @throws \think\db\exception\DbException
     */
    public function withdrawList()
    {
        // 接收数据
        $data = Request::only(['keywords', 'per_page', 'current_page', 'status']);
        $info = Db::name('owner_withdraw')
            ->field('id,money,create_time,cause,indent,withdraw_account,status,user_id,owner_id')
            ->where('status', $data['status'])
            ->whereLike('indent', "%" . $data['keywords'] . "%")
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
     * 车主提现审核列表审核通过操作
     * @throws \think\db\exception\DbException
     */
    public function pass()
    {
        $id = Request::param('id');
        $res = Db::name('owner_withdraw')->where('id', $id)->update(['status' => '1']);
        if ($res) {
            show(200, '审核成功！');
        } else {
            show(403, '审核失败！');
        }
    }

    /**
     * 车主提现审核列表驳回操作
     * @throws \think\db\exception\DbException
     */
    public function reject()
    {
        $data = Request::only(['id', 'cause']);
        $res = Db::name('owner_withdraw')->where('id', $data['id'])->update(['status' => '2', 'cause' => $data['cause']]);
        if ($res) {
            show(200, '驳回成功！');
        } else {
            show(403, '驳回失败！');
        }
    }

    /**
     * 根据车主ID获取提现详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function withdrawQuery()
    {
        $id = Request::param('id');
        $info = Db::view('owner_withdraw', 'id,create_time,money as apply_money,indent,withdraw_account,status,cause')
            ->view('user', 'user,name,mobile,email,money,id as user_id', 'user.id=owner_withdraw.user_id')
            ->view('user_owner', 'id as owner_id,service,km,alipay,alipay_name,wxpay,wxpay_name,bank_card,bank_card_name,bank_card_type', 'user_owner.id=owner_withdraw.owner_id')
            ->where('owner_withdraw.id', $id)
            ->find();
        show(200, "获取数据成功！", $info);
    }

    /**
     * 车主审核列表
     * @throws \think\db\exception\DbException
     */
    public function auditList()
    {
        // 接收数据
        $data = Request::only(['keywords', 'per_page', 'current_page']);
        $info = Db::view('user', 'id,user,name,sex,age,mobile,is_owner')
            ->view('user_owner', 'service,km,create_time,update_time', 'user_owner.user_id=user.id')
            ->where('user.is_owner', 'in', '1,3')
            ->whereLike('user|name|mobile', "%" . $data['keywords'] . "%")
            ->order('user_owner.create_time', 'desc')
            ->paginate([
                'list_rows' => $data['per_page'],
                'query' => request()->param(),
                'var_page' => 'page',
                'page' => $data['current_page']
            ]);
        show(200, "获取数据成功！", $info->toArray() ?? []);
    }

    /**
     * 根据车主ID获取审核详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function auditQuery()
    {
        // 接收ID
        $id = Request::param('id');
        // 关联查询
        $info = Db::view('user_owner', 'service,km,patente_url,registration_url,car_url,plate_number,capacity,color')
            ->view('user', 'user,name,card', 'user.id=user_owner.user_id')
            ->where('user_owner.id', $id)
            ->find();
        show(200, "获取数据成功！", $info);
    }

    /**
     * 车主审核通过操作
     * @throws \think\db\exception\DbException
     */
    public function auditPass()
    {
        $id = Request::param('id');
        // 状态改为车主
        $res = Db::name('user')->where('id', $id)->update(['is_owner' => '2']);
        if ($res) {
            // 状态改为正常
            Db::name('user_owner')->where('user_id',$id)->update(['status'=>'1']);
            show(200, '审核成功！');
        } else {
            show(403, '审核失败！');
        }
    }

    /**
     * 车主审核驳回操作
     * @throws \think\db\exception\DbException
     */
    public function auditReject()
    {
        $data = Request::only(['id', 'cause']);
        $res = Db::name('user')->where('id', $data['id'])->update(['is_owner' => '3', 'cause' => $data['cause']]);
        if ($res) {
            show(200, '驳回成功！');
        } else {
            show(403, '驳回失败！');
        }
    }
}