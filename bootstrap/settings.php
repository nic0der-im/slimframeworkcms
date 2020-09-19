<?php
return [
    'settings' => [
        'production'=>true,
        'determineRouteBeforeAppMiddleware' => true,
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false,
        'db' => [
            'driver'=>'mysql',
            //'host'=>'192.168.0.115',
            'host'=>'localhost',
            'database'=>'digitalnoacms',
            'username'=>'root',
            'password'=>'',
            'charset'=>'utf8',
            'collation'=>'u
            tf8_unicode_ci',
            'prefix'=>'',
            ],
        'logger' => [
      'name' => 'ciro',
      'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
      'level' => \Monolog\Logger::DEBUG,
    ],
    ]   
];