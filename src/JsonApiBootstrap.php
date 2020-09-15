<?php

namespace insolita\fractal;

use yii\base\BootstrapInterface;
use yii\web\JsonParser;
use yii\web\Request;
use yii\web\Response;
use const YII_ENV_PROD;

class JsonApiBootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $app->set('request', [
            'class' => Request::class,
            'enableCsrfValidation' => false,
            'enableCookieValidation' => false,
            'enableCsrfCookie' => false,
            'parsers' => [
                'application/vnd.api+json' => JsonParser::class,
            ]
        ]);

        $app->set('response', [
            'class' => Response::class,
            'formatters'=>[
                \yii\web\Response::FORMAT_JSON => [
                    'class'=>JsonApiResponseFormatter::class,
                    'prettyPrint'=>!YII_ENV_PROD
                ]
            ]
        ]);

        $app->set('errorHandler', [
            'class'=>JsonApiErrorHandler::class,
            'validationErrorFormat'=> JsonApiErrorHandler::ERROR_FORMAT_SPEC
        ]);
        $app->errorHandler->register();
    }
}
