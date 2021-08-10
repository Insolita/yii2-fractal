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

/**
 * Handler for list actions with parent id equals current user identity
 * @example
 * show user posts
 *  $dataProvider by query Post::find()->where(['author_id' => Yii::$app->user->id])->... + filter conditions
 * 'my-posts' => [
 *       'class' => ListForIdentityAction::class,
 *       'userIdAttribute' => 'author_id',
 *       'modelClass' => Post::class,
 *       'transformer' => PostTransformer::class
 *  ],
**/
class ListForIdentityAction extends JsonApiAction
{
    use HasResourceTransformer;

    /**
     * @var string
     * user foreign key attribute
    */
    public $userIdAttribute = 'user_id';
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
        $userId = Yii::$app->user->id;
        $condition[$this->modelTable().'.'.$this->userIdAttribute] = $userId;
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
