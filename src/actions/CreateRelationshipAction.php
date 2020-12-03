<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\actions;

use insolita\fractal\providers\CursorActiveDataProvider;
use insolita\fractal\providers\JsonApiActiveDataProvider;
use insolita\fractal\RelationshipManager;
use League\Fractal\Resource\Item;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQueryInterface;

/**
 * Link model relations
 * Handler for routes POST /resource/{id}/relationships/{relationName}
**/
class CreateRelationshipAction extends JsonApiAction
{
    use HasResourceTransformer;
    /**
     * Used to validate ids from request; Set string if some
     * @var string
     */
    public $pkType = 'integer';
    /**
     * Relation name for model defined at modelClass property
     * @var string $relationName
     */
    public $relationName;

    /**
     * Custom callback for validate ids. It accept founded model and array of ids as parameter and must throw exception,
     * when data not valid
     * @example
     * 'idValidateCallback' => function($model, array $ids) {
     *       foreach($ids as $id) {
     *          if($id < 5 or $id > 15) {
     *              throw new ValidationException(422, 'Wrong ids');
     *          }
     *     }
     * }
     * @var callable
    */
    public $idValidateCallback;

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
    }

    /**
     * @param $id
     * @return \insolita\fractal\providers\CursorActiveDataProvider|\insolita\fractal\providers\JsonApiActiveDataProvider|\League\Fractal\Resource\Item|object
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     * @throws \yii\web\NotFoundHttpException
     */
    public function run($id)
    {
        $model = $this->findModel($id);
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }
        $manager = new RelationshipManager($model, $this->relationName, $this->getResourceData(), $this->pkType);
        if ($this->idValidateCallback !== null) {
            $manager->setIdValidateCallback($this->idValidateCallback);
        }
        $relation = $manager->attach();
        return $relation->multiple? $this->showHasOne($relation) : $this->showHasMany($relation);
    }
    /**
     * @param \yii\db\ActiveQueryInterface|\yii\db\ActiveQuery $relation
     * @return \League\Fractal\Resource\Item
     */
    private function showHasOne(ActiveQueryInterface $relation):Item
    {
        $relatedModel = $relation->one();
        return new Item($relatedModel, $this->transformer, $this->resourceKey);
    }

    /**
     * @param \yii\db\ActiveQueryInterface|\yii\db\ActiveQuery $relation
     * @return \insolita\fractal\providers\CursorActiveDataProvider|\insolita\fractal\providers\JsonApiActiveDataProvider|object
     * @throws \yii\base\InvalidConfigException
     */
    private function showHasMany(ActiveQueryInterface $relation)
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
