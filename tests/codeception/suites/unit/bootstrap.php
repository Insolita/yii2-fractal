<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
require dirname(__DIR__) . '/../../../vendor/autoload.php';
require dirname(__DIR__) . '/../../../vendor/yiisoft/yii2/Yii.php';

Yii::setAlias('@tests', dirname(__DIR__).'/../../');
