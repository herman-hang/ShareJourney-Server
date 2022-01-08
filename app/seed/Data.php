<?php
/**
 * FileName: 数据填充工厂
 * Description: 用于数据填充
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-01-02 0:08
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\seed;


use app\admin\model\UserModel;
use app\admin\model\UserOwnerModel;
use Faker\Factory;
use think\facade\Db;

class Data
{
    /**
     * 批量填充用户
     * @throws \Throwable
     */
    public function addUserDataSeed()
    {
        // faker默认语言是英文会生成英文的数据，在创建实例的时候可以指定为中文
        $faker = Factory::create('zh_CN');
        for ($i = 0; $i < 100; $i++) {
            $rows[] = [
                'user'         => $faker->numberBetween($min = 100000, $max = 9999999999),
                'password'     => password_hash("123456", PASSWORD_BCRYPT),
                'nickname'     => $faker->name,
                'name'         => $faker->name,
                'age'          => $faker->numberBetween($min = 0, $max = 120),
                'card'         => 450981 . $faker->year($max = 'now') . $faker->month($max = 'now') . $faker->dayOfMonth($max = 'now') . $faker->numberBetween($min = 1000, $max = 9999),
                'sex'          => $faker->numberBetween($min = 0, $max = 2),
                'region'       => $faker->address,
                'mobile'       => $faker->numberBetween($min = 15200000000, $max = 15299999999),
                'email'        => $faker->email,
                'qq'           => $faker->numberBetween($min = 10000, $max = 99999999999),
                'introduction' => '这是一个用户',
                'create_time'  => time(),
                'update_time'  => time(),
                'status'       => $faker->numberBetween($min = 0, $max = 1),
                'money'        => $faker->numberBetween($min = 0, $max = 100),
                'expenditure'  => $faker->numberBetween($min = 0, $max = 100),
                'is_owner'     => 0,
            ];
        }
        throw_if(!Db::name('user')->insertAll($rows), new \Exception("添加失败！"));
    }

    /**
     * 批量填充车主
     * @throws \Throwable
     */
    public function addOwnerDataSeed()
    {
        // faker默认语言是英文会生成英文的数据，在创建实例的时候可以指定为中文
        $faker = Factory::create('zh_CN');
        for ($i = 0; $i < 100; $i++) {
            $rows[] = [
                'user'         => $faker->numberBetween($min = 100000, $max = 9999999999),
                'password'     => password_hash("123456", PASSWORD_BCRYPT),
                'nickname'     => $faker->name,
                'name'         => $faker->name,
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
                'is_owner'     => $faker->numberBetween($min = 0, $max = 2),
            ];
        }
        $userModel = new UserModel();
        $result    = $userModel->saveAll($rows);
        foreach ($result as $key => $value) {
            if ($value['is_owner'] !== 0) {
                $row[] = [
                    'user_id'          => $value['id'],
                    'service'          => $faker->numberBetween($min = 1, $max = 50),
                    'km'               => $faker->numberBetween($min = 1, $max = 100),
                    'patente_url'      => $faker->imageUrl($width = 640, $height = 480),
                    'registration_url' => $faker->imageUrl($width = 640, $height = 480),
                    'car_url'          => $faker->imageUrl($width = 640, $height = 480),
                    'plate_number'     => '粤A' . $faker->century . $faker->numberBetween($width = 000, $height = 999),
                    'capacity'         => $faker->numberBetween($min = 1, $max = 9),
                    'color'            => $faker->colorName,
                    'status'           => $faker->numberBetween($width = 0, $height = 1),
                    'alipay'           => $faker->numberBetween($min = 100000, $max = 9999999999),
                    'alipay_name'      => $faker->name,
                    'wxpay'            => $faker->numberBetween($min = 100000, $max = 9999999999),
                    'wxpay_name'       => $faker->name,
                    'bank_card'        => $faker->numberBetween($min = 1000000000000000000, $max = 1999999999999999999),
                    'bank_card_name'   => $faker->name,
                    'bank_card_type'   => "中国农业银行",
                ];
            }
        }
        $ownerModel = new UserOwnerModel();
        throw_if(!$ownerModel->saveAll($row), new \Exception("添加失败！"));
    }

    /**
     * 批量填充用户支付订单
     * @throws \Throwable
     */
    public function addUserBuyDataSeed()
    {
        // faker默认语言是英文会生成英文的数据，在创建实例的时候可以指定为中文
        $faker = Factory::create('zh_CN');
        for ($i = 0; $i < 50; $i++) {
            $rows[] = [
                'user'         => $faker->numberBetween($min = 100000, $max = 9999999999),
                'password'     => password_hash("123456", PASSWORD_BCRYPT),
                'nickname'     => $faker->name,
                'name'         => $faker->name,
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
                'is_owner'     => $faker->numberBetween($min = 0, $max = 2),
            ];
        }
        Db::transaction(function () use ($rows, $faker) {
            $userModel = new UserModel();
            $result    = $userModel->saveAll($rows);
            foreach ($result as $key => $value) {
                $row[] = [
                    'uid'          => $value['id'],
                    'indent'       => trade_no(),
                    'pay_type'     => $faker->numberBetween($min = 0, $max = 2),
                    'buy_ip'       => $faker->ipv4,
                    'status'       => $faker->numberBetween($min = 0, $max = 1),
                    'introduction' => '这是一份订单',
                    'money'        => $faker->numberBetween($min = 0, $max = 100),
                    'start'        => $faker->address,
                    'end'          => $faker->address,
                    'create_time'  => time()
                ];
            }
            throw_if(!Db::name('user_buylog')->insertAll($row), new \Exception("添加失败！"));
        });
    }

    /**
     * 批量填充提现订单
     * @throws \Throwable
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addOwnerWithdrawDataSeed()
    {
        // faker默认语言是英文会生成英文的数据，在创建实例的时候可以指定为中文
        $faker = Factory::create('zh_CN');
        $owner = Db::name('user_owner')->limit(50)->select();
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
        }
        throw_if(!Db::name('owner_withdraw')->insertAll($rows), new \Exception("添加失败！"));
    }

    /**
     * 批量填充旅途数据
     * @throws \Throwable
     */
    public function addJourneyDataSeed()
    {
        // faker默认语言是英文会生成英文的数据，在创建实例的时候可以指定为中文
        $faker = Factory::create('zh_CN');
        for ($i = 0; $i < 100; $i++) {
            $rows[] = [
                'start'       => $faker->address,
                'end'         => $faker->address,
                'type'        => $faker->numberBetween($min = 0, $max = 1),
                'sum'         => $faker->numberBetween($min = 0, $max = 12),
                'current'     => '1,2,3',
                'create_time' => time(),
                'update_time' => time(),
                'deadline'    => strtotime('+1second'),
                'status'      => $faker->numberBetween($min = 0, $max = 4),
                'uid'         => $faker->numberBetween($min = 653, $max = 661)
            ];
        }
        throw_if(!Db::name('journey')->insertAll($rows), new \Exception("添加失败！"));
    }
}