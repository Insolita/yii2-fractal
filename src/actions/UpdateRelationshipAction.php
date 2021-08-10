<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\actions;

use insolita\fractal\RelationshipManager;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Sync relations for model
 * Handler for routes PATCH /resource/{id}/relationships/{relationName}
**/
class UpdateRelationshipAction extends JsonApiAction
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
     * If true -existed relations will be unlinked, but related models will be untouched
     * If false, current related models will be removed also
     */
    public $unlinkOnly = true;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init():void
    {
        parent::init();
        if (!$this->relationName) {
            throw new InvalidConfigException('Relation name parameter required!');
        }
    }

    /**
     * @param $id
     * @throws \Throwable
     * @throws \insolita\fractal\exceptions\ValidationException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
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
        $manager->patch($this->unlinkOnly);
        Yii::$app->response->setStatusCode(204);
    }
}
