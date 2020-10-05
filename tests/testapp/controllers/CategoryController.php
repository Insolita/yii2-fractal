<?php

namespace app\controllers;

use app\models\Category;
use insolita\fractal\actions\CreateAction;
use insolita\fractal\actions\ListAction;
use insolita\fractal\actions\UpdateAction;
use insolita\fractal\actions\ViewAction;
use insolita\fractal\actions\ViewRelationshipAction;
use insolita\fractal\JsonApiController;
use app\transformers\CategoryTransformer;
use insolita\fractal\providers\CursorActiveDataProvider;
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
//                  'findModel'=>function($id){
//                     return Category::find()->where(['active'=>true, 'id'=>$id])->one();
//                  }
                  //'transformer'=>CategoryTransformer::class,
              ],
             'update'=>[
                 'class' => UpdateAction::class,
                 'modelClass'=>Category::class,
                 'resourceKey'=>'category',
//                 'findModel'=>function($id){
//                     return Category::find()->where(['active'=>true, 'id'=>$id])->one();
//                 }
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
             'related-posts'=>[
                 'class' => ViewRelationshipAction::class,
                 'modelClass' => Category::class,
                 'relationName'=>'posts',
                 'resourceKey'=>'posts',
                 'dataProvider'=>['class' => CursorActiveDataProvider::class]
             ],
             'options' => [
                 'class' => OptionsAction::class,
             ],
         ];
     }
}