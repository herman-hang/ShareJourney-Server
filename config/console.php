<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'user:seed' => 'app\command\UserSeed',
        'owner:seed' => 'app\command\OwnerSeed',
        'withdraw:seed' => 'app\command\OwnerWithdraw'
    ],
];
