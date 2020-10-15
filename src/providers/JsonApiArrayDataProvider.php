<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\providers;

use insolita\fractal\actions\HasResourceTransformer;
use insolita\fractal\pagination\JsonApiPaginator;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\ResourceInterface;
use Yii;
use yii\base\InvalidArgumentException;
use yii\data\ArrayDataProvider;

/**
 * The wrapper around ArrayDataProvider that helps to return valid League\Fractal\Resource\Collection
 */
class JsonApiArrayDataProvider extends ArrayDataProvider implements JsonApiDataProviderInterface
{
    use HasResourceTransformer;

    private $_pagination;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init():void
    {
        parent::init();
        $this->initResourceTransformer();
    }

    /**
     * @return \insolita\fractal\pagination\JsonApiPaginator|false
     * @throws \yii\base\InvalidConfigException
     */
    public function getPagination()
    {
        if ($this->_pagination === null) {
            $this->setPagination(['class' => JsonApiPaginator::class]);
        }

        return $this->_pagination;
    }

    /**
     * @param array|bool|\yii\data\Pagination $value
     * @throws \yii\base\InvalidConfigException
     */
    public function setPagination($value):void
    {
        if (is_array($value)) {
            $config = ['class' => JsonApiPaginator::class];
            $this->_pagination = Yii::createObject(array_merge($config, $value));
            if (! $this->_pagination instanceof JsonApiPaginator) {
                throw new InvalidArgumentException('Only JsonApiPaginator instance or false allowed');
            }
        } elseif ($value instanceof JsonApiPaginator || $value === false) {
            $this->_pagination = $value;
        } else {
            throw new InvalidArgumentException('Only Pagination instance, configuration array or false is allowed.');
        }
    }

    /**
     * @return \League\Fractal\Resource\ResourceInterface
     * @throws \yii\base\InvalidConfigException
     */
    public function toCollection():ResourceInterface
    {
        $resource = new Collection($this->getModels(), $this->transformer, $this->resourceKey);
        $paginator = $this->getPagination();
        if ($paginator !== false) {
            $paginator->setItemsCount($this->getCount());
            $resource->setPaginator($this->getPagination());
        }
        return $resource;
    }
}
