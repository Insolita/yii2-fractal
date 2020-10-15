<?php

namespace insolita\fractal\actions;

use League\Fractal\Resource\Item;

/**
 * Handler for routes GET /resource/{id}
 * With defined parentIdParam and parentIdAttribute Handler for  GET /resource/{parentId}/relation/{id}, modelClass
 * should be  defined for related model for this case
 **/
class ViewAction extends JsonApiAction
{
    use HasResourceTransformer;
    use HasParentAttributes;

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
     * Displays a model.
     * @param string|int $id the primary key of the model.
     * @return \League\Fractal\Resource\ResourceInterface
     * @throws \yii\web\NotFoundHttpException
     */
    public function run($id)
    {
        $model = $this->isParentRestrictionRequired() ? $this->findModelForParent($id) : $this->findModel($id);


        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        return new Item($model, new $this->transformer, $this->resourceKey);
    }
}
