<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\actions;

use insolita\fractal\exceptions\ValidationException;
use insolita\fractal\providers\CursorActiveDataProvider;
use insolita\fractal\providers\JsonApiActiveDataProvider;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQueryInterface;
use yii\helpers\StringHelper;
use function array_map;
use function explode;
use function implode;
use function ltrim;
use function strpos;

/**
 * Handler for routes GET /resource
 * With defined parentIdParam and parentIdAttribute Handler for  GET /resource/{id}/relation, modelClass should be
 * defined for related model for this case
 **/
class ListAction extends JsonApiAction
{
    use HasResourceTransformer;
    use HasParentAttributes;

    /**
     * @var callable
     * @example
     * 'prepareDataProvider' => function(ListAction $action, \yii\data\DataProviderInterface $dataProvider) {
     *      Modify $dataProvider
     *      or return completely configured dataProvider (JsonApiActiveDataProvider|CursorActiveDataProvider)
     * }
    */
    public $prepareDataProvider;

    /**
     * @var \yii\data\DataFilter
    */
    public $dataFilter;

    /**
     * Provide custom configured dataProvider object (JsonApiActiveDataProvider|CursorActiveDataProvider)
     * You can set 'pagination' => false for disable pagination
     * @var array
     */
    public $dataProvider = [
        'class' => JsonApiActiveDataProvider::class,
        'pagination'=>['defaultPageSize' => 20]
    ];

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
     * @return \insolita\fractal\providers\JsonApiActiveDataProvider|object
     * @throws \insolita\fractal\exceptions\ValidationException
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }

        return $this->makeDataProvider();
    }

    /**
     * @param $requestParams
     * @return array|bool|mixed|null
     * @throws \insolita\fractal\exceptions\ValidationException
     * @throws \yii\base\InvalidConfigException
     */
    protected function prepareDataFilter($requestParams)
    {
        if ($this->dataFilter === null) {
            return null;
        }
        $this->dataFilter = Yii::createObject($this->dataFilter);
        if ($this->dataFilter->load($requestParams)) {
            $filter = $this->dataFilter->build();
            if ($filter === false) {
                throw new ValidationException($this->dataFilter->getErrors());
            }
            return $filter;
        }
        return null;
    }

    /**
     * Add condition for parent model restriction if needed
     * @param \yii\db\ActiveQueryInterface $query
     * @return \yii\db\ActiveQueryInterface
     */
    protected function prepareParentQuery(ActiveQueryInterface $query):ActiveQueryInterface
    {
        if (!$this->isParentRestrictionRequired()) {
            return $query;
        }
        $id = Yii::$app->request->getQueryParam('id', null);
        $condition = ($this->parentIdParam !== 'id')? $this->findModelCondition($id): [];
        $parentId = Yii::$app->request->getQueryParam($this->parentIdParam, null);
        $condition[$this->modelTable().'.'.$this->parentIdAttribute] = $parentId;
        $query->where($condition);
        return $query;
    }


    /**
     * @return JsonApiActiveDataProvider|object
     * @throws \insolita\fractal\exceptions\ValidationException
     * @throws \yii\base\InvalidConfigException
     */
    protected function makeDataProvider()
    {
        $requestParams = Yii::$app->getRequest()->getBodyParams();
        if (empty($requestParams)) {
            $requestParams = Yii::$app->getRequest()->getQueryParams();
        }
        $filter = $this->prepareDataFilter($requestParams);

        /* @var $modelClass \yii\db\BaseActiveRecord */
        $modelClass = $this->modelClass;
        $query = $this->prepareParentQuery($modelClass::find());
        $query = $this->prepareIncludeQuery($query);


        if (!empty($filter)) {
            $query->andWhere($filter);
        }

        $dataProvider = Yii::createObject($this->dataProvider);
        if (!$dataProvider instanceof JsonApiActiveDataProvider && !$dataProvider instanceof CursorActiveDataProvider) {
            throw new InvalidConfigException('Invalid dataProvider configuration');
        }
        $dataProvider->query = $query;
        $dataProvider->resourceKey = $this->resourceKey;
        $dataProvider->transformer = $this->transformer;
        $dataProvider->setSort(['params' => $requestParams]);

        if ($this->prepareDataProvider !== null) {
            return call_user_func($this->prepareDataProvider, $this, $dataProvider);
        }

        return $dataProvider;
    }
}
