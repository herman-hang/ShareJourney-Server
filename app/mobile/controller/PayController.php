<?php
/**
 * FileName: 微信小程序支付控制器
 * Description: 处理微信小程序支付相关业务
 * @Author: XieGuanHua
 * @Email: wetalk.vip@foxmail.com
 * DateTime: 2022-02-23 19:52
 * Aphorism: 尊重是一种修养，知性而优雅，将人格魅力升华，大爱无声挥洒；尊重两个字看似轻于鸿毛，实则重于泰山。
 */

namespace app\mobile\controller;


use app\mobile\model\JourneyModel;
use app\mobile\model\JourneyPassModel;
use app\mobile\model\UserBuyLog;
use app\mobile\validate\PayValidate;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;
use Yansongda\Pay\Pay;

class PayController extends CommonController
{
    /**
     * 发起支付
     * @throws \Yansongda\Pay\Exception\ContainerDependencyException
     * @throws \Yansongda\Pay\Exception\ContainerException
     * @throws \Yansongda\Pay\Exception\ServiceNotFoundException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function wechatPay()
    {
        // 检测是否有进行的订单有直接返回
        $this->checkIndentStatus();
        // 接收微信临时登录凭证
        $data = Request::only(['code', 'site', 'type', 'trip', 'line']);
        // 验证数据
        $validate = new PayValidate();
        if (!$validate->sceneWechatPay()->check($data)) {
            show(403, $validate->getError());
        }
        /*if (request()->scheme() !== 'https') {
            show(403, '请配置HTTPS协议进行支付！');
        }*/
        // 获取缓存数据
        $cacheData = Cache::get('trip_data_' . request()->uid);
        // 查询支付配置信息
        /*       $info = Db::name('pay')->where('id', 1)->field([
                   'wx_mini_appid',
                   'wx_mini_secret',
                   'wxpay_key'
               ])->find();
               // 微信小程序支付auth.code2Session的API
               $oauth2Url = 'https://api.weixin.qq.com/sns/jscode2session' . '?appid=' . $info['wx_mini_appid'] . '&secret=' . $info['wx_mini_secret'] . '&js_code=' . $data['code'] . '&grant_type=authorization_code';
               // 获取用户openid
               $response = $this->http($oauth2Url);*/
        // 订单号
        $tradeNo = trade_no();
//        if (!empty($response->openid)) {
        /*            Pay::config($this->getWechatPayConfig());
                    $order = [
                        'out_trade_no' => $tradeNo,
                        'description'  => '出行旅途车费',
                        'amount'       => [
                            'total'    => $cacheData['money'] * 100,
                            'currency' => 'CNY',
                        ],
                        'payer'        => [
                            'openid' => $response->openid,
                        ]
                    ];*/
        /*$result = Pay::wechat()->mini($order);
        if (!empty($result['prepay_id'])) {
        $payData['appId']     = $info['wx_mini_appid'];
        $payData['nonceStr']  = $this->createNoncestr();
        $payData['package']   = 'prepay_id=' . $result['prepay_id'];
        $payData['signType']  = 'MD5';
        $payData['timeStamp'] = (string)time();
        $payData['paySign']   = $this->getSign($payData, $info['wxpay_key']);*/

        // 记录订单
        $indent['user_id']      = request()->uid;
        $indent['indent']       = $tradeNo;
        $indent['pay_type']     = '0';
        $indent['buy_ip']       = Request::ip();
        $indent['status']       = '0';// 待付款状态
        $indent['introduction'] = '一笔旅行出行车费';
        $indent['money']        = $cacheData['money'];
        $indent['km']           = $cacheData['km'];

        $site            = $this->getStartEnd($data);
        $indent['start'] = $site['start'];
        $indent['end']   = $site['end'];
        // 生成订单
        $buyLog = UserBuyLog::create($indent);
        if ($buyLog) {
            $data['indent']         = $tradeNo;
            $data['user_id']        = request()->uid;
            $data['scheduled_time'] = $cacheData['time'];
            // 缓存数据,有效期两个小时
            Cache::set($tradeNo, $data, 7200);
            // 删除缓存
            Cache::delete('trip_data_' . request()->uid);
            $this->wechatPayCallback($tradeNo);// 测试专用，正式环境请注释这一行
            show(200, '订单生成完成！', $payData ?? []);
        }
//                }

        /*        } else {
                    show(403, '获取用户openId失败！');
                }*/
    }

    /**
     * 微信支付回调
     */
    public function wechatPayCallback($tradeNo)// 参数为测试使用，正式环境请配置支付信息
    {
        // 获取缓存消息
        $info = Cache::get($tradeNo);
        if (empty($info)) return false;
        $site                      = $this->getStartEnd($info);
        $journey['start']          = $site['start'];
        $journey['end']            = $site['end'];
        $journey['type']           = $info['type'];
        $journey['deadline']       = strtotime($info['trip']['deadline']);
        $journey['trip']           = $info['trip']['number'];
        $journey['user_id']        = $info['user_id'];
        $journey['scheduled_time'] = $info['scheduled_time'];
        $journey['line']           = $info['line'];
        // 插入旅途表
        $journeyResult = JourneyModel::create($journey);
        if ($journeyResult) {
            // 修改订单状态为已付款
            Db::name('user_buylog')->where('indent', $tradeNo)->update(['status' => '1', 'journey_id' => $journeyResult->id]);
            if (count($info['site']) == 2) {
                $journeyPass['journey_id'] = $journeyResult->id;
                $journeyPass['end']        = $info['site'][1]['destination'];
                $journeyPass['end_id']     = $info['site'][1]['id'];
                $journeyPass['status']     = '0';
                $journeyPass['user_id']    = $info['user_id'];
                $journeyPassResult         = JourneyPassModel::create($journeyPass ?? []);
            } else {
                foreach ($info['site'] as $key => $value) {
                    if ($value['id'] !== 0) {
                        $journeyPass[$key]['journey_id'] = $journeyResult->id;
                        $journeyPass[$key]['end']        = $value['destination'];
                        $journeyPass[$key]['end_id']     = $value['id'];
                        $journeyPass[$key]['status']     = '0';
                        $journeyPass[$key]['user_id']    = $info['user_id'];
                    }
                }
                // 插入旅途附属表
                $journeyPassResult = (new JourneyPassModel)->saveAll($journeyPass ?? []);
            }
            if ($journeyPassResult) {
                Cache::delete($tradeNo);
                $data['journey_id'] = $journeyResult->id;
                show(200, "支付成功！", $data);
            }
        }
        /*Pay::config($this->getWechatPayConfig());
        $result = Pay::wechat()->callback();
        if ($result['event_type'] === 'TRANSACTION.SUCCESS') {
            // 解密

            // 获取缓存信息
            $info = Cache::get($result['out_trade_no']);
            // 构建旅途信息

        }
        return Pay::wechat()->success();*/
    }

    /**
     * 获取微信小程序支付配置信息
     * @return \array[][]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function getWechatPayConfig(): array
    {
        // 查询支付配置信息
        $info = Db::name('pay')->where('id', 1)->field([
            'wxpay_key',
            'wxpay_mchid',
            'wx_mini_appid',
            'wx_mini_secret'
        ])->find();
        return [
            'wechat' => [
                'default' => [
                    // 必填-商户号，服务商模式下为服务商商户号
                    'mch_id'               => $info['wxpay_mchid'],
                    // 必填-商户秘钥
                    'mch_secret_key'       => $info['wxpay_key'],
                    // 必填-商户私钥 字符串或路径
                    'mch_secret_cert'      => '',
                    // 必填-商户公钥证书路径
                    'mch_public_cert_path' => '',
                    // 必填
                    'notify_url'           => Request::domain() . '/pay/wechat/callback',
                    // 选填-小程序 的 app_id
                    'mini_app_id'          => $info['wx_mini_appid'],
                ]
            ]
        ];
    }

    /**
     * 生成随机字符串方法
     * @param int $length 生成位数
     * @return string
     */
    private function createNonceStr(int $length = 32): string
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str   = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 对要发送到微信统一下单接口的数据进行签名
     * @param array $payParam 支付相关参数
     * @param string $apiKey API KEY
     * @return string
     */
    private function getSign(array $payParam, string $apiKey): string
    {
        foreach ($payParam as $k => $v) {
            $param[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($param);
        $string = $this->formatBizQueryParaMap($param, false);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $apiKey;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        return strtoupper($string);
    }

    /**
     * 排序并格式化参数方法，签名时需要使用
     * @param $paraMap
     * @param $urlEncode
     * @return false|string
     */
    private function formatBizQueryParaMap($paraMap, $urlEncode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlEncode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = "";
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}