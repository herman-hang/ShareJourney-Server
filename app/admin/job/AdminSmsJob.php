<?php
/**
 * FileName: 短信消息队列
 * Description: 用于异步发送短信
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-11 16:20
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\job;


use think\api\Client;
use think\facade\Db;
use think\facade\Log;
use think\queue\Job;

class AdminSmsJob
{
    /**
     * 短信发送
     * @param Job $job 队列
     * @param array $data 业务参数
     * @throws \think\api\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function fire(Job $job, array $data)
    {
        if ($job->attempts() > 3) {
            //执行失败写入错误日志
            Log::error('短信发送队列错误');
            //删除这个任务
            $job->delete();
        } else {
            //调用随机验证码方法
            $code = code_str(2);
            //think:ThinkAPI接口，smsbao:短信宝接口
            if ($data['type'] == "think") {
                //查询AppCode
                $sms = Db::name('sms')->where('id', 1)->field('app_code')->find();
                //利用正则表达式检测当前的密码是否为MD5字符串
                if (!preg_match("/^[a-z0-9]{32}$/", $data['app_code'])) {
                    //对密码进行MD5算法加密
                    $data['app_code'] = md5($data['app_code']);
                }
                //实例化ThibkAPI短信接口
                $client = new Client($sms['app_code']);
                $res    = $client->smsSend()
                    ->withSignId($data['sign_id'])
                    ->withTemplateId('2')
                    ->withPhone($data['phone'])
                    ->withParams(json_encode(['code' => $code]))
                    ->request();
                $res    = $res['code'];
            } else {
                //利用正则表达式检测当前的密码是否为MD5字符串
                if (!preg_match("/^[a-z0-9]{32}$/", $data['smsbao_pass'])) {
                    //对密码进行MD5算法加密
                    $data['smsbao_pass'] = md5($data['smsbao_pass']);
                }
                //自定义测试短信内容
                $content = "【测试】这是一条测试内容，您的验证码是{$code}，在5分钟有效。";
                //调用发送函数
                $res = send_sms($data['smsbao_account'], $data['smsbao_pass'], $content, $data['phone']);
            }
            if ($res !== 0) {
                Log::error("发送失败");
            }
            // 删除任务
            $job->delete();
        }
    }
}