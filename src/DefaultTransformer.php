<?php

namespace insolita\fractal;

use League\Fractal\TransformerAbstract;
use yii\base\Model;

/**
 * The dummy transformer implementation that returns data as is
 */
class DefaultTransformer extends TransformerAbstract
{
    public function transform($data)
    {
        if ($data instanceof Model) {
            return $data->getAttributes();
        }
        return $data;
    }
}
