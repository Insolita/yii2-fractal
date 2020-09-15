<?php

namespace insolita\fractal\actions;

use insolita\fractal\exceptions\ValidationException;
use League\Fractal\Resource\Item;
use Yii;
use yii\base\Model;
use yii\helpers\Url;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;

class CreateAction extends JsonApiAction
{
    use HasResourceTransformer;
    use HasParentAttributes;
    /**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * @var string the name of route or action for view created model
     */
    public $viewRoute = 'view';

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
     * Creates a new model.
     * @return \League\Fractal\Resource\ResourceInterface
     * @throws \insolita\fractal\exceptions\ValidationException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\ServerErrorHttpException if there is any error when creating the model
     */
    public function run()
    {
        if ($this->hasResourceRelationships()) {
            /** @see https://jsonapi.org/format/#crud-updating-resource-relationships **/
            throw new ForbiddenHttpException('Creating with relationships not supported yet');
        }
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }

        /* @var $model \yii\db\ActiveRecord */
        $model = new $this->modelClass([
            'scenario' => $this->scenario,
        ]);
        $model->load($this->getResourceAttributes(), '');
        if ($this->isParentRestrictionRequired()) {
            $parentId = Yii::$app->request->getQueryParam($this->parentIdParam, null);
            if ($parentId) {
                $model->setAttribute($this->parentIdAttribute, $parentId);
            }
        }
        if ($model->save()) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
            $id = implode(',', array_values($model->getPrimaryKey(true)));
            $response->getHeaders()->set('Location', Url::to([$this->viewRoute, 'id' => $id], true));
        } elseif ($model->hasErrors()) {
            throw new ValidationException($model->getErrors());
        } else {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }

        return new Item($model, new $this->transformer, $this->resourceKey);
    }
}
