<?php
/**
 * FileName: 车主量数据填充器
 * Description: 用于填充车主数据
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-03-08 21:18
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */
declare (strict_types=1);

namespace app\command;

use app\admin\model\UserModel;
use app\admin\model\UserOwnerModel;
use Faker\Factory;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class OwnerSeed extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('owner:seed')
            ->setDescription('the owner:seed command');
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
                'card_front'   => 'https://cdn.uviewui.com/uview/demo/upload/positive.png',
                'card_verso'   => 'https://cdn.uviewui.com/uview/demo/upload/positive.png',
                'age'          => $faker->numberBetween($min = 0, $max = 120),
                'card'         => 450981 . $faker->year($max = 'now') . $faker->month($max = 'now') . $faker->dayOfMonth($max = 'now') . $faker->numberBetween($min = 1000, $max = 9999),
                'sex'          => $faker->numberBetween($min = 0, $max = 2),
                'region'       => $faker->address,
                'mobile'       => $faker->numberBetween($min = 15200000000, $max = 15299999999),
                'email'        => $faker->email,
                'qq'           => $faker->numberBetween($min = 10000, $max = 99999999999),
                'introduction' => '这是一个用户',
                'status'       => $faker->numberBetween($min = 0, $max = 1),
                'money'        => $faker->numberBetween($min = 0, $max = 100),
                'expenditure'  => $faker->numberBetween($min = 0, $max = 100),
                'is_owner'     => $faker->numberBetween($min = 1, $max = 3),
                'cause'        => '测试驳回原因'
            ];
            $output->writeln("User generated-{$i}%");
        }
        $userModel = new UserModel();
        $result    = $userModel->saveAll($rows);
        foreach ($result as $key => $value) {
            $row[] = [
                'user_id'          => $value['id'],
                'service'          => $faker->numberBetween($min = 1, $max = 50),
                'km'               => $faker->numberBetween($min = 1, $max = 100),
                'patente_url'      => $faker->imageUrl($width = 640, $height = 480),
                'registration_url' => $faker->imageUrl($width = 640, $height = 480),
                'car_url'          => $faker->imageUrl($width = 640, $height = 480),
                'plate_number'     => '粤A' . $faker->century . $faker->numberBetween($width = 000, $height = 999),
                'capacity'         => $faker->numberBetween($min = 5, $max = 9),
                'color'            => $faker->colorName,
                'status'           => $faker->numberBetween($width = 0, $height = 1),
                'alipay'           => $faker->numberBetween($min = 100000, $max = 9999999999),
                'alipay_name'      => $faker->name,
                'wxpay'            => $faker->numberBetween($min = 100000, $max = 9999999999),
                'wxpay_name'       => $faker->name,
                'bank_card'        => $faker->numberBetween($min = 1000000000000000000, $max = 1999999999999999999),
                'bank_card_name'   => $faker->name,
                'bank_card_type'   => "中国农业银行"
            ];
            $output->writeln("As the owner-{$key}%");
        }
        (new UserOwnerModel())->saveAll($row);
        // 指令输出
        $output->writeln('owner:seed success!');
    }
}
