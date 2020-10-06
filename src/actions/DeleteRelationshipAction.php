<?php

/**
 * @copyright Copyright (c) 2018 Carsten Brandt <mail@cebe.cc> and contributors
 * @license https://github.com/cebe/yii2-openapi/blob/master/LICENSE
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
            call_user_func($this->checkAccess, $this->id, $model);
        }
        $manager = new RelationshipManager($model, $this->relationName, $this->getResourceData(), $this->pkType);
        $manager->delete($this->unlinkOnly);
        Yii::$app->response->setStatusCode(204);
    }
}
