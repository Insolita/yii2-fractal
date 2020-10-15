<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\providers;

use insolita\fractal\actions\HasResourceTransformer;
use insolita\fractal\pagination\CursorPagination;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\ResourceInterface;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\data\BaseDataProvider;
use yii\db\ActiveQueryInterface;
use yii\db\Connection;
use yii\di\Instance;
use function max;

class CursorActiveDataProvider extends BaseDataProvider implements JsonApiDataProviderInterface
{
    use HasResourceTransformer;

    /**@var string cursored attribute name */
    public $cursorAttribute = 'id';

    /**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection.
     * If not set, the default DB connection will be used.
     * Starting from version 2.0.2, this can also be a configuration array for creating the object.
     */
    public $db;

    /**@var \yii\db\ActiveQueryInterface $query */
    public $query;

    /**@var CursorPagination */
    private $pagination;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init():void
    {
        parent::init();
        if (is_string($this->db)) {
            $this->db = Instance::ensure($this->db, Connection::class);
        }
        $this->initResourceTransformer();
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
            $nextCursor = !empty($this->getKeys()) ? max($this->getKeys()) : null;
            $paginator->setNextCursor($nextCursor);
            $resource->setCursor($paginator->toFractalCursor());
        }
        return $resource;
    }

    /**
     * Returns the pagination object used by this data provider.
     * Note that you should call [[prepare()]] or [[getModels()]] first to get correct values
     * @return CursorPagination|false the pagination object. If this is false, it means the pagination is disabled.
     * @throws \yii\base\InvalidConfigException
     */
    public function getPagination()
    {
        if ($this->pagination === null) {
            $this->setPagination([]);
        }

        return $this->pagination;
    }

    /**
     * Sets the pagination for this data provider.
     * @param array|\insolita\fractal\pagination\CursorPagination|bool $value the pagination to be used by this data provider.
     * This can be one of the following:
     * - a configuration array for creating the pagination object. The "class" element defaults
     *   \insolita\fractal\pagination\CursorPagination
     * - an instance of [[CursorPagination]] or its subclass
     * - false, if pagination needs to be disabled.
     * @throws InvalidArgumentException|\yii\base\InvalidConfigException
     */
    public function setPagination($value):void
    {
        if (is_array($value)) {
            $config = ['class' => CursorPagination::class];
            $this->pagination = Yii::createObject(array_merge($config, $value));
        } elseif ($value instanceof CursorPagination || $value === false) {
            $this->pagination = $value;
        } else {
            throw new InvalidArgumentException('Only CursorPagination instance, configuration array or false is allowed.');
        }
    }

    public function __clone()
    {
        if (is_object($this->query)) {
            $this->query = clone $this->query;
        }

        parent::__clone();
    }

    /**
     * @return string
     * @throws \yii\base\NotSupportedException
     */
    protected function getCursorAttribute():string
    {
        if ($this->cursorAttribute === null) {
            $modelClass = $this->query->modelClass;
            $pks = $modelClass::primaryKey();
            if (count($pks) === 1) {
                $this->cursorAttribute = $pks[0];
            } else {
                throw new NotSupportedException('Composite primary key not supported');
            }
        }
        return $this->cursorAttribute;
    }

    /**
     * @param array|\yii\db\ActiveRecord[] $models
     * @return array
     * @throws \yii\base\NotSupportedException
     */
    protected function prepareKeys($models)
    {
        $keys = [];
        $pk = $this->getCursorAttribute();
        foreach ($models as $model) {
            $keys[] = $model[$pk];
        }
        return $keys;
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     */
    protected function prepareModels():array
    {
        $this->validateQuery();
        $query = clone $this->query;
        if (($pagination = $this->getPagination()) !== false) {
            $pagination->setTotalCount($this->getTotalCount());
            if ($pagination->getTotalCount() === 0) {
                return [];
            }
            $query->limit($pagination->getLimit());
            if ($pagination->getCurrentCursor() !== null) {
                $query->where(['>', $this->getCursorAttribute(), $pagination->getCurrentCursor()]);
            }
        }
        if (($sort = $this->getSort()) !== false) {
            $query->addOrderBy($sort->getOrders());
        } else {
            $query->addOrderBy([$this->getCursorAttribute() => 'asc']);
        }

        return $query->all($this->db);
    }

    /**
     * @return int
     * @throws \yii\base\InvalidConfigException
     */
    protected function prepareTotalCount():int
    {
        $this->validateQuery();
        $query = clone $this->query;
        return (int)$query->limit(-1)->offset(-1)->orderBy([])->count('*', $this->db);
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    protected function validateQuery():void
    {
        if (!$this->query instanceof ActiveQueryInterface) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the ActiveQueryInterface e.g. yii\db\ActiveQuery or its subclasses.');
        }
    }
}
