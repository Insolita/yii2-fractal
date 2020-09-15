<?php

namespace insolita\fractal\actions;

use insolita\fractal\DefaultTransformer;
use League\Fractal\TransformerAbstract;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use function is_string;
use function property_exists;

/**
 * Inject transformer to components
 * @see \League\Fractal\TransformerAbstract
 * @see https://fractal.thephpleague.com/transformers/
 */
trait HasResourceTransformer
{
    /**
     * @var \League\Fractal\TransformerAbstract
     **/
    public $transformer;

    /**
     * Required for valid resource links, use current model name by default
     * @var string
     */
    public $resourceKey;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    protected function initResourceTransformer():void
    {
        if (!$this->transformer) {
            $this->transformer = new DefaultTransformer();
        }
        if (is_string($this->transformer)) {
            $this->transformer = Yii::createObject($this->transformer);
        }
        if (!$this->transformer instanceof TransformerAbstract) {
            throw new InvalidConfigException('Transformer must be an instance of  \League\Fractal\TransformerAbstract');
        }
        if (!$this->resourceKey || !is_string($this->resourceKey)) {
            if (property_exists($this, 'modelClass')) {
                $this->resourceKey = Inflector::pluralize(
                    Inflector::camel2id(
                        StringHelper::basename($this->modelClass)
                    )
                );
            } else {
                $controller = property_exists($this, 'controller')? $this->controller : Yii::$app->controller;
                $this->resourceKey = $controller->id;
            }
        }
    }
}
