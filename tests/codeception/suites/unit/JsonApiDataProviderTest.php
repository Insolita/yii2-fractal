<?php

use app\models\Category;
use app\models\User;
use app\transformers\CategoryTransformer;
use Codeception\Test\Unit;
use insolita\fractal\DefaultTransformer;
use insolita\fractal\pagination\JsonApiPaginator;
use insolita\fractal\providers\JsonApiActiveDataProvider;
use insolita\fractal\providers\JsonApiArrayDataProvider;
use League\Fractal\Resource\Collection;

class JsonApiDataProviderTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;


    public function testToCollection()
    {
        $provider = new JsonApiActiveDataProvider([
            'query'=>User::find(),
            'resourceKey'=>'authors'
        ]);
        expect($provider->pagination)->isInstanceOf(JsonApiPaginator::class);
        $resource = $provider->toCollection();
        expect($resource)->isInstanceOf(Collection::class);
        expect($resource->getResourceKey())->equals('authors');
        expect($resource->getTransformer())->isInstanceOf(DefaultTransformer::class);

        $provider = new JsonApiArrayDataProvider([
            'models'=>Category::find()->all(),
            'resourceKey'=>'categories',
            'transformer'=>CategoryTransformer::class
        ]);
        expect($provider->pagination)->isInstanceOf(JsonApiPaginator::class);
        $resource = $provider->toCollection();
        expect($resource)->isInstanceOf(Collection::class);
        expect($resource->getResourceKey())->equals('categories');
        expect($resource->getTransformer())->isInstanceOf(CategoryTransformer::class);
    }


}