<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\actions;

use insolita\fractal\pagination\JsonApiPaginator;
use insolita\fractal\providers\CursorActiveDataProvider;
use insolita\fractal\providers\JsonApiActiveDataProvider;
use League\Fractal\Resource\Item;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQueryInterface;
use yii\helpers\Html;
use yii\web\NotFoundHttpException;

/**
 * Handler for routes GET /resource/{id}/relationships/{relationName}
**/
class ViewRelationshipAction extends JsonApiAction
{
    use HasResourceTransformer;

    /**
     * Relation name for model defined at modelClass property
     * @var string $relationName
     */
    public $relationName;

    /**
     * Modify dataProvider for 1-n relations
     * @var callable
     * @example
     * 'prepareDataProvider' => function(ViewRelationshipAction $action, DataProviderInterface  $dataProvider) {
     *      Modify $dataProvider
     *      or return completely configured dataProvider (JsonApiActiveDataProvider|CursorActiveDataProvider)
     * }
     */
    public $prepareDataProvider;


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
        if ($relation->multiple) {
            $dataProvider = $this->resolveHasMany($relation);
            if (Yii::$app->request->isHead && $dataProvider->pagination !== false) {
                $dataProvider->fillHeaders(Yii::$app->response->headers);
            }
            return $dataProvider;
        }
        return $this->resolveHasOne($relation);
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

        if ($this->prepareDataProvider !== null) {
            return call_user_func($this->prepareDataProvider, $this, $dataProvider);
        }

        return $dataProvider;
    }
}
