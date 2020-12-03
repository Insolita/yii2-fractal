<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal;

use League\Fractal\TransformerAbstract;
use yii\helpers\ArrayHelper;

/**
 * The dummy transformer implementation that returns only data ids
 */
class IdOnlyTransformer extends TransformerAbstract
{
    public function transform($data)
    {
        $data = ArrayHelper::toArray($data);
        return isset($data['id']) ? ['id' => $data['id']]: ['id' => '0'];
    }
}
