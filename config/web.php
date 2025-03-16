<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'asd',
            'baseUrl' => "",
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'multipart/form-data' => 'yii\web\MultipartFormDataParser'
            ]
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
            'class' => 'yii\web\Response',
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->statusCode == 404) {
                    $response->data = [
                        'message' => 'Not Found',
                        'code' => 404
                    ];
                } else {
                    if ($response->statusCode == 403) {
                        $response->data = [
                            'message' => 'Forbidden for you',
                        ];
                    }
                    if ($response->statusCode == 401) {
                        $response->data = [
                            'message' => 'Login failed',
                        ];
                        $response->statusCode == 403;
                    }
                }
            },
            // ...
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'enableSession' => false,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                ['class' => 'yii\rest\UrlRule', 'controller' => 'user'],
                [

                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'book',
                    'pluralize' => true,
                    'prefix' => 'api',
                    'extraPatterns' => [
                        'POST upload' => 'new',
                        'OPTIONS upload' => 'options',
                        'GET' => 'get-books',
                        'GET progress' => 'get-reading-books',
                        'DELETE <id>' => 'delete-book',
                        'PATCH <id>' => 'edit-book',
                        'GET <id>' => 'get-book',
                        'POST <id>/progress' => 'save-progress',
                        'OPTIONS' => 'options',
                    ]
                ],
                'POST api/registration' => 'user/register',
                'OPTIONS api/registration' => 'user/options',

                'POST api/login' => 'user/login',
                'OPTIONS api/login' => 'user/options',
                'POST api/logout' => 'user/logout',
                'OPTIONS api/logout' => 'user/options',
            ],
        ]
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];
}

return $config;
