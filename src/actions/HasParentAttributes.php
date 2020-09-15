<?php

namespace insolita\fractal\actions;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecordInterface;
use yii\web\NotFoundHttpException;

/**
 * @mixin \insolita\fractal\actions\JsonApiAction
*/
trait HasParentAttributes
{
    /**
     * attribute name for parent resource id
     * Used with $parentIdParam for restrict results by parent resource
     * For example for requests GET|POST /posts/{id}/comments  in resource comments
     * we can configure ListAction or CreateAction like
     * @example
     *     'list-for-post'=>[
     *         'class' => ListAction::class,
     *         'modelClass' => Comment::class,
     *         'parentIdParam' => 'id',
     *         'parentIdAttribute' => 'post_id'
     *     ]
     *     This will generate query Comment::find()->where(['post_id' => {id}])
     * For requests like GET|DELETE|UPDATE /category/{categoryId}/posts/{id} in posts resource
     * we can configure actions
     *      'view-for-category'=>[
     *         'class' => ViewAction::class,
     *         'modelClass' => Post::class,
     *         'parentIdParam' => 'categoryId',
     *         'parentIdAttribute' => 'category_id'
     *     ]
     * This will generate query for find model Post::findOne(['id' => {id}, 'category_id' => {categoryId}])
     * @see $parentIdParam
     * @var string
     */
    public $parentIdAttribute;

    /**
     * The query parameter name for parent resource id
     * @var string
     * @see $parentIdAttribute
     */
    public $parentIdParam;

    /**
     * @var callable a PHP callable that will be called to return the model corresponding
     * to the specified primary key value with restriction by parent resource.
     * If not set, [[findModelFor()]] will be used instead.
     * The signature of the callable should be:
     * ```php
     * function ($id, $parentId, $action) {
     *     // $id is the primary key value. If composite primary key, the key values
     *     // will be separated by comma.
     *     // $action is the action object currently running
     * }
     * ```
     * The callable should return the model found, or throw an exception if not found.
     */
    public $findModelFor;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    protected function validateParentAttributes():void
    {
        if (
            ($this->parentIdAttribute !== null && $this->parentIdParam === null)
            || ($this->parentIdAttribute === null && $this->parentIdParam !== null)
        ) {
            throw new InvalidConfigException('Both parameters: parentIdAttribute and parentIdParam must be defined, or both must be null');
        }
    }

    protected function isParentRestrictionRequired():bool
    {
        return $this->parentIdParam !== null && $this->parentIdAttribute !== null;
    }


    /**
     * Find model with restriction by parent
     * @param int|string|null $id
     * @return \yii\db\ActiveRecord|\yii\db\ActiveRecordInterface
     * @throws \yii\web\NotFoundHttpException
     */
    protected function findModelForParent($id)
    {
        $parentId = Yii::$app->request->getQueryParam($this->parentIdParam, null);
        if ($this->findModelFor !== null) {
            $model = call_user_func($this->findModelFor, $id, $parentId, $this);
            if(!$model) {
                throw new NotFoundHttpException("Object not found: $id");
            }
            return $model;
        }

        /* @var $modelClass ActiveRecordInterface */
        $modelClass = $this->modelClass;

        if ($this->parentIdParam === 'id') {
            $id = $id ?? Yii::$app->request->getQueryParam('id', null);
            $model = $modelClass::findOne([$this->parentIdAttribute => $id]);
        } else {
            $condition = $this->findModelCondition($id);
            $condition[$this->parentIdAttribute] = $parentId;
            $model = $modelClass::findOne($condition);
        }

        if (isset($model)) {
            return $model;
        }

        throw new NotFoundHttpException("Object not found: $id");
    }
}
