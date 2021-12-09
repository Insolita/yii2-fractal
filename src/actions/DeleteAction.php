<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\actions;

use Closure;
use Yii;
use yii\base\Model;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Handler for routes DELETE /resource/{id}
 *  With defined parentIdParam and parentIdAttribute Handler for  DELETE /resource/{parentId}/relation/{id} modelClass
 * should be defined for related model for this case
 **/
class DeleteAction extends JsonApiAction
{
    use HasParentAttributes;

    /**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * @var callable|Closure Callback after save model with all relations
     * @example
     *   'afterDelete' => function ($model) {
     *           doSomething();
     * }
     */
    public $afterDelete = null;

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
        $model->setScenario($this->scenario);
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }
        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }
        if ($this->afterDelete !== null) {
            call_user_func($this->afterDelete, $model);
        }
        Yii::$app->getResponse()->setStatusCode(204);
    }
}
