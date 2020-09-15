<?php

namespace insolita\fractal\actions;

use League\Fractal\Resource\Collection;

/**
 * Provide ability to show collection with all data without pagination
**/
class ListAllAction extends JsonApiAction
{
    use HasResourceTransformer;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init():void
    {
        parent::init();
        $this->initResourceTransformer();
    }

    public function run():Collection
    {
        $model = new $this->modelClass;
        $items = $model::find()->all();

        return new Collection($items, $this->transformer, $this->resourceKey);
    }
}
