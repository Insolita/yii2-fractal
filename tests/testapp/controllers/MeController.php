<?php

namespace app\controllers;

use insolita\fractal\JsonApiController;
use League\Fractal\Resource\Item;
use app\transformers\UserExtendTransformer;
use app\transformers\UserTransformer;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;

class MeController extends JsonApiController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                ['allow' => true, 'roles' => ['@']]
            ],
        ];
        $behaviors['authenticator']['authMethods']=[
            HttpBearerAuth::class
        ];
        return $behaviors;
    }

    public function actionInfo()
    {
        $model = Yii::$app->user->getIdentity();
        return new Item($model, new UserTransformer(), 'me');
    }

    public function actionDetails()
    {
        $model = Yii::$app->user->getIdentity();
        return new Item($model, new UserExtendTransformer(), 'me');
    }
}