<?php

namespace app\controllers;

use app\models\Post;
use app\transformers\CategoryTransformer;
use app\transformers\PostTransformer;
use insolita\fractal\actions\CreateAction;
use insolita\fractal\actions\DeleteAction;
use insolita\fractal\actions\ListAction;
use insolita\fractal\actions\ListRelationshipAction;
use insolita\fractal\actions\UpdateAction;
use insolita\fractal\actions\ViewAction;
use insolita\fractal\ActiveJsonApiController;
use yii\base\DynamicModel;
use yii\data\ActiveDataFilter;
use yii\filters\AccessControl;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\web\ForbiddenHttpException;

class PostController extends ActiveJsonApiController
{
    public $modelClass = Post::class;

    public $resourceKey = 'posts';

    public $transformer = PostTransformer::class;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']=[
            'class' => CompositeAuth::class,
            'only'=>['create-for-category', 'update-for-category'],
            'authMethods'=>[
                HttpBearerAuth::class,
            ]
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        $actions['list']['dataFilter'] = [
            'class' => ActiveDataFilter::class,
            'searchModel' => function() {
                return (new DynamicModel(['name' => null, 'category_id' => null, 'author_id' => null]))
                    ->addRule('category_id', 'integer')
                    ->addRule('author_id', 'integer')
                    ->addRule('name', 'trim')
                    ->addRule('name', 'string');
            },
        ];

        $actions['list-for-user'] = [
            'class' => ListAction::class,
            'modelClass' => $this->modelClass,
            'resourceKey' => $this->resourceKey,
            'transformer' => $this->transformer,
            'checkAccess' => [$this, 'checkAccess'],
            'parentIdParam' => 'id',
            'parentIdAttribute' => 'author_id',
        ];

        $actions['list-for-category'] = [
            'class' => ListAction::class,
            'modelClass' => $this->modelClass,
            'resourceKey' => $this->resourceKey,
            'transformer' => $this->transformer,
            'checkAccess' => [$this, 'checkAccess'],
            'parentIdParam' => 'id',
            'parentIdAttribute' => 'category_id',
        ];

        $actions['view-for-category'] = [
            'class' => ViewAction::class,
            'modelClass' => $this->modelClass,
            'resourceKey' => $this->resourceKey,
            'transformer' => $this->transformer,
            'checkAccess' => [$this, 'checkAccess'],
            'parentIdParam' => 'categoryId',
            'parentIdAttribute' => 'category_id',
        ];
        $actions['create-for-category'] = [
            'class' => CreateAction::class,
            'modelClass' => $this->modelClass,
            'resourceKey' => $this->resourceKey,
            'transformer' => $this->transformer,
            'checkAccess' => [$this, 'checkAccess'],
            'parentIdParam' => 'categoryId',
            'parentIdAttribute' => 'category_id',
        ];
        $actions['update-for-category'] = [
            'class' => UpdateAction::class,
            'modelClass' => $this->modelClass,
            'resourceKey' => $this->resourceKey,
            'transformer' => $this->transformer,
            'checkAccess' => [$this, 'checkAccess'],
            'parentIdParam' => 'categoryId',
            'parentIdAttribute' => 'category_id',
        ];
        $actions['delete-for-category'] = [
            'class' => DeleteAction::class,
            'modelClass' => $this->modelClass,
            'checkAccess' => [$this, 'checkAccess'],
            'parentIdParam' => 'categoryId',
            'parentIdAttribute' => 'category_id',
            'findModelFor'=>function($id, $parentId) {
               return Post::find()->where(['category_id'=>$parentId, 'id' => $id])->one();
            }
        ];
        $actions['relationships'] = [
            'class' => ListRelationshipAction::class,
            'modelClass' => $this->modelClass,
            'relationMap' => [
                'author' => ['authors' => null],
                'category' => ['categories' => CategoryTransformer::class],
                'comments' => ['comments' => null],
            ],
        ];
        return $actions;
    }

    /**
     * Checks the privilege of the current user.
     * This method should be overridden to check whether the current user has the privilege
     * to run the specified action against the specified data model.
     * If the user does not have access, a [[ForbiddenHttpException]] should be thrown.
     * @param string $action the ID of the action to be executed
     * @param object $model the model to be accessed. If null, it means no specific model is being accessed.
     * @param array  $params additional parameters
     * @throws ForbiddenHttpException if the user does not have access
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        if ($model && $model->id > 7 && $model->id < 10) {
            throw new ForbiddenHttpException("You haven't access permissions to this data", 403);
        }
    }
}