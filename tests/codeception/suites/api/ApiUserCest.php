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

    public function testMyPostsListAction(ApiTester  $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Alpha_secret_token');
        $I->sendGET('/me/my-posts');
        $I->seeResponseCodeIsSuccessful();
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseContainsJson([
            'data'=>[
                'type'=>'posts',
                'id' => '1',
                'attributes' => ['author_id' => 1]
            ]
        ]);
    }

    public function testMyLastCommentAction(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Gamma_secret_token');
        $I->sendGET('/me/last-comment');
        $I->seeResponseCodeIsSuccessful();
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiResource();
        $I->seeResponseContainsJson([
            'data'=>[
                'type'=>'comments',
                'id' => '111',
                'attributes' => ['user_id' => 3, 'message' => 'Last comment for 3 user']
            ]
        ]);
    }

    public function testMyCommentAction(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Gamma_secret_token');
        $I->sendGET('/me/comment/109');
        $I->seeResponseCodeIsSuccessful();
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiResource();
        $I->seeResponseContainsJson([
            'data'=>[
                'type'=>'comments',
                'id' => '109',
                'attributes' => [
                    'user_id' => 3,
                    'message' => 'Previous comment for 3 user'
                ]
            ]
        ]);

        $I->sendGET('/me/comment/110');
        $I->seeResponseCodeIs(404);
    }

}