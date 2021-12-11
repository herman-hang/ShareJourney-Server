<?php
/**
 * FileName: 邮件队列
 * Description: 用于异步发送邮件
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-11 17:13
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\job;


use think\facade\Log;
use think\queue\Job;

class AdminEmailJob
{
    /**
     * 邮件发送失败
     * @param Job $job 队列
     * @param array $data 业务参数
     */
    public function fire(Job $job,array $data)
    {
        if ($job->attempts() > 3) {
            //执行失败写入错误日志
            Log::error('邮件发送队列错误');
            //删除这个任务
            $job->delete();
        } else {
            //数据模拟定义
            $name = "我叫测试";
            $title = "这是邮件发送测试标题";
            $content = "我是邮件发送测试的内容！";
            //执行测试发送
            $res = sendEmail($data['email'], $data['key'], $data['stmp'], $data['sll'], $name, $title, $content, $data['test_email']);
            if (!$res){
                Log::error("邮件发送失败！");
            }
        }
    }
}