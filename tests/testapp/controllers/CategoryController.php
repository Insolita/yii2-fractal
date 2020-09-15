<?php

namespace app\controllers;

use app\models\Category;
use insolita\fractal\actions\CreateAction;
use insolita\fractal\actions\ListAction;
use insolita\fractal\actions\UpdateAction;
use insolita\fractal\actions\ViewAction;
use insolita\fractal\JsonApiController;
use app\transformers\CategoryTransformer;
use yii\rest\DeleteAction;
use yii\rest\OptionsAction;

class CategoryController extends JsonApiController
{
     public function actions()
     {
         return [
             'bad-config'=>[
                 'class' => ViewAction::class,
                 'transformer'=>CategoryTransformer::class,
                 'resourceKey'=>'category'
             ],
             'create'=>[
                 'class' => CreateAction::class,
                 'modelClass'=>Category::class,
                 'viewRoute'=>'view',
                 //'scenario'=>'create',
                 'resourceKey'=>'category',
                 'transformer'=>CategoryTransformer::class,
             ],
              'view'=>[
                  'class' => ViewAction::class,
                  'modelClass'=>Category::class,
                  'resourceKey'=>'category',
                  //'transformer'=>CategoryTransformer::class,
              ],
             'update'=>[
                 'class' => UpdateAction::class,
                 'modelClass'=>Category::class,
                 'resourceKey'=>'category',
                 //'transformer'=>CategoryTransformer::class,
             ],
             'delete'=>[
                 'class' => DeleteAction::class,
                 'modelClass'=>Category::class,
             ],
             'list'=>[
                 'class' => ListAction::class,
                 'modelClass'=>Category::class,
                 'transformer'=>CategoryTransformer::class,
                 'resourceKey'=>'category'
             ],
             'options' => [
                 'class' => OptionsAction::class,
             ],
         ];
     }
}