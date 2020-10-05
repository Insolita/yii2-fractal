<?php

namespace insolita\fractal;

use League\Fractal\TransformerAbstract;
use yii\base\Model;

/**
 * The dummy transformer implementation that returns only data ids
 */
class IdOnlyTransformer extends TransformerAbstract
{
    public function transform($data)
    {
        return isset($data['id']) ? ['id' => $data['id']]: [];
    }
}
