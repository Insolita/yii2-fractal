<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\actions;

use Closure;
use insolita\fractal\exceptions\ValidationException;
use insolita\fractal\RelationshipManager;
use League\Fractal\Resource\Item;
use Throwable;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecordInterface;
use yii\helpers\Url;
use yii\web\ServerErrorHttpException;
use function array_keys;
use function call_user_func;

/**
 * Handler for routes POST /resource
 * With defined parentIdParam and parentIdAttribute Handler for  POST /resource/{id}/relation, modelClass should be
 * defined for related model for this case
 **/
class CreateAction extends JsonApiAction
{
    use HasResourceTransformer;
    use HasParentAttributes;
    /**
     * @var array
     *  * Configuration for attaching relationships
     * Should contains key - relation name and array with
     *             idType - php type of resource ids for validation
     *             validator = callback for custom id validation
     * Keep it empty for disable this ability
     * @see https://jsonapi.org/format/#crud-creating
     * @example
     *  'allowedRelations' => [
     *       'author' => ['idType' => 'integer'],
     *       'photos' => ['idType' => 'integer', 'validator' => function($model, array $ids) {
     *              $relatedModels = Relation::find()->where(['id' => $ids])->andWhere([additional conditions])->all();
     *              if(count($relatedModels) < $ids) {
     *                throw new HttpException(422, 'Invalid photos ids');
     *        }],
     * ]
    **/
    public $allowedRelations = [];
    /**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * @var string the name of route or action for view created model
     */
    public $viewRoute = 'view';

    /**
     * @var callable|Closure Callback after save model with all relations
     * @example
     *   'afterSave' => function ($model) {
     *           $model->doSomething();
     * }
    */
    public $afterSave = null;

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
     * @throws \Throwable
     * @throws \insolita\fractal\exceptions\ValidationException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\Exception
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\HttpException
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\web\ServerErrorHttpException
     */
    public function run()
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this);
        }

        /* @var $model \yii\db\ActiveRecord */
        $model = new $this->modelClass([
            'scenario' => $this->scenario,
        ]);
        RelationshipManager::validateRelationships($model, $this->getResourceRelationships(), $this->allowedRelations);
        $model->load($this->getResourceAttributes(), '');
        if ($this->isParentRestrictionRequired()) {
            $parentId = Yii::$app->request->getQueryParam($this->parentIdParam, null);
            if ($parentId) {
                $model->setAttribute($this->parentIdAttribute, $parentId);
            }
        }
        $transact = $model::getDb()->beginTransaction();
        try {
            if ($model->save() === false && !$model->hasErrors()) {
                throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
            }
            if ($model->hasErrors()) {
                throw new ValidationException($model->getErrors());
            }
            if (!empty($this->allowedRelations) && $this->hasResourceRelationships()) {
                $this->linkRelationships($model);
            }
            $transact->commit();
        } catch (Throwable $e) {
            $transact->rollback();
            throw $e;
        }
        $model->refresh();
        if ($this->afterSave !== null) {
            call_user_func($this->afterSave, $model);
        }
        $response = Yii::$app->getResponse();
        $response->setStatusCode(201);
        $response->getHeaders()->set('Location', Url::to([$this->viewRoute, 'id' => $model->primaryKey], true));

        return new Item($model, new $this->transformer, $this->resourceKey);
    }

    /**
     * @param \yii\db\ActiveRecordInterface $model
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\Exception
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\HttpException
     * @throws \yii\web\NotFoundHttpException
     */
    protected function linkRelationships(ActiveRecordInterface $model):void
    {
        $relationships = $this->getResourceRelationships();
        $relationNames = array_keys($relationships);
        foreach ($relationNames as $relationName) {
            $options = $this->allowedRelations[$relationName];
            $manager = new RelationshipManager(
                $model,
                $relationName,
                $relationships[$relationName]['data'],
                $options['idType']
            );
            if (isset($options['validator']) && \is_callable($options['validator'])) {
                $manager->setIdValidateCallback($options['validator']);
            }
            $manager->attach();
        }
    }
}
