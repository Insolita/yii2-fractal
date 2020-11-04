<?php
use app\models\User;
use insolita\fractal\JsonApiBootstrap;
use insolita\fractal\JsonApiErrorHandler;
use insolita\fractal\JsonApiResponseFormatter;
use yii\caching\FileCache;
use yii\web\JsonParser;
use yii\web\UrlManager;

$dsn = getenv('IN_DOCKER')
    ? 'pgsql:host=pgsql;dbname=testdb'
    : 'pgsql:host=127.0.0.1;dbname=testdb';
return [
    'id' => 'fractal-test-app',
    'timeZone' => 'UTC',
    'homeUrl' => 'http://127.0.0.1:80',
    'basePath' => dirname(__DIR__),
    'runtimePath' => dirname(__DIR__) . '/runtime',
    'vendorPath' => dirname(__DIR__, 3) . '/vendor',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'bootstrap' => ['log'],
    //'bootstrap' => [JsonApiBootstrap::class, 'log'],
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => $dsn,
            'username' => 'dbuser',
            'password' => 'dbpass',
            'charset' => 'utf8',
        ],
        'user' => [
            'identityClass' => User::class,
            'enableAutoLogin' => true,
        ],
        'request' => [
            'class' => \yii\web\Request::class,
            'enableCsrfValidation' => false,
            'enableCookieValidation' => false,
            'enableCsrfCookie' => false,
            'parsers' => [
                'application/json' => JsonParser::class,
                'application/vnd.api+json' => JsonParser::class,
            ]
        ],
        'response' => [
            'formatters'=>[
                \yii\web\Response::FORMAT_JSON => [
                    'class'=>JsonApiResponseFormatter::class,
                    'prettyPrint'=>true
                ]
            ]
        ],
        'errorHandler'=>[
            'class'=>JsonApiErrorHandler::class,
            'validationErrorFormat' => JsonApiErrorHandler::ERROR_FORMAT_SPEC
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/error.log',
                    'logVars' => ['_GET', '_POST']
                ],
            ],
        ],
        'cache'=>[
            'class' => FileCache::class
        ],
        'urlManager' => [
            'class' => UrlManager::class,
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'hostinfo'=>'http://127.0.0.1:80',
            'rules' => [
                'GET,HEAD /defaults' => 'default/index',
                'POST,PUT,PATCH /media' => '/default/media',
                'GET,HEAD /me' => 'me/info',
                'GET,HEAD /me/details' => 'me/details',
                'GET,HEAD /default/<action:[\w-]+>' => 'default/<action>',

                'GET,HEAD /users/<id:\d+>/posts' => 'post/list-for-user',
                'GET,HEAD /categories/<id:\d+>/posts' => 'post/list-for-category',
                'GET,HEAD /categories/<id:\d+>/posts-join' => 'post/list-parent-with-join',
                'GET,HEAD /categories/<id:\d+>/relationships/posts' => 'category/related-posts',
                'POST /categories/<categoryId:\d+>/posts' => 'post/create-for-category',
                'GET /categories/<categoryId:\d+>/posts/<id:\d+>' => 'post/view-for-category',
                'PATCH /categories/<categoryId:\d+>/posts/<id:\d+>' => 'post/update-for-category',
                'DELETE /categories/<categoryId:\d+>/posts/<id:\d+>' => 'post/delete-for-category',
                'OPTIONS /posts' => 'post/options',
                'GET,HEAD /posts' => 'post/list',
                'GET,HEAD /posts-join' => 'post/list-with-join',
                'GET,HEAD /comments' => 'comment/list',
                'POST /posts' => 'post/create',
                'POST /posts2' => 'post/create2',
                'POST /comments' => 'comment/create',
                'GET,HEAD /posts/<id:\d+>' => 'post/view',
                'GET,HEAD /comments/<id:\d+>' => 'comment/view',
                'GET,HEAD /posts/<id:\d+>/relationships/author' => 'post/related-author',
                'GET,HEAD /posts/<id:\d+>/relationships/category' => 'post/related-category',
                'DELETE /posts/<id:\d+>/relationships/category' => 'post/delete-related-category',
                'GET,HEAD /posts/<id:\d+>/relationships/comments' => 'post/related-comments',
                'DELETE /posts/<id:\d+>/relationships/comments' => 'post/delete-related-comments',
                'POST /posts/<id:\d+>/relationships/comments' => 'post/create-related-comments',
                'PUT,PATCH /posts/<id:\d+>/relationships/comments' => 'post/update-related-comments',
                'PUT,PATCH /posts/<id:\d+>' => 'post/update',
                'PUT,PATCH /posts2/<id:\d+>' => 'post/update2',
                'DELETE /posts/<id:\d+>' => 'post/delete',

                'GET,HEAD /<controller:[\w]+>/<id:\d+>' => '<controller>/view',
                'PUT,PATCH /<controller:[\w]+>/<id:\d+>' => '<controller>/update',
                'DELETE /<controller:[\w]+>/<id:\d+>' => '<controller>/delete',
                'GET,HEAD /<controller:[\w]+>/<action:[\w-]+>' => '<controller>/<action>',
                'POST /<controller:[\w]+>' => '<controller>/create',
                'GET,HEAD /<controller:[\w]+>' => '<controller>/list',
                'OPTIONS /<controller:[\w]+>/<id:\d+>' => '<controller>/options',
                'OPTIONS /<controller:[\w]+>' => '<controller>/options',
            ],
        ],
    ],
];