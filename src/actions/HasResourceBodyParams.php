<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\actions;

use Yii;
use function is_array;

trait HasResourceBodyParams
{
    protected $resourceData;

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected function getResourceData():array
    {
        if (!$this->resourceData) {
            $this->resourceData = Yii::$app->getRequest()->getBodyParams()['data'] ?? [];
        }
        return $this->resourceData;
    }

    protected function getResourceMeta():array
    {
        return Yii::$app->getRequest()->getBodyParams()['meta'] ?? [];
    }

    /**
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    protected function isCollectionResource():bool
    {
        return !empty($this->getResourceData()) && is_array($this->getResourceData());
    }

    /**
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    protected function isSingleResource():bool
    {
        return !$this->isCollectionResource();
    }

    /**
     * @return string|null
     * @throws \yii\base\InvalidConfigException
     */
    protected function getResourceType():?string
    {
        return $this->getResourceData()['type'] ?? null;
    }

    /**
     * @return string|null
     * @throws \yii\base\InvalidConfigException
     */
    protected function getResourceId():?string
    {
        return $this->getResourceData()['id'] ?? null;
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected function getResourceAttributes():array
    {
        return $this->getResourceData()['attributes'] ?? [];
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected function getResourceRelationships():array
    {
        return $this->getResourceData()['relationships'] ?? [];
    }

    /**
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    protected function hasResourceRelationships():bool
    {
        return !empty($this->getResourceData()['relationships']);
    }
}
