<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\actions;

use insolita\fractal\RelationshipManager;
use Yii;

/**
 * Unlink model relations
 * Handler for routes DELETE /resource/{id}/relationships/{relationName}
**/
class DeleteRelationshipAction extends JsonApiAction
{
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
     * If true - relation will be unlinked, but related models will be untouched
     * If false, related models will be removed also
     */
    public $unlinkOnly = true;

    /**
     * @param $id
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\Exception
     * @throws \yii\web\NotFoundHttpException
     */
    public function run($id):void
    {
        $model = $this->findModel($id);
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this, $model);
        }
        $manager = new RelationshipManager($model, $this->relationName, $this->getResourceData(), $this->pkType);
        if ($this->idValidateCallback !== null) {
            $manager->setIdValidateCallback($this->idValidateCallback);
        }
        $manager->delete($this->unlinkOnly);
        Yii::$app->response->setStatusCode(204);
    }
}
