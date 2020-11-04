<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace app\controllers;

use app\models\Post;
use app\transformers\PostTransformer;
use app\transformers\PostWithRelationsTransformer;
use insolita\fractal\actions\CreateAction;
use insolita\fractal\actions\CreateRelationshipAction;
use insolita\fractal\actions\DeleteAction;
use insolita\fractal\actions\DeleteRelationshipAction;
use insolita\fractal\actions\ListAction;
use insolita\fractal\actions\UpdateAction;
use insolita\fractal\actions\UpdateRelationshipAction;
use insolita\fractal\actions\ViewAction;
use insolita\fractal\actions\ViewRelationshipAction;
use insolita\fractal\ActiveJsonApiController;
use insolita\fractal\IdOnlyTransformer;
use insolita\fractal\providers\CursorActiveDataProvider;
use insolita\fractal\providers\JsonApiActiveDataProvider;
use yii\base\DynamicModel;
use yii\data\ActiveDataFilter;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\web\ForbiddenHttpException;
use const SORT_ASC;
use const SORT_DESC;

class PostController extends ActiveJsonApiController
{
    public $modelClass = Post::class;

    public $resourceKey = 'posts';

    public $transformer = PostTransformer::class;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::class,
            'only' => ['create-for-category', 'update-for-category'],
            'authMethods' => [
                HttpBearerAuth::class,
            ],
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        $actions['list']['dataFilter'] = [
            'class' => ActiveDataFilter::class,
            'searchModel' => function () {
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
            'findModelFor' => function ($id, $parentId) {
                return Post::find()->where(['category_id' => $parentId, 'id' => $id])->one();
            },
        ];
        $actions['related-category'] = [
            'class' => ViewRelationshipAction::class,
            'modelClass' => $this->modelClass,
            'relationName' => 'category',
            'resourceKey' => 'categories',
        ];
        $actions['delete-related-category'] = [
            'class' => DeleteRelationshipAction::class,
            'modelClass' => $this->modelClass,
            'relationName' => 'category',
        ];
        $actions['related-author'] = [
            'class' => ViewRelationshipAction::class,
            'modelClass' => $this->modelClass,
            'relationName' => 'author',
            'resourceKey' => 'users',
        ];
        $actions['related-comments'] = [
            'class' => ViewRelationshipAction::class,
            'modelClass' => $this->modelClass,
            'relationName' => 'comments',
            'resourceKey' => 'comments',
            'transformer' => IdOnlyTransformer::class,
            'dataProvider' => ['class' => CursorActiveDataProvider::class],
        ];
        $actions['delete-related-comments'] = [
            'class' => DeleteRelationshipAction::class,
            'modelClass' => $this->modelClass,
            'relationName' => 'comments',
        ];
        $actions['update-related-comments'] = [
            'class' => UpdateRelationshipAction::class,
            'modelClass' => $this->modelClass,
            'relationName' => 'comments',
        ];
        $actions['create-related-comments'] = [
            'class' => CreateRelationshipAction::class,
            'modelClass' => $this->modelClass,
            'relationName' => 'comments',
            'resourceKey' => 'comments',
            'transformer' => IdOnlyTransformer::class,
        ];

        $actions['create2'] = [
            'class' => CreateAction::class,
            'modelClass' => $this->modelClass,
            'resourceKey' => $this->resourceKey,
            'transformer' => $this->transformer,
            'checkAccess' => [$this, 'checkAccess'],
            'scenario' => $this->createScenario,
            'allowedRelations' => [
                'comments' => ['idType' => 'integer'],
            ],
        ];
        $actions['update2'] = [
            'class' => UpdateAction::class,
            'modelClass' => $this->modelClass,
            'resourceKey' => $this->resourceKey,
            'transformer' => $this->transformer,
            'checkAccess' => [$this, 'checkAccess'],
            'scenario' => $this->updateScenario,
            'allowedRelations' => [
                'comments' => ['idType' => 'integer', 'unlinkOnly' => true],
            ],
        ];
        $actions['list-with-join'] = [
            'class' => ListAction::class,
            'modelClass' => $this->modelClass,
            'resourceKey' => $this->resourceKey,
            'transformer' => $this->transformer,
            'checkAccess' => [$this, 'checkAccess'],
            'prepareDataProvider' => function ($action, JsonApiActiveDataProvider $dp) {
                $dp->query->joinWith(['category category'])
                          ->select(['posts.*', 'category.id as cid', 'category.name as cat']);
                $dp->sort->addAttributes([
                    'category.name',
                    'category.id'=>['asc' =>['category.id' => SORT_ASC], 'desc' => ['category.id' => SORT_DESC]]
                ]);
                return $dp;
            },
        ];

        $actions['list-parent-with-join'] = [
            'class' => ListAction::class,
            'modelClass' => $this->modelClass,
            'resourceKey' => $this->resourceKey,
            'transformer' => $this->transformer,
            'checkAccess' => [$this, 'checkAccess'],
            'parentIdParam' => 'id',
            'parentIdAttribute' => 'category_id',
            'prepareDataProvider' => function ($action, JsonApiActiveDataProvider $dp) {
                $dp->query->joinWith(['category category', 'comments'])
                          ->addSelect(['posts.*', 'category.*', 'comments.*']);
                return $dp;
            },
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
