<?php
// +----------------------------------------------------------------------
// | Captcha配置文件
// +----------------------------------------------------------------------

return [
    //验证码位数
    'length'       => 5,
    // 验证码字符集合
    'codeSet'      => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY',
    // 验证码过期时间
    'expire'       => 1800,
    // 是否使用中文验证码
    'useZh'        => false,
    // 是否使用算术验证码
    'math'         => false,
    // 是否使用背景图
    'useImgBg'     => false,
    //验证码字符大小
    'fontSize'     => 25,
    // 是否使用混淆曲线
    'useCurve'     => true,
    //是否添加杂点
    'useNoise'     => true,
    // 验证码字体 不设置则随机
    'fontttf'      => '',
    //背景颜色
    'bg'           => [243, 251, 254],
    // 验证码图片高度
    'imageH'       => 0,
    // 验证码图片宽度
    'imageW'       => 0,

    // 添加额外的验证码设置
    // verify => [
    //     'length'=>4,
    //    ...
    //],

    // 滑动验证码
    'font_file'    => '', //自定义字体包路径， 不填使用默认值
    //文字验证码
    'click_world'  => [
        'backgrounds' => []
    ],
    //滑动验证码
    'block_puzzle' => [
        'backgrounds' => [], //背景图片路径， 不填使用默认值
        'templates'   => [], //模板图
        'offset'      => 10, //容错偏移量
        'is_cache_pixel' => true, //是否开启缓存图片像素值，开启后能提升服务端响应性能（但要注意更换图片时，需要清除缓存）
    ],
    //水印
    'watermark'    => [
        'fontsize' => 12,
        'color'    => '#ffffff',
        'text'     => '共享旅途'
    ],
    'cache'        => [
        'constructor' => [\think\facade\Cache::class, 'instance']
    ]

];
