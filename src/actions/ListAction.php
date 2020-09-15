<?php

namespace insolita\fractal\actions;

use insolita\fractal\exceptions\ValidationException;
use insolita\fractal\providers\JsonApiActiveDataProvider;
use League\Fractal\TransformerAbstract;
use Yii;
use yii\db\ActiveQueryInterface;
use function array_intersect;
use function array_merge;

class ListAction extends JsonApiAction
{
    use HasResourceTransformer;
    use HasParentAttributes;

    /**
     * @var callable
    */
    public $prepareDataProvider;

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
     * Eager loading for included relations
     * @param \yii\db\ActiveQueryInterface|\yii\db\ActiveQuery $query
     * @return \yii\db\ActiveQueryInterface
     */
    protected function prepareIncludeQuery(ActiveQueryInterface  $query):ActiveQueryInterface
    {
        if (!$this->transformer instanceof TransformerAbstract) {
            return $query;
        }
        $defaultIncludes = $this->transformer->getDefaultIncludes();
        $allowedIncludes = $this->transformer->getAvailableIncludes();
        $requestedIncludes = $this->controller->manager->getRequestedIncludes();
        $include = array_merge($defaultIncludes, array_intersect($allowedIncludes, $requestedIncludes));
        //@TODO: ?validate if included relations existed ?
        return empty($include)? $query : $query->with($include);
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
        $condition[$this->parentIdAttribute] = $parentId;
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

        if ($this->prepareDataProvider !== null) {
            return call_user_func($this->prepareDataProvider, $this, $filter);
        }

        /* @var $modelClass \yii\db\BaseActiveRecord */
        $modelClass = $this->modelClass;

        $query = $this->prepareIncludeQuery($modelClass::find());
        $query = $this->prepareParentQuery($query);

        if (!empty($filter)) {
            $query->andWhere($filter);
        }

        return  Yii::createObject([
            'class' => JsonApiActiveDataProvider::class,
            'query' => $query,
            'resourceKey'=>$this->resourceKey,
            'transformer'=>$this->transformer,
            'sort' => [
                'params' => $requestParams,
            ],
        ]);
    }
}
