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
 * @param string $emailPassword 安全码/授权码
 * @param string $smtp 邮箱的服务器地址
 * @param string $sll 端口
 * @param string $emailName 发件人昵称
 * @param string $title 邮件主题
 * @param string $content 邮件内容
 * @param string $toEmail 收件人邮箱
 * @return bool
 */
function send_email(string $email, string $emailPassword, string $smtp, string $sll, string $emailName, string $title, string $content, string $toEmail): bool
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
        $mail->Password = $emailPassword;
        // 允许 TLS 或者ssl协议
        $mail->SMTPSecure = 'ssl';
        // 服务器端口 25 或者465 具体要看邮箱服务器支持
        $mail->Port = $sll;
        //发件人
        $mail->setFrom($email, $emailName);
        // 收件人
        $mail->addAddress($toEmail);
        // 可添加多个收件人
        //$mail->addAddress('ellen@example.com');
        //回复的时候回复给哪个邮箱 建议和发件人一致
        $mail->addReplyTo($toEmail, $emailName);
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

/**
 * 邮件HTML模板
 * @param string $domain 网站域名，带http/https
 * @param string $logo 网站LOGO
 * @param string $user 接收邮件用户名（本站）
 * @param string $content 邮件内容
 * @param string $name 网站名称
 * @return mixed
 */
function email_html(string $domain, string $logo, string $user, string $content, string $name): string
{
    return <<<EOT
<div style="position:relative;font-size:14px;height:auto;padding:15px 15px 10px 15px;z-index:1;zoom:1;line-height:1.7;"
	class="body">
	<div id="qm_con_body">
		<div id="mailContentContainer" class="qmbox qm_con_body_content qqmail_webmail_only" style="">
			<table style="font-family:'Microsoft YaHei';" width="800" cellspacing="0" cellpadding="0" border="0"
				bgcolor="#ffffff" align="center">
				<tbody>
					<tr>
						<td>
							<table style="font-family:'Microsoft YaHei';" width="800" height="48" cellspacing="0"
								cellpadding="0" border="0" bgcolor="#409EFF" align="center">
								<tbody>
									<tr>
										<td border="0" style="padding-left:20px;" height="48"
											align="center">
											<a href="{$domain}" target="_blank">
											<img src="{$logo}" alt="{$name}">
											</a>
										</td>
									</tr>
								</tbody>
							</table>

						</td>
					</tr>

					<tr>
						<td>
							<table
								style=" border:1px solid #edecec; border-top:none; padding:0 20px;font-size:14px;color:#333333;"
								width="800" cellspacing="0" cellpadding="0" border="0" align="left">
								<tbody>
									<tr>
										<td border="0" colspan="2"
											style=" font-size:16px;vertical-align:bottom;font-family:'Microsoft YaHei';"
											width="760" height="56" align="left">尊敬的
											<a target="_blank"
												style="font-size:16px; font-weight:bold;text-decoration: none;">{$user}</a>：
										</td>
									</tr>
									<tr>
										<td border="0" colspan="2" width="760" height="30" align="left">&nbsp;</td>
									</tr>
									<tr>
										<td border="0"
											style=" width:40px; text-align:left;vertical-align:middle; line-height:32px; float:left;"
											width="40" valign="middle" height="32" align="left"></td>
										<td border="0"
											style=" width:720px; text-align:left;vertical-align:middle;line-height:32px;font-family:'Microsoft YaHei';"
											width="720" valign="middle" height="32" align="left">
											{$content}
										</td>
									</tr>

									<tr>
										<td colspan="2"
											style="padding-bottom:16px; border-bottom:1px dashed #e5e5e5;font-family:'Microsoft YaHei';text-align: right;"
											width="720" height="14">{$name}</td>
									</tr>
									<tr>
										<td colspan="2"
											style="padding:8px 0 28px;color:#999999; font-size:12px;font-family:'Microsoft YaHei';"
											width="720" height="14">此为系统邮件请勿回复</td>
									</tr>
								</tbody>
							</table>

						</td>
					</tr>
				</tbody>
			</table>

			<style type="text/css">
				.qmbox style,
				.qmbox script,
				.qmbox head,
				.qmbox link,
				.qmbox meta {
					display: none !important;
				}

				.qmbox body {
					margin: 0 auto;
					padding: 0;
					font-family: Microsoft Yahei, Tahoma, Arial;
					color: #333333;
					background-color: #fff;
					font-size: 12px;
				}

				.qmbox a {
					color: #00a2ca;
					line-height: 22px;
					text-decoration: none;
				}

				.qmbox a:hover {
					text-decoration: underline;
					color: #00a2ca;
				}

				.qmbox td {
					font-family: 'Microsoft YaHei';
				}

				#mailContentContainer .txt {
					height: auto;
				}
			</style>
		</div>
	</div>
</div>
EOT;
}