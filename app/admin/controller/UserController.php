<?php
/**
 * FileName: 用户控制器
 * Description: 处理用户业务逻辑
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-05 17:40
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\controller;


use app\admin\model\UserModel;
use app\admin\model\UserOwnerModel;
use app\admin\validate\UserValidate;
use think\facade\Db;
use think\facade\Request;

class UserController extends CommonController
{
    /**
     * 用户列表
     * @throws \think\db\exception\DbException
     */
    public function list()
    {
        // 接收数据
        $data = Request::only(['keywords', 'per_page', 'current_page']);
        //查询所有用户
        $info = Db::name('user')
            ->whereLike('nickname|user|mobile|email', "%" . $data['keywords'] . "%")
            ->withoutField(['weixin_openid', 'gitee_openid', 'qq_openid', 'weibo_openid', 'money', 'cause', 'login_error', 'error_time', 'ban_time', 'lastlog_ip', 'lastlog_time', 'login_sum'])
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
     * 添加用户
     */
    public function add()
    {
        // 接收数据
        $data = Request::except(['create_time', 'update_time', 'wx_openid', 'qq_openid', 'weibo_openid', 'expenditure', 'money', 'cause', 'login_error', 'error_time', 'ban_time', 'lastlog_ip', 'lastlog_time', 'login_sum']);
        // 验证数据
        $validate = new UserValidate();
        if (!$validate->sceneAdd()->check($data)) {
            show(403, $validate->getError());
        }
        //对密码进行加密
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        // 执行添加
        $res = UserModel::create($data);
        if ($res) {
            // 如果是车主，则在车主数据表添加一条数据
            if ($data['is_owner'] == '2') {
                if (!$validate->sceneCheckOwner()->check($data)) {
                    show(403, $validate->getError());
                }
                $data['user_id'] = $res->id;
                UserOwnerModel::create($data);
            }
            show(201, "添加成功！");
        } else {
            show(403, "添加失败！");
        }
    }

    /**
     * 编辑用户
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit()
    {
        // 接收数据
        $data = Request::except(['create_time', 'update_time', 'status', 'wx_openid', 'qq_openid', 'weibo_openid', 'expenditure', 'money', 'cause', 'login_error', 'error_time', 'ban_time', 'lastlog_ip', 'lastlog_time', 'login_sum']);
        // 验证数据
        $validate = new UserValidate();
        if (!$validate->sceneEdit()->check($data)) {
            show(403, $validate->getError());
        }
        // 执行更新
        $user = UserModel::find($data['id']);
        // 判断密码是否已经修改
        if ($data['password'] !== '') {
            // 重新hash加密
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        // 判断是否已经设置了车主，设置了则判断车主数据表是否存在数据，不存在则添加
        if ($data['is_owner'] == '2') {
            if (!$validate->sceneCheckOwner()->check($data)) {
                show(403, $validate->getError());
            }
            $owner = Db::name('user_owner')->where('user_id', $data['id'])->field('id')->find();
            $data['user_id'] = $data['id'];
            if (empty($owner)) {
                UserOwnerModel::create($data);
            } else {
                $ownerModel = UserOwnerModel::find($owner['id']);
                $ownerModel->save($data);
            }
        }
        $res = $user->save($data);
        if ($res) {
            show(200, "修改成功！");
        } else {
            show(403, "修改失败！");
        }
    }

    /**
     * 根据ID获取用户信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function query()
    {
        // 接收用户ID
        $id = Request::param('id');
        // 查询用户信息
        $info = Db::name('user')->withoutField(['weixin_openid', 'gitee_openid', 'qq_openid', 'weibo_openid', 'login_error', 'error_time', 'ban_time', 'lastlog_ip', 'lastlog_time', 'login_sum'])->where('id', $id)->find();
        // 查询车主信息
        $info['owner'] = Db::name('user_owner')->where('user_id', $id)->find();
        show(200, "获取数据成功！", $info ?? []);
    }

    /**
     * 删除用户
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
        $res = Db::name('user')->delete($array);
        if ($res) {
            Db::name('user_owner')->delete($array);
            show(200, "删除成功！");
        } else {
            show(200, "删除失败！");
        }
    }

    /**
     * 修改用户的状态状态
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function statusEdit()
    {
        // 接收ID
        $data = Request::only(['id', 'status']);
        // 执行更新
        $user = UserModel::find($data['id']);
        $res = $user->save($data);
        if ($res) {
            show(200, "修改成功！");
        } else {
            show(403, "修改失败！");
        }
    }

    /**
     * 用户消费明细
     * @throws \think\db\exception\DbException
     */
    public function buyLog()
    {
        // 接收数据
        $data = Request::only(['keywords', 'per_page', 'current_page']);
        //查询所有用户
        $info = Db::name('user_buylog')
            ->whereLike('indent|uid|start|end', "%" . $data['keywords'] . "%")
            ->order('create_time', 'desc')
            ->paginate([
                'list_rows' => $data['per_page'],
                'query' => request()->param(),
                'var_page' => 'page',
                'page' => $data['current_page']
            ]);
        show(200, "获取数据成功！", $info->toArray() ?? []);
    }
}