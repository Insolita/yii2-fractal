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
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQueryInterface;
use const SORT_ASC;
use const SORT_DESC;

/**
 * The wrapper around ActiveDataProvider that helps to return valid League\Fractal\Resource\Collection
 */
class JsonApiActiveDataProvider extends ActiveDataProvider implements JsonApiDataProviderInterface
{
    use HasResourceTransformer;

    private $_pagination;

    /**@var \insolita\fractal\providers\JsonApiSort $_sort*/
    private $_sort;

    public function init():void
    {
        parent::init();
        $this->initResourceTransformer();
    }

    /**
     * @return JsonApiPaginator|false
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
     * @param array|bool|JsonApiPaginator $value
     * @throws \yii\base\InvalidConfigException
     */
    public function setPagination($value):void
    {
        if (is_array($value)) {
            $config = ['class' => JsonApiPaginator::class];
            $this->_pagination = Yii::createObject(array_merge($config, $value));
            if (!$this->_pagination instanceof JsonApiPaginator) {
                throw new InvalidArgumentException('Only JsonApiPaginator instance or false allowed');
            }
        } elseif ($value instanceof JsonApiPaginator || $value === false) {
            $this->_pagination = $value;
        } else {
            throw new InvalidArgumentException('Only JsonApiPaginator instance, configuration array or false is allowed.');
        }
    }

    /**
     * @inheritdoc
     * @return JsonApiSort|bool the sorting object. If this is false, it means the sorting is disabled.
     * @throws \yii\base\InvalidConfigException
     */
    public function getSort()
    {
        if ($this->_sort === null) {
            $this->setSort([]);
        }

        return $this->_sort;
    }

    /**
     * @inheritdoc
     * @param array|JsonApiSort|bool $value the sort definition to be used by this data provider.
     * @throws \yii\base\InvalidConfigException
     */
    public function setSort($value)
    {
        if (is_array($value)) {
            $config = ['class' => JsonApiSort::class];
            if ($this->id !== null) {
                $config['sortParam'] = $this->id . '-sort';
            }
            $this->_sort = Yii::createObject(array_merge($config, $value));
        } elseif ($value instanceof JsonApiSort || $value === false) {
            $this->_sort = $value;
        } else {
            throw new InvalidArgumentException('Only JsonApiSort instance, configuration array or false is allowed.');
        }
        if ($this->_sort !== false && $this->query instanceof ActiveQueryInterface) {
            /* @var $modelClass Model */
            $modelClass = $this->query->modelClass;
            $this->_sort->tableName = $modelClass::tableName();
            $model = $modelClass::instance();
            if (empty($this->_sort->attributes)) {
                foreach ($model->attributes() as $attribute) {
                    $this->_sort->attributes[$attribute] = [
                        'asc' => [$attribute => SORT_ASC],
                        'desc' => [$attribute => SORT_DESC],
                        'label' => $model->getAttributeLabel($attribute),
                    ];
                }
            } else {
                foreach ($this->_sort->attributes as $attribute => $config) {
                    if (!isset($config['label'])) {
                        $this->_sort->attributes[$attribute]['label'] = $model->getAttributeLabel($attribute);
                    }
                }
            }
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
