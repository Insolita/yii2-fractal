<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal\actions;

use League\Fractal\TransformerAbstract;
use Yii;
use yii\db\ActiveQueryInterface;
use function explode;
use function in_array;

trait HasIncludes
{
    /**
     * Eager loading for included relations
     * @param \yii\db\ActiveQueryInterface|\yii\db\ActiveQuery $query
     * @return \yii\db\ActiveQueryInterface
     */
    protected function prepareIncludeQuery(ActiveQueryInterface  $query):ActiveQueryInterface
    {
        if (!Yii::$app->request->isGet) {
            return $query;
        }
        if (!$this->transformer instanceof TransformerAbstract) {
            return $query;
        }
        $defaultIncludes = $this->transformer->getDefaultIncludes();
        $allowedIncludes = $this->transformer->getAvailableIncludes();
        $requestedIncludes = $this->controller->manager->getRequestedIncludes();
        $validIncludes = array_filter($requestedIncludes, function ($value) use ($allowedIncludes) {
            $baseRelation = explode('.', $value)[0];
            return in_array($baseRelation, $allowedIncludes, true);
        });
        $include = array_merge($defaultIncludes, $validIncludes);
        //@TODO: ?validate if included relations existed ?
        return empty($include)? $query : $query->with($include);
    }
}
