<?php
/**
 * FileName: 管理员日志记录队列任务
 * Description: 管理员日志记录队列
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2021-12-04 11:43
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\admin\job;


use app\admin\controller\CommonController;
use think\Log;
use think\queue\Job;

class AdminLogJob
{
    /**
     * 逻辑处理
     * @param Job $job
     * @param array $data
     */
    public function fire(Job $job, array $data)
    {
        // 重复执行3次后停止
        if ($job->attempts() > 3) {
            Log::error('记录日志失败！');
        } else {
            if (!empty($data['uid'])) {
                CommonController::log($data['msg'], 1, $data['uid']);
            } else {
                CommonController::log($data['msg']);
            }
        }
        // 删除任务
        $job->delete();
    }
}