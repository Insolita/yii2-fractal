<?php

namespace insolita\fractal\actions;

use insolita\fractal\providers\JsonApiActiveDataProvider;
use League\Fractal\Resource\Item;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecordInterface;
use yii\helpers\Html;
use yii\web\NotFoundHttpException;

class ListRelationshipAction extends JsonApiAction
{
    use HasRelationResourceMap;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init():void
    {
        parent::init();
        if (empty($this->relationMap)) {
            throw new InvalidConfigException('Property relationMap is required');
        }
    }

    /**
     * Displays a model relation
     * @param int|string $id
     * @param string     $relationName
     * @return \League\Fractal\Resource\ResourceInterface|JsonApiActiveDataProvider
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\NotFoundHttpException
     */
    public function run($id, $relationName)
    {
        $model = $this->findModel($id);

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        $this->resolveResource($relationName);

        /**@var ActiveQueryInterface|\yii\db\ActiveQuery $relation*/
        $relation = $model->getRelation($relationName, false);

        if (!$relation) {
            throw new NotFoundHttpException('Relation ' . Html::encode($relationName) . ' not found');
        }

        return $relation->multiple
            ?  $this->resolveHasMany($model, $relationName, $relation)
            :  $this->resolveHasOne($model, $relationName, $relation);
    }

    /**
     * @param \yii\db\ActiveRecordInterface                    $model
     * @param string                                           $relationName
     * @param \yii\db\ActiveQueryInterface|\yii\db\ActiveQuery $relation
     * @return object|JsonApiActiveDataProvider
     * @throws \yii\base\InvalidConfigException
     */
    private function resolveHasMany(ActiveRecordInterface $model, string $relationName, ActiveQueryInterface $relation)
    {
        $method = Yii::$app->request->method;
        if ($this->checkAccessRelation) {
            call_user_func($this->checkAccessRelation, $this->id, $model, $relationName, $method, null);
        }
        $requestParams = Yii::$app->getRequest()->getQueryParams();
        $defaultIncludes = $this->transformer->getDefaultIncludes();
        $allowedIncludes = $this->transformer->getAvailableIncludes();
        $requestedIncludes = $this->controller->manager->getRequestedIncludes();
        $include = array_merge($defaultIncludes, array_intersect($allowedIncludes, $requestedIncludes));
        if (!empty($include)) {
            $relation->with($include);
        }
        return Yii::createObject([
            'class' => JsonApiActiveDataProvider::class,
            'query' => $relation,
            'resourceKey' => $this->resourceKey,
            'transformer' => $this->transformer,
            'sort' => [
                'params' => $requestParams,
            ],
        ]);
    }

    /**
     * @param \yii\db\ActiveRecordInterface|\yii\db\ActiveRecord $model
     * @param string                                             $relationName
     * @param \yii\db\ActiveQueryInterface|\yii\db\ActiveQuery   $relation
     * @return \League\Fractal\Resource\Item
     */
    private function resolveHasOne(
        ActiveRecordInterface $model,
        string $relationName,
        ActiveQueryInterface $relation
    ):Item {
        $method = Yii::$app->request->method;
        $relatedModel = $relation->one();
        if ($this->checkAccessRelation) {
            call_user_func($this->checkAccessRelation, $this->id, $model, $relationName, $method, $relatedModel);
        }
        return new Item($relatedModel, $this->transformer, $this->resourceKey);
    }
}
