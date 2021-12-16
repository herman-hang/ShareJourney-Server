<?php
/**
 * FileName: 邮件发送队列
 * Description: 异步处理邮件发送
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-16 19:02
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\job;


use think\facade\Db;
use think\facade\Log;
use think\queue\Job;

class SendEmailJob
{
    /**
     * 全局邮件发送队列
     * @param Job $job 队列
     * @param array $data 数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function fire(Job $job, array $data)
    {
        if ($job->attempts() > 3) {
            //执行失败写入错误日志
            Log::error('邮件发送队列失败！');
            //删除这个任务
            $job->delete();
        } else {
            //查询邮件配置信息
            $info = Db::name('email')->where('id', 1)->find();
            //查询网站信息
            $system = Db::name('system')->where('id', 1)->field('name,logo,domain')->find();
            if ($info) {
                // 构造HTML模板
                $html = email_html($system['domain'], $system['logo'], $data['user'], $data['content'], $system['name']);
                //执行邮件发送
                send_email($info['email'], $info['key'], $info['stmp'], $info['sll'], $system['name'], $data['title'], $html, $data['email']);
            }
            // 删除任务
            $job->delete();
        }
    }
}