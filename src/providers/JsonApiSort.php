<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\providers;

use Traversable;
use yii\data\Sort;
use yii\helpers\StringHelper;
use function is_array;
use function is_string;
use function ltrim;
use function strpos;
use const SORT_ASC;
use const SORT_DESC;

class JsonApiSort extends Sort
{
    public $tableName;

    /**
     * Append attributes allowed for sort
     * @example
     * $dataProvider->sort->addAttributes([
     *    'relation.fieldA',
     *    'relation.fieldB',
     *    'alias'=>['asc'=>['some_field'=>SORT_ASC], 'desc'=>['some_field'=>SORT_DESC]]
     * ])
     * @param array $attributes
     */
    public function addAttributes(array $attributes):void
    {
        foreach ($attributes as $attribute => $data) {
            if (is_string($attribute) && is_array($data)) {
                $this->attributes[$attribute] = $data;
            } elseif (is_string($data)) {
                $this->attributes[$data] = [
                    'asc' => [$data => SORT_ASC],
                    'desc' => [$data => SORT_DESC],
                ];
            }
        }
    }

    /**
     * Override default order query builder, always add table prefix
     * @param false $recalculate
     * @return array
     */
    public function getOrders($recalculate = false)
    {
        $attributeOrders = $this->getAttributeOrders($recalculate);
        $orders = [];
        foreach ($attributeOrders as $attribute => $direction) {
            $definition = $this->attributes[$attribute];
            $columns = $definition[$direction === SORT_ASC ? 'asc' : 'desc'];
            if (is_array($columns) || $columns instanceof Traversable) {
                foreach ($columns as $name => $dir) {
                    if (strpos($name, '.') === false) {
                        $name = $this->wrapWithTable($name);
                    }

                    $orders[$name] = $dir;
                }
            } elseif (strpos($columns, '.') !== false) {
                $orders[] = $columns;
            } else {
                $isDesc = StringHelper::startsWith($columns, '-');
                $orders[] = ($isDesc ? '-' : '') . $this->wrapWithTable(ltrim($columns, '-'));
            }
        }

        return $orders;
    }

    private function wrapWithTable(string $attribute):string
    {
        if (!$this->tableName) {
            return $attribute;
        }
        return $this->tableName . '.' . $attribute;
    }
}
