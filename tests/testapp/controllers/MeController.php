<?php

namespace app\controllers;

use app\models\Comment;
use app\models\Post;
use app\transformers\CommentTransformer;
use app\transformers\PostShortTransformer;
use insolita\fractal\actions\ListForIdentityAction;
use insolita\fractal\actions\ViewForIdentityAction;
use insolita\fractal\JsonApiController;
use League\Fractal\Resource\Item;
use app\transformers\UserExtendTransformer;
use app\transformers\UserTransformer;
use Yii;
use yii\db\ActiveQuery;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use const SORT_DESC;

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

    public function actions()
    {
        return [
            'my-posts' => [
                'class' => ListForIdentityAction::class,
                'userIdAttribute' => 'author_id',
                'modelClass' => Post::class,
                'transformer' => PostShortTransformer::class
            ],
            'my-last-comment' =>  [
                'class' => ViewForIdentityAction::class,
                'userIdAttribute' => 'user_id',
                'modelClass' => Comment::class,
                'transformer' => CommentTransformer::class,
                'queryWrapper' => function(ActiveQuery $query) {
                    return $query->orderBy(['created_at' => SORT_DESC]);
                }
            ],
            'my-comment' => [
                'class' => ViewForIdentityAction::class,
                'userIdAttribute' => 'user_id',
                'modelClass' => Comment::class,
                'transformer' => CommentTransformer::class,
            ]
        ];
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