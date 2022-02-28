<?php
/**
 * FileName: 旅途列表状态刷新队列
 * Description: 刷新旅途列表
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-02-27 14:55
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\job;


use app\mobile\controller\CommonController;
use think\App;
use think\facade\Log;
use think\queue\Job;

class RefreshJourneyJob
{
    /**
     * 刷新旅途信息列表
     * @param Job $job 队列
     * @param array $data 数据
     */
    public function fire(Job $job, array $data = [])
    {
        if ($job->attempts() > 3) {
            //执行失败写入错误日志
            Log::error('刷新旅途列表信息队列失败！');
            //删除这个任务
            $job->delete();
        } else {
            (new CommonController(new App()))->editDeadlineData();
            // 删除任务
            $job->delete();
        }
    }
}