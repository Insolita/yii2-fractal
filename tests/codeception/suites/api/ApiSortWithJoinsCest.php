<?php

namespace codeception\suites\api;

use ApiTester;

class ApiSortWithJoinsCest
{
    public function testListWithJoins(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/posts-join', ['sort'=>'-category.id', 'include'=>['category'], 'page'=>['size' => 5]]);
        $I->seeResponseCodeIsSuccessful();
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseHasMetaPagination();
        $categories = $I->grabDataFromResponseByJsonPath('$.data[*].attributes.category_id');
        $I->assertEquals([4, 4, 4, 4, 4], $categories);
    }

    public function testListForCategoryWithJoins(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/categories/3/posts-join', ['page'=>['size' => 5], 'sort'=>'-comments.id']);
        $I->seeResponseCodeIsSuccessful();
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseHasMetaPagination();
        $categories = $I->grabDataFromResponseByJsonPath('$.data[*].attributes.category_id');
        $I->assertEquals([3, 3, 3, 3, 3], $categories);
    }
}