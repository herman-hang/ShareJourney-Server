<?php
/**
 * FileName: 管理员控制器
 * Description: 处理管理相关业务
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-04 17:03
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\controller;


use app\admin\model\AdminModel;
use app\admin\validate\AdminValidate;
use think\facade\Db;
use think\facade\Request;

class AdminController extends CommonController
{
    /**
     * 管理员列表
     * @throws \think\db\exception\DbException
     */
    public function list()
    {
        // 接收数据
        $data = Request::only(['keywords', 'per_page', 'current_page']);
        // ID为1的是超级管理员，拥有最高权限，输出全部管理员信息
        if (request()->uid == 1) {
            // 查询所有管理信息
            $list = Db::view('admin', 'id,user,password,photo,name,card,sex,age,region,mobile,email,introduction,create_time,update_time,status,role_id')
                ->view('group', 'name as rolename', 'admin.role_id=group.id')
                ->whereLike('admin.name|user|mobile|email', "%" . $data['keywords'] . "%")
                ->order('admin.create_time', 'desc')
                ->paginate([
                    'list_rows' => $data['per_page'],
                    'query' => request()->param(),
                    'var_page' => 'page',
                    'page' => $data['current_page']
                ]);
        } else {
            // 查询当前管理员的信息
            $list = Db::view('admin', 'user,password,photo,name,card,sex,age,region,mobile,email,introduction,create_time,update_time,status,role_id')
                ->view('group', 'name as rolename', 'admin.role_id=group.id')
                ->where('admin.id', request()->uid)
                ->whereLike('admin.name|user|mobile|email', "%" . $data['keywords'] . "%")
                ->order('admin.create_time', 'desc')
                ->paginate([
                    'list_rows' => $data['per_page'],
                    'query' => request()->param(),
                    'var_page' => 'page',
                    'page' => $data['current_page']
                ]);
        }
        show(200, "获取数据成功！", $list->toArray());
    }

    /**
     * 添加管理员
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add()
    {
        // 接收数据
        $data = Request::only(['user', 'password', 'passwords', 'photo', 'name', 'card', 'sex', 'age', 'region', 'mobile', 'email', 'introduction', 'status', 'role_id']);
        // 验证数据
        $validate = new AdminValidate();
        if (!$validate->sceneAdd()->check($data)) {
            show(403, $validate->getError());
        }
        //对密码进行加密
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        //执行添加并过滤非数据表字段
        $res = AdminModel::create($data);
        if ($res) {
            show(201, "添加成功！");
        } else {
            show(403, "添加失败！");
        }
    }

    /**
     * 编辑管理员
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit()
    {
        // 接收数据
        $data = Request::only(['id', 'user', 'password', 'photo', 'name', 'card', 'sex', 'age', 'region', 'mobile', 'email', 'introduction', 'role_id']);
        // 验证数据
        $validate = new AdminValidate();
        if (!$validate->sceneEdit()->check($data)) {
            show(403, $validate->getError());
        }
        // 执行更新
        $admin = AdminModel::find($data['id']);
        //如果为超级管理员，则可以修改密码，否则不行
        if (request()->uid == 1) {
            // 判断密码是否已经修改
            if ($data['password'] !== '') {
                // 重新hash加密
                $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }
        } else {
            // 普通管理不能修改密码，若存在密码则删除
            if (isset($data['password'])) {
                // 删除密码
                unset($data['password']);
            }
        }
        // 执行更新
        $res = $admin->save($data);
        if ($res) {
            show(200, "修改成功！");
        } else {
            show(403, '修改失败！');
        }
    }

    /**
     * 根据ID获取管理员数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function query()
    {
        // 接收ID
        $id = Request::param('id');
        // 查询当前编辑的管理员
        $info = Db::view('admin', 'id,user,password,photo,name,card,sex,age,region,mobile,email,introduction,create_time,update_time,role_id')
            ->view('group', 'name as rolename', 'group.id=admin.role_id')
            ->where('admin.id', $id)
            ->find();
        show(200, "获取数据成功！", $info ?? []);
    }

    /**
     * 管理员删除
     * @throws \think\db\exception\DbException
     */
    public function delete()
    {
        //接收前台传过来的ID
        $id = Request::param('id');
        if (!strpos($id, ',')) {
            $array = array($id);
        } else {
            //转为数组
            $array = explode(',', $id);
            // 删除数组中空元素
            $array = array_filter($array);
        }
        //判断是否存在超级管理员，是则不能删除
        if (!in_array(1, $array)) {
            //判断是否存在自己,是则不能删除
            if (!in_array(request()->uid, $array)) {
                //进行删除操作
                $res = Db::name('admin')->delete($array);
                if ($res) {
                    show(200, "删除成功！");
                } else {
                    show(403, "删除失败！");
                }
            } else {
                show(403, "自己不能删除！");
            }
        } else {
            show(403, "超级管理员不能删除！");
        }
    }

    /**
     * 修改管理员的状态
     * @throws \think\db\exception\DbException
     */
    public function statusEdit()
    {
        // 接收管理员ID
        $data = Request::only(['id', 'status']);
        if ($data['id'] == 1 && $data['status'] == 0) {
            show(403, "超级管理员状态不能修改！");
        } elseif ($data['id'] == request()->uid && $data['status'] == 0) {
            show(403, "自己的状态不能修改！");
        } else {
            // 执行更新
            $admin = AdminModel::find($data['id']);
            $res = $admin->save($data);
            if ($res) {
                show(200, "修改成功！");
            } else {
                show(403, "修改失败！");
            }
        }
    }
}