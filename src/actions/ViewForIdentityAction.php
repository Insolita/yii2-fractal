<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\actions;

use League\Fractal\Resource\Item;
use Yii;
use yii\base\InvalidCallException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecordInterface;
use yii\web\NotFoundHttpException;

/**
 * Handler for view action with parent id equals current user identity
 * @example
 *   show user profile:
 *   Profile::find()->where(['user_id' => Yii::$app->user->id])->one();
 *   'profile' => [
 *         'class' => ViewForIdentityAction::class,
 *         'userIdAttribute' => 'user_id',
 *         'modelClass' => Profile::class,
 *         'transformer' => ProfileTransformer::class,
 *    ]
 *   show user's latest comment:
 *   Comment::find()->where(['author_id' => Yii::$app->user->id])->orderBy(['created_at' => SORT_DESC])->one();
 *   'my-last-comment' =>  [
 *        'class' => ViewForIdentityAction::class,
 *        'userIdAttribute' => 'author_id',
 *        'modelClass' => Comment::class,
 *        'transformer' => CommentTransformer::class,
 *        'queryWrapper' => function(ActiveQuery $query) {
 *              return $query->orderBy(['created_at' => SORT_DESC]);
 *        }
 *  ],
 **/
class ViewForIdentityAction extends JsonApiAction
{
    use HasResourceTransformer;

    /**
     * @var string
     * user foreign key attribute
     */
    public $userIdAttribute = 'user_id';

    /**
     * @var callable
     * @example
     * 'queryWrapper' => function(ActiveQuery $query, $userId, $id) {
     *     // Should return modified ActiveQuery
     *     return $query->orderBy('createdAt');
     * }
    */
    public $queryWrapper = null;
    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init():void
    {
        parent::init();
        $this->initResourceTransformer();
    }

    /**
     * Displays a model.
     * @param string|int|null $id the primary key of the model.
     * @return \League\Fractal\Resource\ResourceInterface
     * @throws \yii\web\NotFoundHttpException
     */
    public function run($id = null)
    {
        $model = $this->findModelForIdentity($id);


        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        return new Item($model, new $this->transformer, $this->resourceKey);
    }

    private function findModelForIdentity(?int $id)
    {
        $userId = Yii::$app->user->id;

        /* @var $modelClass ActiveRecordInterface */
        $modelClass = $this->modelClass;
        $condition = $id !== null ? $this->findModelCondition($id) : [];
        $condition[$this->modelTable().'.'.$this->userIdAttribute] = $userId;
        $query = $modelClass::find()->where($condition);
        if ($this->queryWrapper !== null) {
            $query = call_user_func($this->queryWrapper, $query, $userId, $id);
        }
        if (! $query instanceof ActiveQuery) {
            throw new InvalidCallException('queryWrapper callback should return \yii\db\ActiveQuery');
        }
        $model = $query->limit(1)->one();
        if (isset($model)) {
            return $model;
        }

        throw new NotFoundHttpException("Object not found: $id");
    }
}
