<?php
// 应用公共文件


use think\Response;

/**
 * 返回数据函数
 * @param int $code 状态码
 * @param string $msg 自定义消息
 * @param array $data 返回数据
 * @param string $type 返回的数据格式
 * @param array $header 返回的响应头
 */
function show(int $code = 200, string $msg = '操作成功', array $data = [], string $type = '', array $header = [])
{
    $result = [
        'code' => $code,
        'msg' => $msg,
        'time' => time(),
        'data' => $data,
    ];
    $type = $type ?: 'json';
    $response = Response::create($result, $type)->header($header);

    throw new \think\exception\HttpResponseException($response);
}

/**
 * 订单号生成函数
 * @return string
 */
function trade_no(): string
{
    list($usec, $sec) = explode(" ", microtime());
    $usec = substr(str_replace('0.', '', $usec), 0, 4);
    $str = rand(10, 99);
    return date("YmdHis") . $usec . $str;
}

/**
 * 生成6位随机验证码函数
 * @param int $type 验证码类型：1为邮件验证码，2为短信验证码
 * @return string
 */
function code_str(int $type = 1): string
{
    //邮件验证码
    if ($type == 1) {
        $arr = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
    } elseif ($type == 2) {//短信验证码
        $arr = array_merge(range('0', '9'));
    }
    shuffle($arr);
    $arr = array_flip($arr);
    $arr = array_rand($arr, 6);
    $res = '';
    foreach ($arr as $v) {
        $res .= $v;
    }
    return $res;
}

/**
 * 短信接口
 * @param string $user 短信宝账号
 * @param string $pass MD5加密的密码
 * @param string $content 短信内容
 * @param string $phone 待发送的手机号码
 * @return string
 */
function send_sms(string $user, string $pass, string $content, string $phone): string
{
    $statusStr = array(
        "0" => "短信发送成功",
        "-1" => "参数不全",
        "-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
        "30" => "密码错误",
        "40" => "账号不存在",
        "41" => "余额不足",
        "42" => "帐户已过期",
        "43" => "IP地址限制",
        "50" => "内容含有敏感词"
    );
    $smsApi = "http://www.smsbao.com/"; //短信网关
    $sendurl = $smsApi . "sms?u=" . $user . "&p=" . $pass . "&m=" . $phone . "&c=" . urlencode($content);
    $result = file_get_contents($sendurl);
    return $statusStr[$result];
}