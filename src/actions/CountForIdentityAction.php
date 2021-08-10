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
 * Provide ability for count resource items without data loading related to current user identity
 * (with filters support)
 * Return header X-Pagination-Total-Count  with count value  (Use with HEAD request)
 * @example
 *  count posts
 *  Post::find()->where(['author_id' => Yii::$app->userId])->andWhere([...filter condition])->count();
 *  'count' => [
 *     'class' => CountAction::class,
 *     'modelClass' => Post::class,
 *     'dataFilter' => PostDataFilter::class,
 *     'userIdAttribute' => 'author_id'
 *  ],
**/
class CountForIdentityAction extends JsonApiAction
{
    /**
     * @var string
     * user foreign key attribute
     */
    public $userIdAttribute = 'user_id';

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
        $condition[$this->modelTable().'.'.$this->userIdAttribute] = Yii::$app->user->id;
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

        if (!empty($filter)) {
            $query->andWhere($filter);
        }
        return $query;
    }
}
