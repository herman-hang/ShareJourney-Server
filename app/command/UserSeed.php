<?php
/**
 * FileName: 用户量数据填充器
 * Description: 用于填充用户数据
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-03-08 21:15
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */
declare (strict_types=1);

namespace app\command;

use app\admin\model\UserModel;
use Faker\Factory;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

class UserSeed extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('user:seed')
            ->setDescription('the user:seed command');
    }

    protected function execute(Input $input, Output $output)
    {
        // faker默认语言是英文会生成英文的数据，在创建实例的时候可以指定为中文
        $faker = Factory::create('zh_CN');
        for ($i = 0; $i < 100; $i++) {
            $rows[] = [
                'user'         => rand(10000, 9999999999),
                'password'     => password_hash("123456", PASSWORD_BCRYPT),
                'nickname'     => $faker->name,
                'name'         => $faker->name,
                'card'         => 450981 . $faker->year($max = 'now') . $faker->month($max = 'now') . $faker->dayOfMonth($max = 'now') . $faker->numberBetween($min = 1000, $max = 9999),
                'card_front'   => 'https://cdn.uviewui.com/uview/demo/upload/positive.png',
                'card_verso'   => 'https://cdn.uviewui.com/uview/demo/upload/positive.png',
                'sex'          => $faker->numberBetween($min = 0, $max = 2),
                'age'          => $faker->numberBetween($min = 0, $max = 120),
                'region'       => $faker->address,
                'mobile'       => $faker->numberBetween($min = 15200000000, $max = 15299999999),
                'email'        => $faker->email,
                'qq'           => $faker->numberBetween($min = 10000, $max = 99999999999),
                'introduction' => '这是一个用户',
                'status'       => $faker->numberBetween($min = 0, $max = 1),
                'money'        => $faker->numberBetween($min = 0, $max = 100),
                'expenditure'  => $faker->numberBetween($min = 0, $max = 100),
                'is_owner'     => '0'
            ];
            // 指令输出
            $output->writeln("User data filling progress-{$i}%");
        }
        (new UserModel())->saveAll($rows);
        // 指令输出
        $output->writeln('user:seed success!');
    }
}
