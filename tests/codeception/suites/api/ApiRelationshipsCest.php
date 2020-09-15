<?php

namespace codeception\suites\api;

use ApiTester;

class ApiRelationshipsCest
{
    public function testPostCategoryRelationship(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/posts/1/relationships/category');
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiResource();
        $I->dontSeeResponseHasMetaPagination();
        $I->dontSeeResponseHasIncludes();
        $I->dontSeeResponseHasRelationships();
        $I->seeResponseContainsJson(['data'=>['type'=>'categories', 'id'=>1]]);
    }

    public function testPostAuthorRelationship(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/posts/1/relationships/author');
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiResource();
        $I->dontSeeResponseHasMetaPagination();
        $I->dontSeeResponseHasIncludes();
        $I->dontSeeResponseHasRelationships();
        $I->seeResponseContainsJson(['data'=>['type'=>'authors', 'id'=>1]]);
    }

    public function testPostCommentsEmptyRelatioship(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/posts/1/relationships/comments');
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseHasMetaPagination();
        $I->dontSeeResponseHasIncludes();
        $I->dontSeeResponseHasRelationships();
        $I->seeResponseContainsJson(['data'=>[]]);
        $I->seeResponseContainsJson(['pagination'=>['total' => 0]]);
    }

    public function testPostCommentsRelationshipList(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/posts/11/relationships/comments');
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseHasMetaPagination();
        $I->dontSeeResponseHasIncludes();
        $I->dontSeeResponseHasRelationships();
        $I->seeResponseContainsJson(['data'=>[['type'=>'comments']]]);
    }

}