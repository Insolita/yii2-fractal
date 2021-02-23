<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\actions;

use insolita\fractal\exceptions\ValidationException;
use insolita\fractal\providers\JsonApiActiveDataProvider;
use Yii;
use yii\db\ActiveQueryInterface;

/**
 * Provide ability for count resource items without data loading
 * (with filters support)
 * Return header X-Pagination-Total-Count  with count value  (Use with HEAD request)
 * @example
 *  count posts
 *  Post::find()->where([...filter condition])->count();
 *  'count' => [
 *     'class' => CountAction::class,
 *     'modelClass' => Post::class,
 *     'dataFilter' => PostDataFilter::class
 *  ],
 *  count posts for category (for example by route /category/<id:d+>/post-count)
 *  Post::find()->where(['category_id' => Yii::$app->request->get('id')])->andWhere([...filter condition])->count();
 *  'count-for-category' => [
 *     'class' => CountAction::class,
 *     'modelClass' => Post::class,
 *     'parentIdAttribute' => 'category_id',
 *     'parentIdParam' => 'id'
 *  ]
**/
class CountAction extends JsonApiAction
{
    use HasParentAttributes;

    /**
     * @var callable
     * @example
     * 'queryWrapper' => function(CountAction $action, ActiveQuery Query) {
     *      Modify $query
     *      or return own ActiveQuery
     * }
     */
    public $queryWrapper;

    /**
     * @var \yii\data\DataFilter
     */
    public $dataFilter;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init():void
    {
        parent::init();
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

        $query =  $this->makeQuery();
        if ($this->queryWrapper !== null) {
            $query = \call_user_func($this->queryWrapper, $this, $query);
        }
        $count = $query->count();
        Yii::$app->response->headers->set('X-Pagination-Total-Count', $count);
        Yii::$app->response->setStatusCode(204);
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
    protected function makeQuery()
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
        return $query;
    }
}
