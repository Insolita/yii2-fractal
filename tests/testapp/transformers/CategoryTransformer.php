<?php

namespace app\transformers;

use app\models\Category;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;
use Yii;
use yii\helpers\Url;

class CategoryTransformer extends TransformerAbstract
{
    public function transform(Category $category)
    {
        return [
            'id' => $category->id,
            'name'=>$category->name,
//            'links'=>[
//                'self'=>Url::to('/category/'.$category->id, true)
//            ]
        ];
    }
}