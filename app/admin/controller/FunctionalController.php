<?php
/**
 * FileName: 共嫩配置控制器
 * Description: 专用于配置各种接口信息
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-11 12:08
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\controller;


use app\admin\validate\FunctionalValidate;
use think\api\Client;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Request;

class FunctionalController extends CommonController
{
    /**
     * 获取支付配置信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function pay()
    {
        //查询所有支付配置信息
        $info = Db::name('pay')->where('id', 1)->withoutField('id,epay_api,epay_appid,epay_key')->find();
        show(200, "获取数据成功！", $info ?? []);
    }

    /**
     * 修改支付配置信息
     * @throws \think\db\exception\DbException
     */
    public function payEdit()
    {
        $data = Request::except(['id']);
        //当存在数据时执行更新数据
        $res = Db::name('pay')->where('id', 1)->update($data);
        //判断返回的值是否为true
        if ($res) {
            show(200, "修改成功！");
        } else {
            show(200, "修改失败！");
        }
    }

    /**
     * 短信配置
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sms()
    {
        //查询短信配置信息
        $info = Db::name('sms')->where('id', 1)->withoutField('id')->find();
        show(200, "获取数据成功！", $info ?? []);
    }

    /**
     * 编辑短信配置
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function smsEdit()
    {
        // 接收数据
        $data = Request::except(['phone', 'id']);
        // 验证数据
        $validate = new FunctionalValidate();
        if (!$validate->sceneSmsEdit()->check($data)) {
            show(403, $validate->getError());
        }
        //查询短信宝密码
        $info = Db::name('sms')->where('id', 1)->field('smsbao_pass,app_code')->find();
        //对密码进行MD5算法加密
        if ($data['smsbao_pass'] !== $info['smsbao_pass']) {
            $data['smsbao_pass'] = md5($data['smsbao_pass']);
        }
        if ($data['app_code'] !== $info['app_code']) {
            $data['app_code'] = md5($data['app_code']);
        }
        //当存在数据时执行更新数据
        $res = Db::name('sms')->where('id', 1)->update($data);
        if ($res) {
            show(200, "修改成功！");
        } else {
            show(403, "修改失败！");
        }
    }

    /**
     * 短信测试
     * @throws \think\api\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function testSms()
    {
        // 接收数据
        $data = Request::param();
        $isPushed = Queue::push('app\admin\job\AdminSmsJob', $data, 'admin');
        if ($isPushed !== false) {
            show(200, "发送成功！");
        } else {
            show(403, "发送失败！");
        }
    }

    /**
     * 邮件配置
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function email()
    {
        //查询邮件配置信息
        $info = Db::name('email')->where('id', 1)->withoutField('id')->find();
        show(200, "获取数据成功！", $info ?? []);
    }

    /**
     * 编辑邮件配置
     * @throws \think\db\exception\DbException
     */
    public function emailEdit()
    {
        // 接收数据
        $data = Request::except(['test_email', 'id']);
        $res = Db::name('email')->where('id', 1)->update($data);
        //判断返回的值是否为true
        if ($res) {
            show(200, "修改成功！");
        } else {
            show(403, "修改失败！");
        }
    }

    /**
     * 测试邮件发送
     */
    public function testEmail()
    {
        //接收前台信息
        $data = Request::param();
        $isPushed = Queue::push('app\admin\job\AdminEmailJob', $data, 'admin');
        if ($isPushed !== false) {
            show(200, "发送成功！");
        } else {
            show(403, "发送失败！");
        }
    }

    /**
     * 第三方登录配置
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function thirdparty()
    {
        //查询第三方登录配置信息
        $info = Db::name('thirdparty')->where('id', 1)->withoutField('id')->find();
        show(200, "获取数据成功！", $info ?? []);
    }

    /**
     * 第三方登录配置编辑
     * @throws \think\db\exception\DbException
     */
    public function thirdpartyEdit()
    {
        //接收前台传过来的值
        $data = Request::except(['qq_callback', 'wx_callback', 'weibo_callback', 'gitee_callback', 'id']);
        //执行更新操作
        $res = Db::name('thirdparty')->where('id', 1)->update($data);
        //判断是否操作成功，true为操作成功
        if ($res) {
            show(200, "修改成功！");
        } else {
            show(403, "修改失败！");
        }
    }
}