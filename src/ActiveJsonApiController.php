<?php

namespace insolita\fractal;

use insolita\fractal\actions\CreateAction;
use insolita\fractal\actions\DeleteAction;
use insolita\fractal\actions\HasResourceTransformer;
use insolita\fractal\actions\ListAction;
use insolita\fractal\actions\UpdateAction;
use insolita\fractal\actions\ViewAction;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\web\ForbiddenHttpException;

class ActiveJsonApiController extends JsonApiController
{
    use HasResourceTransformer;

    public $modelClass;

    /**
     * @var string the scenario used for updating a model.
     * @see \yii\base\Model::scenarios()
     */
    public $updateScenario = Model::SCENARIO_DEFAULT;
    /**
     * @var string the scenario used for creating a model.
     * @see \yii\base\Model::scenarios()
     */
    public $createScenario = Model::SCENARIO_DEFAULT;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if ($this->modelClass === null) {
            throw new InvalidConfigException('The "modelClass" property must be set.');
        }
        if ($this->resourceKey === null) {
            throw new InvalidConfigException('The "resourceKey" property must be set.');
        }

        if ($this->transformer === null) {
            throw new InvalidConfigException('The "transformer" property must be set.');
        }
    }

    public function actions()
    {
        return [
            'list' => [
                'class' => ListAction::class,
                'modelClass' => $this->modelClass,
                'resourceKey'=> $this->resourceKey,
                'transformer'=> $this->transformer,
                'checkAccess' => [$this, 'checkAccess'],
            ],
            'view' => [
                'class' => ViewAction::class,
                'modelClass' => $this->modelClass,
                'resourceKey'=> $this->resourceKey,
                'transformer'=> $this->transformer,
                'checkAccess' => [$this, 'checkAccess'],
            ],
            'create' => [
                'class' => CreateAction::class,
                'modelClass' => $this->modelClass,
                'resourceKey'=> $this->resourceKey,
                'transformer'=> $this->transformer,
                'checkAccess' => [$this, 'checkAccess'],
                'scenario' => $this->createScenario,
            ],
            'update' => [
                'class' => UpdateAction::class,
                'modelClass' => $this->modelClass,
                'resourceKey'=> $this->resourceKey,
                'transformer'=> $this->transformer,
                'checkAccess' => [$this, 'checkAccess'],
                'scenario' => $this->updateScenario,
            ],
            'delete' => [
                'class' => DeleteAction::class,
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
            ],
            'options' => [
                'class' => 'yii\rest\OptionsAction',
            ],
        ];
    }

    /**
     * Checks the privilege of the current user.
     *
     * This method should be overridden to check whether the current user has the privilege
     * to run the specified action against the specified data model.
     * If the user does not have access, a [[ForbiddenHttpException]] should be thrown.
     *
     * @param string $action the ID of the action to be executed
     * @param object $model the model to be accessed. If null, it means no specific model is being accessed.
     * @param array $params additional parameters
     * @throws ForbiddenHttpException if the user does not have access
     */
    public function checkAccess($action, $model = null, $params = [])
    {
    }
}
