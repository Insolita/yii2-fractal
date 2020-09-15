<?php

class ApiCategoryCest
{
    public function testActionWithInvalidConfiguration(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/category/bad-config');
        $I->seeResponseCodeIsServerError();
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiError();
        $I->seeResponseHasMeta();
        $I->seeResponseContainsJson(['errors'=>[['title'=>'Invalid Configuration']]]);
        $I->seeResponseContainsJson(['meta' => ['error_type'=> "yii\\base\\InvalidConfigException"]]);
    }

//    public function testHeadRequest(ApiTester $I)
//    {
//        $I->haveValidContentType();
//        $I->sendHEAD('/category');
//        $I->seeResponseCodeIsSuccessful();
//    }

    public function testOptionsRequest(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendOPTIONS('/category');
        $I->seeResponseCodeIsSuccessful();
    }

    public function testViewAction(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/category/1');
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiResource();
        $I->seeResponseEqualsPretty([
            'data'=>[
                'type'=>'category',
                'id'=>'1',
                'attributes' => ['name' => 'Apple', 'active'=>true],
                'links'=>['self'=> "http://127.0.0.1:80/category/1"]
            ]
        ]);
    }

    public function testViewActionWithCustomFields(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/category/1',['fields'=>['category'=>'name']]);
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiResource();
        $I->seeResponseEqualsPretty([
            'data'=>[
                'type'=>'category',
                'id'=>'1',
                'attributes' => ['name' => 'Apple'],
                'links'=>['self'=> "http://127.0.0.1:80/category/1"]
            ]
        ]);
    }

    public function testViewActionWithCustomUnexistedFields(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->expect('Unexisted fields should be ignored');
        $I->sendGET('/category/1',['fields'=>['category'=>'name,author']]);
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiResource();
        $I->seeResponseEqualsPretty([
            'data'=>[
                'type'=>'category',
                'id'=>'1',
                'attributes' => ['name' => 'Apple'],
                'links'=>['self'=> "http://127.0.0.1:80/category/1"]
            ]
        ]);
    }
    public function testViewActionWithEmptyFields(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/category/1',['fields'=>['category'=>'']]);
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiResource();
        $I->seeResponseEqualsPretty([
            'data'=>[
                'type'=>'category',
                'id'=>'1',
                'attributes' => (object)[],
                'links'=>['self'=> "http://127.0.0.1:80/category/1"]
            ]
        ]);
    }

    public function testListAction(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/category');
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $I->seeResponseHasMetaPagination();
        $I->dontSeeResponseHasIncludes();
        $I->dontSeeResponseHasRelationships();
        $I->seeResponseContainsJson([
            'data'=>[
                'type'=>'category',
                'id'=>'3',
                'attributes' => ['name' => 'Orange'],
                'links'=>['self'=> "http://127.0.0.1:80/category/3"]
            ]
        ]);
        $resp = $I->grabDataFromResponseByJsonPath('$.data[*].id');
        $I->assertEquals([1,2,3,4], $resp);
        $I->seeResponseContainsJson([
            'pagination'=>[
                'total'=>4,
                'count'=>4,
                'current_page'=>1,
                'total_pages'=>1,
            ]
        ]);
        $firstLink = $I->grabDataFromResponseByJsonPath('$.links.first');
        $selfLink = $I->grabDataFromResponseByJsonPath('$.links.self');
        $lastLink = $I->grabDataFromResponseByJsonPath('$.links.last');
        $I->assertEquals($firstLink[0], $selfLink[0]);
        $I->assertEquals($lastLink[0], $selfLink[0]);
        $I->dontSeeResponseJsonMatchesJsonPath('$.links.next');
        $I->dontSeeResponseJsonMatchesJsonPath('$.links.prev');
    }

    public function testListActionWithSort(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/category', ['sort'=>'-name']);
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiCollection();
        $resp = $I->grabDataFromResponseByJsonPath('$.data[*].id');
        $I->assertEquals([4,3,2,1], $resp);
        $I->seeResponseContainsJson([
            'pagination'=>[
                'total'=>4,
                'count'=>4,
                'current_page'=>1,
                'total_pages'=>1,
            ]
        ]);
        $selfLink = $I->grabDataFromResponseByJsonPath('$.links.self');
        $I->assertContains('sort=-name', $selfLink[0]);
    }

    public function testCreateInvalidFormat1(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendPOST('/category', [
            'name'=>'Banana',
            'active'=>'bar'
        ]);
        $I->expect('validation  exception should be thrown');
        $I->seeResponseCodeIs(422);
        $I->seeResponseIsJsonApiError();
        $I->seeResponseEqualsPretty([
            'meta'=>['type'=>'Validation Errors'],
            'errors'=>[
                ['status'=>422, 'source'=>['attribute' => 'name'], 'detail'=>'Name cannot be blank.'],
            ]
        ]);
    }

    public function testCreateInvalidFormat2(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendPOST('/category', [
            'data'=>[
                'name'=>'Banana',
                'active'=>'bar'
            ]
        ]);
        $I->expect('validation  exception should be thrown');
        $I->seeResponseCodeIs(422);
        $I->seeResponseIsJsonApiError();
        $I->seeResponseEqualsPretty([
            'meta'=>['type'=>'Validation Errors'],
            'errors'=>[
                ['status'=>422, 'source'=>['attribute' => 'name'], 'detail'=>'Name cannot be blank.'],
            ]
        ]);
    }

    public function testCreateInvalidData(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendPOST('/category', [
            'data'=>[
                'type'=>'category',
                'attributes'=>[
                    'name'=>'Banana',
                    'active'=>'bar'
                ]
            ]
        ]);
        $I->expect('validation  exception should be thrown');
        $I->seeResponseCodeIs(422);
        $I->seeResponseIsJsonApiError();
        $I->seeResponseEqualsPretty([
            'meta'=>['type'=>'Validation Errors'],
            'errors'=>[
                ['status'=>422, 'source'=>['attribute' => 'name'], 'detail'=>'Name "Banana" has already been taken.'],
                ['status'=>422, 'source'=>['attribute' => 'active'], 'detail'=>'Active must be either "1" or "0".']
            ]
        ]);
    }

    public function testCreateValid(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendPOST('/category', [
            'data'=>[
                'type'=>'category',
                'attributes'=>[
                    'name'=>'Peach',
                    'active'=>1
                ]
            ]
        ]);
        $I->seeResponseCodeIs(201);
        $loc = $I->grabHttpHeader('Location');
        $selfLink = $I->grabDataFromResponseByJsonPath('$.data.links.self');
        $I->assertEquals($selfLink[0], $loc);
        $I->seeResponseContainsJson([
            'data'=>[
                'type'=>'category',
                'attributes'=>['name' => 'Peach']
            ]
        ]);
    }

    public function testUpdateUnexisted(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendPATCH('/category/200', [
            'data'=>[
                'id'=>200,
                'type'=>'category',
                'attributes'=>[
                    'name'=>'Melon'
                ]
            ]
        ]);
        $I->seeResponseCodeIs(404);
    }

    public function testUpdateInvalid(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendPATCH('/category/2', [
            'data'=>[
                'id'=>2,
                'type'=>'category',
                'attributes'=>[
                    'name'=>'Melon',
                    'active'=>'fooo'
                ]
            ]
        ]);
        $I->seeResponseCodeIs(422);
    }

    public function testUpdateValid(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendPATCH('/category/2', [
            'data'=>[
                'id'=>2,
                'type'=>'category',
                'attributes'=>[
                    'name'=>'Melon',
                ]
            ]
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'data'=>[
                'type'=>'category',
                'id' => '2',
                'attributes'=>['name' => 'Melon']
            ]
        ]);
    }

    public function testDelete(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendDELETE('/category/2', [
            'data'=>[
                'id'=>2,
                'type'=>'category',
            ]
        ]);
        $I->seeResponseCodeIs(204);
    }

    public function testDeleteWithAttrs(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendDELETE('/category/1', [
            'data'=>[
                'id'=>1,
                'type'=>'category',
                'attributes'=>[
                    'name'=>'Melon',
                ]
            ]
        ]);
        $I->seeResponseCodeIs(204);
    }

    public function testDeleteNoBody(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendDELETE('/category/3');
        $I->seeResponseCodeIs(204);
    }

    public function testDeleteUnexisted(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendDELETE('/category/200', [
            'data'=>[
                'id'=>200,
                'type'=>'category',
            ]
        ]);
        $I->seeResponseCodeIs(404);
    }

}