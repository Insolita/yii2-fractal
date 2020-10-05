<?php

/**
 * @copyright Copyright (c) 2018 Carsten Brandt <mail@cebe.cc> and contributors
 * @license https://github.com/cebe/yii2-openapi/blob/master/LICENSE
 */

namespace insolita\fractal\actions;

use insolita\fractal\providers\CursorActiveDataProvider;
use insolita\fractal\providers\JsonApiActiveDataProvider;
use League\Fractal\Resource\Item;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQueryInterface;
use yii\helpers\Html;
use yii\web\NotFoundHttpException;

class ViewRelationshipAction extends JsonApiAction
{
    use HasResourceTransformer;

    /**
     * Relation name for model defined at modelClass property
     * @var string $relationName
     */
    public $relationName;


    /**
     * Provide supported dataProvider (JsonApiActiveDataProvider|CursorActiveDataProvider) with configuration
     * (It make sense only for hasMany relationships)
     * You can set 'pagination' => false for disable pagination
     * @var array
    */
    public $dataProvider = [
        'class' => JsonApiActiveDataProvider::class,
        'pagination'=>['defaultPageSize' => 30]
    ];

    public function init():void
    {
        parent::init();
        $this->initResourceTransformer();
        if (!$this->relationName) {
            throw new InvalidConfigException('Relation name parameter required!');
        }
    }

    /**
     * Display resource identifiers for a model relation
     * @param int|string $id
     * @return \League\Fractal\Resource\ResourceInterface|JsonApiActiveDataProvider
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\NotFoundHttpException
     */
    public function run($id)
    {
        $model = $this->findModel($id);

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        /**@var ActiveQueryInterface|\yii\db\ActiveQuery $relation */
        $relation = $model->getRelation($this->relationName, false);

        if (!$relation) {
            throw new NotFoundHttpException('Relation ' . Html::encode($this->relationName) . ' not found');
        }

        return $relation->multiple ? $this->resolveHasMany($relation) : $this->resolveHasOne($relation);
    }

    /**
     * @param \yii\db\ActiveQueryInterface|\yii\db\ActiveQuery $relation
     * @return \League\Fractal\Resource\Item
     */
    private function resolveHasOne(ActiveQueryInterface $relation):Item
    {
        $relatedModel = $relation->one();
        return new Item($relatedModel, $this->transformer, $this->resourceKey);
    }

    /**
     * @param \yii\db\ActiveQueryInterface|\yii\db\ActiveQuery $relation
     * @return \insolita\fractal\providers\CursorActiveDataProvider|\insolita\fractal\providers\JsonApiActiveDataProvider|object
     * @throws \yii\base\InvalidConfigException
     */
    private function resolveHasMany(ActiveQueryInterface $relation)
    {
        $dataProvider = Yii::createObject($this->dataProvider);
        if (!$dataProvider instanceof JsonApiActiveDataProvider && !$dataProvider instanceof CursorActiveDataProvider) {
            throw new InvalidConfigException('Invalid dataProvider configuration');
        }
        $dataProvider->query = $relation;
        $dataProvider->resourceKey = $this->resourceKey;
        $dataProvider->transformer = $this->transformer;
        $dataProvider->setSort(['params' => Yii::$app->getRequest()->getQueryParams()]);

        return $dataProvider;
    }
}
