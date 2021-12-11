<?php
// 应用公共文件

use PHPMailer\PHPMailer\PHPMailer;
use think\facade\Config;
use think\facade\Request;
use think\Response;
use think\facade\Queue;

/**
 * 返回数据函数
 * @param int $code 状态码
 * @param string $msg 自定义消息
 * @param int $uid 管理员id
 * @param array $data 返回数据
 * @param string $type 返回的数据格式
 * @param array $header 返回的响应头
 */
function show(int $code = 200, string $msg = '操作成功',  array $data = [], int $uid = 0, string $type = '', array $header = [])
{
    // 拼接url
    $url = strtolower(Request::controller() . '/' . Request::action());
    $notAuthRoute = Config::get('auth');
    // 将数组中的每一项全部转为小写
    $notAuth = array_map('strtolower', $notAuthRoute['not_auth']);
    if (!in_array($url, $notAuth)) {
        $info['msg'] = $msg;
        if ($uid !== 0){
            $info['uid'] = $uid;
        }
        // 推送到消息队列
        Queue::later(3, 'app\admin\job\AdminLogJob', $info, 'admin');
    }
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

/**
 * 邮件发送
 * @param string $email 登录邮箱
 * @param string $emailpaswsd 安全码/授权码
 * @param string $smtp 邮箱的服务器地址
 * @param string $sll 端口
 * @param string $emname 发件人昵称
 * @param string $title 邮件主题
 * @param string $content 邮件内容
 * @param string $toemail 收件人邮箱
 * @return bool
 */
function sendEmail(string $email, string $emailpaswsd, string $smtp, string $sll, string $emname, string $title, string $content, string $toemail): bool
{
    $mail = new PHPMailer(true);// Passing `true` enables exceptions
    try {
        //设定邮件编码
        $mail->CharSet = "UTF-8";
        // 调试模式输出
        $mail->SMTPDebug = 0;
        // 使用SMTP
        $mail->isSMTP();
        // SMTP服务器
        $mail->Host = $smtp;
        // 允许 SMTP 认证
        $mail->SMTPAuth = true;
        // SMTP 用户名  即邮箱的用户名
        $mail->Username = $email;
        // SMTP 密码  部分邮箱是授权码(例如163邮箱)
        $mail->Password = $emailpaswsd;
        // 允许 TLS 或者ssl协议
        $mail->SMTPSecure = 'ssl';
        // 服务器端口 25 或者465 具体要看邮箱服务器支持
        $mail->Port = $sll;
        //发件人
        $mail->setFrom($email, $emname);
        // 收件人
        $mail->addAddress($toemail);
        // 可添加多个收件人
        //$mail->addAddress('ellen@example.com');
        //回复的时候回复给哪个邮箱 建议和发件人一致
        $mail->addReplyTo($toemail, $emname);
        //抄送
        //$mail->addCC('cc@example.com');
        //密送
        //$mail->addBCC('bcc@example.com');

        //发送附件
        // $mail->addAttachment('../xy.zip');// 添加附件
        // $mail->addAttachment('../thumb-1.jpg', 'new.jpg');// 发送附件并且重命名

        // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
        $mail->isHTML(true);
        $mail->Subject = $title;
        $mail->Body = $content;
        $mail->AltBody = '当前邮件客户端不支持HTML，请用浏览器登录邮箱查看内容！';
        // 发送邮件返回状态
        $status = $mail->send();
        return $status;
    } catch (Exception $e) {
        echo '邮件发送失败: ', $mail->ErrorInfo;
        return false;
    }
}