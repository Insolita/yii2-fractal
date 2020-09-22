<?php

/**
 * @copyright Copyright (c) 2018 Carsten Brandt <mail@cebe.cc> and contributors
 * @license https://github.com/cebe/yii2-openapi/blob/master/LICENSE
 */

use Codeception\Stub;
use Codeception\Test\Unit;
use insolita\fractal\pagination\JsonApiPaginator;

class JsonApiPaginatorTest extends Unit
{
    public function testPaginatorData():void
    {
        $this->mockPageQuery(1, 5);
        $paginator = new JsonApiPaginator([
            'totalCount' => 30,
            'itemsCount' => 5,
        ]);
        expect($paginator->getPage())->equals(1);
        expect($paginator->getPerPage())->equals(5);
        expect($paginator->getPageSize())->equals($paginator->getPerPage());
        expect($paginator->getCurrentPage())->equals($paginator->getPage());
        expect($paginator->getPageCount())->equals(6);
        expect($paginator->getLastPage())->equals($paginator->getPageCount());
        expect($paginator->getTotal())->equals(30);
        expect($paginator->getCount())->equals(5);
        expect($paginator->getOffset())->equals(0);

        $this->mockPageQuery(3, 5);
        $paginator = new JsonApiPaginator([
            'totalCount' => 30,
            'itemsCount' => 5,
        ]);
        expect($paginator->getPage())->equals(3);
        expect($paginator->getPerPage())->equals(5);
        expect($paginator->getPageSize())->equals($paginator->getPerPage());
        expect($paginator->getCurrentPage())->equals($paginator->getPage());
        expect($paginator->getPageCount())->equals(6);
        expect($paginator->getLastPage())->equals($paginator->getPageCount());
        expect($paginator->getTotal())->equals(30);
        expect($paginator->getCount())->equals(5);
        expect($paginator->getOffset())->equals(10);

        $this->mockPageQuery(3, 11);
        $paginator = new JsonApiPaginator([
            'totalCount' => 30,
            'itemsCount' => 8,
        ]);
        expect($paginator->getPage())->equals(3);
        expect($paginator->getPerPage())->equals(11);
        expect($paginator->getPageSize())->equals($paginator->getPerPage());
        expect($paginator->getCurrentPage())->equals($paginator->getPage());
        expect($paginator->getPageCount())->equals(3);
        expect($paginator->getLastPage())->equals($paginator->getPageCount());
        expect($paginator->getTotal())->equals(30);
        expect($paginator->getCount())->equals(8);
        expect($paginator->getOffset())->equals(22);
        $this->mockPageQuery(30, 1);
        $paginator = new JsonApiPaginator([
            'totalCount' => 30,
            'itemsCount' => 1
        ]);
        expect($paginator->getPage())->equals(30);
        expect($paginator->getPerPage())->equals(1);
        expect($paginator->getPageSize())->equals($paginator->getPerPage());
        expect($paginator->getCurrentPage())->equals($paginator->getPage());
        expect($paginator->getPageCount())->equals(30);
        expect($paginator->getLastPage())->equals($paginator->getPageCount());
        expect($paginator->getTotal())->equals(30);
        expect($paginator->getCount())->equals(1);
        expect($paginator->getOffset())->equals(29);
    }

    public function testPaginatorBadValues()
    {
        $this->mockPageQuery(0, 5);
        $paginator = new JsonApiPaginator([
            'totalCount' => 30,
            'itemsCount' => 5,
        ]);
        expect($paginator->getPage())->equals(1);

        $this->mockPageQuery(100, 5);
        $paginator = new JsonApiPaginator([
            'totalCount' => 30,
            'itemsCount' => 5,
        ]);
        expect($paginator->getPage())->equals(6);

        $this->mockPageQuery(4, 11);
        $paginator = new JsonApiPaginator([
            'totalCount' => 30,
            'itemsCount' => 5,
        ]);
        expect($paginator->getPage())->equals(3);
    }

    private function mockPageQuery(int $number = 1, int $size = 10)
    {
        $request = Stub::make(
            \yii\web\Request::class,
            [
                'getQueryParams' => function () use ($number, $size) {
                    return ['page' => ['number' => $number, 'size' => $size]];
                },
            ]
        );
        Yii::$app->set('request', $request);
    }
}
