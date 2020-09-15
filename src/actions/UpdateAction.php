<?php

namespace insolita\fractal\actions;

use insolita\fractal\exceptions\ValidationException;
use League\Fractal\Resource\Item;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;

class UpdateAction extends JsonApiAction
{
    use HasResourceTransformer;
    use HasParentAttributes;
    /**
     * @var string the scenario to be assigned to the model before it is validated and updated.
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init():void
    {
        parent::init();
        $this->initResourceTransformer();
        $this->validateParentAttributes();
    }

    /**
     * @param int|string $id
     * @return \League\Fractal\Resource\Item
     * @throws \insolita\fractal\exceptions\ValidationException
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\web\ServerErrorHttpException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function run($id):Item
    {
        if ($this->hasResourceRelationships()) {
            /** @see https://jsonapi.org/format/#crud-updating-resource-relationships **/
            throw new ForbiddenHttpException('Update with relationships not supported yet');
        }
        /* @var $model ActiveRecord */
        $model = $this->isParentRestrictionRequired() ? $this->findModelForParent($id) : $this->findModel($id);

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        $model->scenario = $this->scenario;
        $model->load($this->getResourceAttributes(), '');
        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }
        if ($model->hasErrors()) {
            throw new ValidationException($model->getErrors());
        }

        return new Item($model, new $this->transformer, $this->resourceKey);
    }
}
