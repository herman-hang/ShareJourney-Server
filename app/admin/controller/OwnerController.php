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


use app\admin\model\OwnerModel;
use app\admin\model\User as UserModel;
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
        $data = Request::only(['id','service','km','patente_url','registration_url','car_url','plate_number','capacity','color','alipay','alipay_name','wxpay', 'wxpay_name', 'bank_card', 'bank_card_name', 'bank_card_type']);
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
        $info = Db::name('user_owner')->where('id',$id)->field(['id','service','km','patente_url','registration_url','car_url','plate_number','capacity','color','alipay','alipay_name','wxpay', 'wxpay_name', 'bank_card', 'bank_card_name', 'bank_card_type'])->find();
        show(200, "获取数据成功！", $info ?? []);
    }
}