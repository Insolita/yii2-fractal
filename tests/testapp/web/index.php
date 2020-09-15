<?php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require dirname(__DIR__, 3) . '/vendor/autoload.php';
require dirname(__DIR__, 3) . '/vendor/yiisoft/yii2/Yii.php';

$config = require dirname(__DIR__) . '/config/api.php';

(new yii\web\Application($config))->run();