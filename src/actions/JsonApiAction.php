<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\actions;

use insolita\fractal\JsonApiController;
use insolita\fractal\JsonApiError;
use yii\base\Action;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecordInterface;
use yii\web\NotFoundHttpException;
use function gettype;
use function in_array;
use function reset;

/**
 * @property-read array       $resourceAttributes
 * @property-read null|string $resourceType
 * @property-read array       $queryFields
 * @property-read array       $resourceData
 * @property-read array       $includes
 */
class JsonApiAction extends Action
{
    use HasResourceBodyParams;
    use HasIncludes;

    /**
     * @var \insolita\fractal\JsonApiController $controller
     */
    public $controller;

    /**
     * @var string class name of the model which will be handled by this action.
     * The model class must implement [[ActiveRecordInterface]].
     * This property must be set.
     */
    public $modelClass;

    /**
     * @var callable a PHP callable that will be called to return the model corresponding
     * to the specified primary key value. If not set, [[findModel()]] will be used instead.
     * The signature of the callable should be:
     * ```php
     * function ($id, $action) {
     *     // $id is the primary key value. If composite primary key, the key values
     *     // will be separated by comma.
     *     // $action is the action object currently running
     * }
     * ```
     * The callable should return the model found, or throw an exception if not found.
     */
    public $findModel;

    /**
     * @var callable a PHP callable that will be called when running an action to determine
     * if the current user has the permission to execute the action. If not set, the access
     * check will not be performed. The signature of the callable should be as follows,
     * ```php
     * function ($action, $model = null) {
     *     // $model is the requested model instance.
     *     // If null, it means no specific model (e.g. IndexAction)
     * }
     * ```
     */
    public $checkAccess;

    /**
     * {@inheritDoc}
     * @throws \yii\base\InvalidConfigException
     */
    public function init():void
    {
        parent::init();
        if (!$this->controller instanceof JsonApiController) {
            throw new InvalidCallException('JsonApiAction Actions must be used only with JsonApiController');
        }
        if ($this->modelClass === null) {
            throw new InvalidConfigException(get_class($this) . '::$modelClass must be set.');
        }
    }

    /**
     * Returns the data model based on the primary key given.
     * If the data model is not found, a 404 HTTP exception will be raised.
     * @param string|int|null $id the ID of the model to be loaded. If the model has a composite primary key,
     * the ID must be a string of the primary key values separated by commas.
     * The order of the primary key values should follow that returned by the `primaryKey()` method
     * of the model.
     * @return ActiveRecordInterface the model found
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $this->ensureIntOrString($id, 'id');
        if ($this->findModel !== null) {
            $model = call_user_func($this->findModel, $id, $this);
            if (!$model) {
                throw new NotFoundHttpException("Object not found: $id");
            }
            return $model;
        }

        /* @var $modelClass ActiveRecordInterface */
        $modelClass = $this->modelClass;
        $condition = $this->findModelCondition($id);

        if (!empty($condition)) {
            $query = $this->prepareIncludeQuery($modelClass::find());
            $model = $query->where($condition)->limit(1)->one();
        }

        if (isset($model)) {
            return $model;
        }

        throw new NotFoundHttpException("Object not found: $id");
    }

    protected function modelTable():string
    {
        /**@var ActiveRecordInterface $modelClass */
        $modelClass = $this->modelClass;
        return $modelClass::tableName();
    }

    /**
     * resolve primary key condition
     * @param int|string|null $id
     * @return array
     */
    protected function findModelCondition($id):array
    {
        /* @var $modelClass ActiveRecordInterface */
        $modelClass = $this->modelClass;
        $keys = $modelClass::primaryKey();
        if (count($keys) > 1) {
            $values = explode(',', $id);
            if (count($keys) === count($values)) {
                $condition = array_combine($keys, $values);
            }
        } elseif ($id !== null) {
            $idKey = reset($keys);
            $condition = [$this->modelTable().'.'.$idKey => $id];
        }

        return $condition ?? [];
    }

    protected function ensureNullIntOrString($value, $name)
    {
        if (!in_array(gettype($value), ['integer', 'string', 'NULL'])) {
            return new JsonApiError([
                'code' => 422,
                'title' => 'Invalid type of "' . $name . '"',
                'detail' => 'Value should be null, integer, or string',
            ]);
        }
    }

    protected function ensureIntOrString($value, $name)
    {
        if (!in_array(gettype($value), ['integer', 'string'])) {
            return new JsonApiError([
                'code' => 422,
                'title' => 'Invalid type of "' . $name . '"',
                'detail' => 'Value should be integer, or string',
            ]);
        }
    }
}
