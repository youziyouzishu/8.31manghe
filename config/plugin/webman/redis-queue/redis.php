<?php
return [
    'default' => [
        'host' => 'redis://119.45.162.109:6379',
        'options' => [
            'auth' => 's5123431212',       // 密码，字符串类型，可选参数
            'db' => 6,            // 数据库
            'prefix' => '',       // key 前缀
            'max_attempts'  => 5, // 消费失败后，重试次数
            'retry_seconds' => 5, // 重试间隔，单位秒
        ]
    ],
];
