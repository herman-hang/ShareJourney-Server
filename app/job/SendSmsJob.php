<?php
/**
 * FileName: 发送手机短信队列
 * Description: 用于发送手机短信
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-16 20:20
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\job;


use think\api\Client;
use think\facade\Db;
use think\facade\Log;
use think\queue\Job;

class SendSmsJob
{
    /**
     * 全局短信发送队列
     * @param Job $job 队列
     * @param array $data 数据
     * @throws \think\api\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function fire(Job $job, array $data)
    {
        if ($job->attempts() > 3) {
            //执行失败写入错误日志
            Log::error('短信发送队列失败！');
            //删除这个任务
            $job->delete();
        } else {
            //查询AppCode
            $sms = Db::name('sms')->where('id', 1)->find();
            if ($data['type'] == 0) {
                //实例化ThinkAPI短信接口
                $client = new Client($sms['app_code']);
                $client->smsSend()
                    ->withSignId($sms['sign_id'])
                    ->withTemplateId($data['tempId'])
                    ->withPhone($data['mobile'])
                    ->withParams(json_encode(['code' => $data['code']]))
                    ->request();
            } else if ($data['type'] == 1) {
                //调用发送函数
                send_sms($sms['smsbao_account'], $sms['smsbao_pass'], $data['content'], $data['mobile']);
            }
            // 删除任务
            $job->delete();
        }
    }
}