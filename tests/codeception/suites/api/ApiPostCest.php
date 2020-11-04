<?php

use app\models\Comment;
use Codeception\Util\Debug;

class ApiPostCest
{
    public function testListHasDataWithRelationships(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/post');
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseHasMetaPagination();
        $I->dontSeeResponseHasIncludes();
        $I->dontSeeResponseHasRelationships();
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.author.links');
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.category.links');
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.comments.links');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data[*].relationships.author.data');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data[*].relationships.category.data');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data[*].relationships.comments.data');
        $I->seeResponseContainsJson([
            'pagination'=>['total' => 54, 'count'=>20, 'per_page'=>20, 'current_page'=>1, 'total_pages'=>3]
        ]);
        $I->seeResponseJsonMatchesJsonPath('$.links.next');
        $I->seeResponseJsonMatchesJsonPath('$.links.self');
        $I->seeResponseJsonMatchesJsonPath('$.links.first');
        $I->seeResponseJsonMatchesJsonPath('$.links.last');
    }

    public function testListFilterWithNotAllowedAttrs(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/post', ['filter'=>['created_at'=>['eq'=>'2012-11-10']]]);
        $I->seeResponseCodeIs(422);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseContainsJson([
            'detail'=>'Unknown filter attribute "created_at"'
        ]);
    }

    public function testListWithFilter(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/post', ['filter'=>['category_id'=>['neq'=>1]]]);
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseHasMetaPagination();
        $I->dontSeeResponseHasIncludes();
        $I->dontSeeResponseHasRelationships();
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.author.links');
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.category.links');
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.comments.links');
        $total = $I->grabDataFromResponseByJsonPath('$.meta.pagination.total');
        $I->assertLessThan(54, $total[0]);
        $I->assertGreaterThan(0, $total[0]);
        $I->seeResponseJsonMatchesJsonPath('$.links.next');
        $I->seeResponseJsonMatchesJsonPath('$.links.self');
        $I->seeResponseJsonMatchesJsonPath('$.links.first');
        $I->seeResponseJsonMatchesJsonPath('$.links.last');

        $I->sendGET('/post', ['filter'=>['category_id'=>['neq'=>1], 'author_id'=>['eq' => 1]]]);
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseHasMetaPagination();
        $I->dontSeeResponseHasIncludes();
        $I->dontSeeResponseHasRelationships();
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.author.links');
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.category.links');
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.comments.links');
        $total2 = $I->grabDataFromResponseByJsonPath('$.meta.pagination.total');
        $I->assertLessThan($total[0], $total2[0]);
        $I->assertGreaterThan(0, $total2[0]);
    }

    public function testListWithInclude(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/post', [
            'filter'=>['category_id'=>['neq'=>1], 'author_id'=>['eq' => 1]],
            'include'=>'category'
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseHasMetaPagination();
        $I->dontSeeResponseHasRelationships();
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.author.links');
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.category.links');
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.comments.links');
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.category.data');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data[*].relationships.author.data');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data[*].relationships.comments.data');
        $I->seeResponseHasIncludes();
        $I->seeResponseContainsJson(['included'=>[['type'=>'category']]]);

        $I->sendGET('/post', [
            'filter'=>['category_id'=>['neq'=>1], 'author_id'=>['eq' => 1]],
            'include'=>'comments'
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseHasMetaPagination();
        $I->dontSeeResponseHasRelationships();
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.author.links');
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.category.links');
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.comments.links');
        $I->seeResponseJsonMatchesJsonPath('$.data[*].relationships.comments.data');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data[*].relationships.author.data');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data[*].relationships.category.data');
        $I->seeResponseHasIncludes();
        $I->dontSeeResponseContainsJson(['included'=>[['type'=>'category']]]);
        $I->seeResponseContainsJson(['included'=>[['type'=>'comments']]]);
    }

    public function testListForUser(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/users/1/posts', ['page'=>['size' => 5], 'sort'=>'name']);
        $I->seeResponseCodeIsSuccessful();
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseHasMetaPagination();
        $authors = $I->grabDataFromResponseByJsonPath('$.data[*].attributes.author_id');
        $I->assertEquals([1, 1, 1, 1, 1], $authors);
    }

    public function testListWithJoins(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/posts-join', ['sort'=>'-category.name', 'include'=>['category']]);
        $I->seeResponseCodeIsSuccessful();
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseHasMetaPagination();
        $I->seeResponseContainsJson([
            'pagination'=>['total' => 54, 'count'=>20, 'per_page'=>20, 'current_page'=>1, 'total_pages'=>3]
        ]);
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

    public function testListForCategory(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/categories/3/posts', ['page'=>['size' => 5], 'sort'=>'name']);
        $I->seeResponseCodeIsSuccessful();
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseHasMetaPagination();
        $categories = $I->grabDataFromResponseByJsonPath('$.data[*].attributes.category_id');
        $I->assertEquals([3, 3, 3, 3, 3], $categories);
    }

    public function testViewForCategoryWrongPost(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/categories/3/posts/1');

        $I->seeResponseCodeIs(404);
    }

    public function testViewForCategory(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/categories/1/posts/1');
        $I->seeResponseCodeIsSuccessful();
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiResource();
        $I->dontSeeResponseHasMetaPagination();
        $I->dontSeeResponseHasIncludes();
        $I->seeResponseHasRelationships();
        $category = $I->grabDataFromResponseByJsonPath('$.data.attributes.category_id')[0];
        $I->assertEquals(1, $category);
    }

    public function testDeleteForCategory(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendDELETE('/categories/1/posts/1');
        $I->seeResponseCodeIs(204);

        $I->sendDELETE('/categories/1/posts/11');
        $I->seeResponseCodeIs(404);
    }

    public function testCreateForCategory(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Delta_secret_token');
        $I->sendPOST('/categories/3/posts', [
            'data'=>[
                'type'=>'posts',
                'attributes'=>[
                    'name'=>'MyNewPost',
                    'body'=>'Bla-bla'
                ]
            ]
        ]);
        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson([
            'data'=>[
                'type'=>'posts',
                'attributes'=>['name'=>'MyNewPost', 'body'=>'Bla-bla', 'category_id' =>3, 'author_id'=>4]
            ]
        ]);

        $I->sendPOST('/categories/3/posts', [
            'data'=>[
                'type'=>'posts',
                'attributes'=>[
                    'name'=>'MySecondPost',
                    'category_id' =>1,
                    'author_id' =>1,
                    'body'=>'Bla-bla'
                ]
            ]
        ]);
        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson([
            'data'=>[
                'type'=>'posts',
                'attributes'=>['name'=>'MySecondPost', 'body'=>'Bla-bla', 'category_id' =>3, 'author_id'=>4]
            ]
        ]);
    }

    public function testUpdateForCategory(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Delta_secret_token');
        $I->sendPATCH('/categories/3/posts/11', [
            'data'=>[
                'type'=>'posts',
                'attributes'=>[
                    'name'=>'Updated Title',
                ]
            ]
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'data'=>[
                'type'=>'posts',
                'attributes'=>['name'=>'Updated Title',  'category_id' =>3]
            ]
        ]);
    }

    public function testUpdateForCategoryAndChangeIt(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Delta_secret_token');
        $I->sendPATCH('/categories/3/posts/11', [
            'data'=>[
                'type'=>'posts',
                'attributes'=>[
                    'name'=>'Updated Title',
                    'category_id'=>2
                ]
            ]
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'data'=>[
                'type'=>'posts',
                'attributes'=>['name'=>'Updated Title', 'category_id' =>2]
            ]
        ]);
    }

    public function testCreateWithLinkRelationship(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Delta_secret_token');
        $id1 = $I->haveInDatabase(
            'public.comments',
            ['post_id'=>null, 'user_id' => 1, 'message' => 'Some comment', 'created_at'=>'2020-09-09 01:02:03']
        );
        $id2 = $I->haveInDatabase(
            'public.comments',
            ['post_id'=>null, 'user_id' => 2, 'message' => 'Some comment222', 'created_at'=>'2020-08-08 01:02:03']
        );
        $I->sendPOST('/posts2', [
            'data'=>[
                'type'=>'posts',
                'attributes'=>[
                    'name'=>'My post with linked comments',
                    'body'=>'Bla-bla',
                    'category_id' =>1
                ], 'relationships'=>[
                   'comments' => [
                       'data' => [
                           ['id'=> $id1, 'type' => 'comments'],
                           ['id'=> $id2, 'type' => 'comments'],
                       ]
                   ]
                ]
            ]
        ]);
        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJsonApiResource();
        $postId = $I->grabFromDatabase('public.posts', 'id', ['name' => 'My post with linked comments']);
        $I->seeInDatabase('public.comments', ['post_id' => $postId, 'id' => $id1]);
        $I->seeInDatabase('public.comments', ['post_id' => $postId, 'id' => $id2]);

        $I->expect('Create with unsupported relation should throw error');
        $I->sendPOST('/posts2', [
            'data'=>[
                'type'=>'posts',
                'attributes'=>[
                    'name'=>'My post with linked comments',
                    'body'=>'Bla-bla'
                ], 'relationships'=>[
                    'author' => ['type' => 'author', 'id' =>2],
                    'comments' => [
                        'data' => [
                            ['id'=> $id1, 'type' => 'comments'],
                            ['id'=> $id2, 'type' => 'comments'],
                        ]
                    ]
                ]
            ]
        ]);
        $I->seeResponseCodeIs(403);
    }

    public function testUpdateWithLinkRelationship(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->amBearerAuthenticated('Delta_secret_token');
        $id1 = $I->haveInDatabase(
            'public.comments',
            ['post_id'=>null, 'user_id' => 1, 'message' => 'One comment', 'created_at'=>'2020-03-02 01:02:03']
        );
        $id2 = $I->haveInDatabase(
            'public.comments',
            ['post_id'=>null, 'user_id' => 2, 'message' => 'Second comment222', 'created_at'=>'2020-03-03 01:02:03']
        );
        $id3 = $I->haveInDatabase(
            'public.comments',
            ['post_id'=>null, 'user_id' => 3, 'message' => 'Third comment333', 'created_at'=>'2020-03-04 01:02:03']
        );

        $I->sendPATCH('/posts2/12', [
            'data' => [
                'type'=>'posts',
                'attributes'=>[
                    'name'=>'My changed post with linked comments',
                ], 'relationships'=>[
                    'comments' => [
                       'data' => [
                           ['id'=> $id1, 'type' => 'comments'],
                           ['id'=> $id2, 'type' => 'comments'],
                       ]
                    ]
                ]
            ]
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJsonApiResource();

        $I->seeInDatabase('public.comments', ['post_id' => 12, 'id' => $id1]);
        $I->seeInDatabase('public.comments', ['post_id' => 12, 'id' => $id2]);
        $I->dontSeeInDatabase('public.comments', ['post_id' => 12, 'id' => $id3]);

        $name = $I->grabFromDatabase('public.posts', 'name', ['id' => 12]);
        $I->assertEquals('My changed post with linked comments', $name);
        //$I->seeInDatabase('public.posts', ['id' => 12, 'name' => 'My changed post with linked comments']);

        $name = $I->grabFromDatabase('public.posts', 'name', ['id' => 12]);
        $I->wantToTest('Patch relationships without attributes should be ok');
        $I->sendPATCH('/posts2/12', [
            'data' => [
                'type'=>'posts',
                'attributes'=>[],
                'relationships'=>[
                    'comments' => [
                        'data' => [
                            ['id'=> $id3, 'type' => 'comments'],
                        ]
                    ]
                ]
            ]
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJsonApiResource();
        $I->seeInDatabase('public.posts', ['id' => 12, 'name' => $name]);
        $I->dontSeeInDatabase('public.comments', ['post_id' => 12, 'id' => $id1]);
        $I->dontSeeInDatabase('public.comments', ['post_id' => 12, 'id' => $id2]);
        $I->seeInDatabase('public.comments', ['post_id' => 12, 'id' => $id3]);
    }
}