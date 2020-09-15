<?php

class ApiUserCest
{
    public function testInfoWithoutAuth(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/me');
        $I->seeResponseCodeIs(401);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiError();
        $I->seeResponseContainsJson(['title' => 'Unauthorized']);
    }

    public function testAuthAndInfo(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Alpha_secret_token');
        $I->sendGET('/me');
        $I->seeResponseCodeIsSuccessful();
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiResource();
        $I->seeResponseEqualsPretty([
            'data'=>[
                'type'=>'me',
                'id' => '1',
                'attributes' => ['username' => 'Alpha', 'email'=>'Alpha@mail.com'],
                'links'=>['self'=> "http://127.0.0.1:80/me", 'details'=>"http://127.0.0.1:80/me/details"]
            ]
        ]);
    }

    public function testAuthAndDetails(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Alpha_secret_token');
        $I->sendGET('/me/details');
        $I->seeResponseCodeIsSuccessful();
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiResource();
        $I->seeResponseContainsJson([
            'data'=>[
                'type'=>'me',
                'id' => '1',
                'attributes' => ['username' => 'Alpha', 'email'=>'Alpha@mail.com', 'created_at' => '2012-03-12 07:01:52'],
                'links'=>['self'=> "http://127.0.0.1:80/me", 'details'=>"http://127.0.0.1:80/me/details"]
            ]
        ]);
        $I->seeResponseHasRelationships();
        $I->seeResponseJsonMatchesJsonPath('$.data.relationships.posts.data[*].id');
        $I->seeResponseJsonMatchesJsonPath('$.data.relationships.posts.data[*].type');
        $I->seeResponseJsonMatchesJsonPath('$.data.relationships.comments.links');
        $I->seeResponseHasIncludes();
        $I->seeResponseContainsJson([
            'included'=>[
               [ 'type'=>'posts', 'id'=>1]
            ]
        ]);

    }
}