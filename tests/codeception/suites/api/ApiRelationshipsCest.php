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
        $I->seeResponseContainsJson(['data'=>['type'=>'users', 'id'=>1]]);
    }

    public function testPostCommentsEmptyRelatioship(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/posts/1/relationships/comments');
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->dontSeeResponseHasIncludes();
        $I->dontSeeResponseHasRelationships();
        $I->seeResponseHasMetaCursor();
        $I->seeResponseContainsJson(['data'=>[]]);
    }

    public function testPostCommentsRelationshipList(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/posts/11/relationships/comments');
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseHasMetaCursor();
        $I->dontSeeResponseHasIncludes();
        $I->dontSeeResponseHasRelationships();
        $I->seeResponseContainsJson(['data'=>[['type'=>'comments']]]);
    }
    public function testViewRelationshipsCategory(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Delta_secret_token');
        $I->sendGET('/posts/1/relationships/category');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJsonApiResource();
        $I->dontSeeResponseHasMeta();
    }

    public function testDeleteRelationshipCategory(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Delta_secret_token');
        $I->sendDELETE('/posts/1/relationships/category');
        $I->seeResponseIsJsonApiError();
    }

    public function testViewRelationshipsComments(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Delta_secret_token');
        $I->sendGET('/posts/26/relationships/comments');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseContainsJson([
            'data'=>[
                ['type'=>'comments'],
            ]
        ]);
        $I->seeResponseHasMetaCursor();
    }

    public function testDeleteRelationshipComments(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Delta_secret_token');

        $I->seeInDatabase('public.comments', ['post_id'=>12, 'id' => 19]);
        $I->seeInDatabase('public.comments', ['post_id'=>12, 'id' => 21]);
        $I->sendDELETE('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> 19, 'type' => 'comments'],
                ['id'=> 21, 'type' => 'comments'],
            ]
        ]);
        $I->seeResponseCodeIs(204);
        $I->dontSeeInDatabase('public.comments', ['post_id'=>12, 'id' => 19]);
        $I->dontSeeInDatabase('public.comments', ['post_id'=>12, 'id' => 21]);
        $I->seeInDatabase('public.comments', ['post_id'=>null, 'id' => 19]);

        $I->sendDELETE('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> 1, 'type' => 'comments'],
                ['id'=> 2, 'type' => 'comments'],
            ]
        ]);
        $I->seeResponseIsJsonApiError();

        $I->expectTo('Wrong id types should be prevented');
        $I->sendDELETE('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> '', 'type' => 'comments'],
            ]
        ]);
        $I->seeResponseCodeIs(422);
        $I->sendDELETE('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> null, 'type' => 'comments'],
            ]
        ]);
        $I->seeResponseCodeIs(422);
        $I->sendDELETE('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> [], 'type' => 'comments'],
            ]
        ]);
        $I->seeResponseCodeIs(422);
    }

    public function testCreateRelationshipComments(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Delta_secret_token');
        $id1 = $I->haveInDatabase(
            'public.comments',
            ['post_id'=>null, 'user_id' => 1, 'message' => 'Some comment', 'created_at'=>'2020-01-01 01:02:03']
        );
        $id2 = $I->haveInDatabase(
            'public.comments',
            ['post_id'=>null, 'user_id' => 2, 'message' => 'Some comment222', 'created_at'=>'2020-01-01 01:02:03']
        );
        $id3 = $I->haveInDatabase(
            'public.comments',
            ['post_id'=>null, 'user_id' => 3, 'message' => 'Some comment333', 'created_at'=>'2020-01-01 01:02:03']
        );
        $I->sendPOST('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> $id1, 'type' => 'comments'],
                ['id'=> $id2, 'type' => 'comments'],
            ]
        ]);
        $I->seeInDatabase('public.comments', ['post_id' => 12, 'id' => $id1]);
        $I->seeInDatabase('public.comments', ['post_id' => 12, 'id' => $id2]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJsonApiResource();
        $I->expectTo('Only new relations should be attached');
        $I->sendPOST('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> $id2, 'type' => 'comments'],
                ['id'=> $id3, 'type' => 'comments'],
            ]
        ]);
        $I->seeInDatabase('public.comments', ['post_id' => 12, 'id' => $id1]);
        $I->seeInDatabase('public.comments', ['post_id' => 12, 'id' => $id2]);
        $I->seeInDatabase('public.comments', ['post_id' => 12, 'id' => $id3]);

        $I->expectTo('Wrong id types should be prevented');
        $I->sendPOST('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> '', 'type' => 'comments'],
            ]
        ]);
        $I->seeResponseCodeIs(422);
        $I->sendPOST('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> null, 'type' => 'comments'],
            ]
        ]);
        $I->seeResponseCodeIs(422);
        $I->sendPOST('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> [], 'type' => 'comments'],
            ]
        ]);
        $I->seeResponseCodeIs(422);
    }

    public function testUpdateRelationshipComments(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Delta_secret_token');
        $id1 = $I->haveInDatabase(
            'public.comments',
            ['post_id'=>null, 'user_id' => 1, 'message' => 'Some comment', 'created_at'=>'2020-01-01 01:02:03']
        );
        $id2 = $I->haveInDatabase(
            'public.comments',
            ['post_id'=>null, 'user_id' => 2, 'message' => 'Some comment222', 'created_at'=>'2020-01-01 01:02:03']
        );
        $id3 = $I->haveInDatabase(
            'public.comments',
            ['post_id'=>null, 'user_id' => 3, 'message' => 'Some comment333', 'created_at'=>'2020-01-01 01:02:03']
        );
        $I->seeInDatabase('public.comments', ['post_id'=>12, 'id' => 19]);
        $I->seeInDatabase('public.comments', ['post_id'=>12, 'id' => 21]);
        $I->sendPATCH('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> $id1, 'type' => 'comments'],
                ['id'=> $id2, 'type' => 'comments'],
            ]
        ]);
        $I->dontSeeInDatabase('public.comments', ['post_id'=>12, 'id' => 19]);
        $I->dontSeeInDatabase('public.comments', ['post_id'=>12, 'id' => 21]);
        $I->seeInDatabase('public.comments', ['post_id' => 12, 'id' => $id1]);
        $I->seeInDatabase('public.comments', ['post_id' => 12, 'id' => $id2]);
        $I->seeResponseCodeIs(204);

        $I->expectTo('Only new relations should be attached');
        $I->sendPATCH('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> $id2, 'type' => 'comments'],
                ['id'=> $id3, 'type' => 'comments'],
            ]
        ]);
        $I->dontSeeInDatabase('public.comments', ['post_id' => 12, 'id' => $id1]);
        $I->seeInDatabase('public.comments', ['post_id' => 12, 'id' => $id2]);
        $I->seeInDatabase('public.comments', ['post_id' => 12, 'id' => $id3]);
        $I->seeResponseCodeIs(204);

        $I->expectTo('Wrong id types should be prevented');
        $I->sendPATCH('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> '', 'type' => 'comments'],
            ]
        ]);
        $I->seeResponseCodeIs(422);
        $I->sendPATCH('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> null, 'type' => 'comments'],
            ]
        ]);
        $I->seeResponseCodeIs(422);
        $I->sendPATCH('/posts/12/relationships/comments', [
            'data'=>[
                ['id'=> [], 'type' => 'comments'],
            ]
        ]);
        $I->seeResponseCodeIs(422);
    }
}
