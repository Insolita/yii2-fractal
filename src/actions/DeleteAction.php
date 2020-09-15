<?php

namespace insolita\fractal\actions;

use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;

class DeleteAction extends JsonApiAction
{
    use HasParentAttributes;

    public function init():void
    {
        parent::init();
        $this->validateParentAttributes();
    }

    /**
     * @param int|string $id
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\web\ServerErrorHttpException
     */
    public function run($id):void
    {
        if ($this->hasResourceRelationships()) {
            /** @see https://jsonapi.org/format/#crud-updating-resource-relationships * */
            throw new ForbiddenHttpException('Update with relationships not supported yet');
        }
        $model = $this->isParentRestrictionRequired() ? $this->findModelForParent($id) : $this->findModel($id);
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }
        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }

        Yii::$app->getResponse()->setStatusCode(204);
    }
}
