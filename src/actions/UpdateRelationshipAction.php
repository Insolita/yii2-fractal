<?php

/**
 * @copyright Copyright (c) 2018 Carsten Brandt <mail@cebe.cc> and contributors
 * @license https://github.com/cebe/yii2-openapi/blob/master/LICENSE
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
            call_user_func($this->checkAccess, $this->id, $model);
        }
        $manager = new RelationshipManager($model, $this->relationName, $this->getResourceData(), $this->pkType);
        $manager->patch($this->unlinkOnly);
        Yii::$app->response->setStatusCode(204);
    }
}
