<?php
/**
 * FileName: 车主量数据填充器
 * Description: 用于填充车主数据
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-03-09 22:28
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */
declare (strict_types=1);

namespace app\command;

use app\mobile\model\OwnerWithdrawModel;
use Faker\Factory;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

class OwnerWithdraw extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('withdraw:seed')
            ->setDescription('the withdraw:seed command');
    }

    protected function execute(Input $input, Output $output)
    {
        // faker默认语言是英文会生成英文的数据，在创建实例的时候可以指定为中文
        $faker = Factory::create('zh_CN');
        $owner = Db::name('user_owner')->where('status', '1')->limit(50)->select();
        foreach ($owner as $key => $value) {
            $rows[] = [
                'user_id'          => $value['user_id'],
                'owner_id'         => $value['id'],
                'money'            => $faker->numberBetween($min = 0, $max = 100),
                'create_time'      => time(),
                'cause'            => '驳回订单，没有原因，任性，咋滴！！',
                'status'           => $faker->numberBetween($min = 0, $max = 2),
                'indent'           => trade_no(),
                'withdraw_account' => $faker->numberBetween($min = 0, $max = 2)
            ];
            $output->writeln("Order Generation-{$key}%");
        }
        (new OwnerWithdrawModel())->saveAll($rows);
        $output->writeln("Order Generation-100%");
        // 指令输出
        $output->writeln('withdraw:seed success!');
    }
}
