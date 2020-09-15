<?php

class ApiDefaultCest
{
    public function testActionWithManualResource(ApiTester $I)
    {
        $I->haveValidContentType();
        $I->sendGET('/defaults');
        $I->seeResponseCodeIs(200);
        $I->seeContentTypeIsBySpec();
        $I->seeResponseIsJsonApiResource();
        $I->seeResponseContainsJson(['content' => 'hello world!']);
    }

    public function testActionThatReturnArray(ApiTester $I)
    {
        $I->sendGET('/default/array');
        $I->expectTo('actions must return only Resources, Exceptions, JsonApiError, or nothing');
        $I->seeContentTypeIsBySpec();
        $I->seeResponseCodeIsServerError();
        $I->seeResponseIsJsonApiError();
        $I->seeResponseContainsJson(['details' => 'Response data is not followed JsonApi spec']);
    }

    public function testActionThatReturnString(ApiTester $I)
    {
        $I->sendGET('/default/string');
        $I->expectTo('actions must return only Resources, Exceptions, JsonApiError, or nothing');
        $I->seeContentTypeIsBySpec();
        $I->seeResponseCodeIsServerError();
        $I->seeResponseIsJsonApiError();
        $I->seeResponseContainsJson(['details' => 'Response data is not followed JsonApi spec']);
    }

    public function testActionThatReturnModel(ApiTester $I)
    {
        $I->sendGET('/default/model');
        $I->expectTo('actions must return only Resources, Exceptions, JsonApiError, or nothing');
        $I->seeContentTypeIsBySpec();
        $I->seeResponseCodeIsServerError();
        $I->seeResponseIsJsonApiError();
        $I->seeResponseContainsJson(['details' => 'Response data is not followed JsonApi spec']);
    }

    public function testActionThatReturnNull(ApiTester $I)
    {
        $I->sendGET('/default/null');
        $I->expectTo('actions should return empty response');
        $I->seeContentTypeIsBySpec();
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseHasMeta();
        $I->seeResponseContainsJson(['meta' => ['type'=>'Empty response']]);
    }

    public function testActionThatReturnNothing(ApiTester $I)
    {
        $I->sendGET('/default/empty');
        $I->expectTo('actions should return empty response');
        $I->seeContentTypeIsBySpec();
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseHasMeta();
        $I->seeResponseContainsJson(['meta' => ['type'=>'Empty response']]);
    }

    public function testActionThatReturnJsonApiError(ApiTester $I)
    {
        $I->sendGET('/default/json-error');
        $I->seeContentTypeIsBySpec();
        $I->seeResponseCodeIs(422);
        $I->seeResponseIsJsonApiError();
        $I->seeResponseContainsJson(['title' => 'custom error', 'details' => 'Just for test']);
    }

    public function testForbiddenAction(ApiTester $I)
    {
        $I->sendGET('/default/forbidden');
        $I->seeContentTypeIsBySpec();
        $I->seeResponseCodeIs(403);
        $I->seeResponseIsJsonApiError();
        $I->seeResponseHasMeta();
        $I->seeResponseContainsJson(['title' => 'Forbidden', 'details' => 'Force thrown exception']);
        $I->seeResponseContainsJson(['meta' => ['error_type' => "yii\\web\\ForbiddenHttpException"]]);
    }

    public function testActionWithNotSupportedException(ApiTester $I)
    {
        $I->sendGET('/default/exception');
        $I->seeContentTypeIsBySpec();
        $I->seeResponseCodeIsServerError();
        $I->seeResponseIsJsonApiError();
        $I->seeResponseHasMeta();
        $I->seeResponseContainsJson(['title' => 'Not Supported', 'details' => 'Force thrown exception']);
        $I->seeResponseContainsJson(['meta' => ['error_type' => "yii\\base\\NotSupportedException"]]);
    }

    public function testActionWithInternalError(ApiTester $I)
    {
        $I->sendGET('/default/fatal');
        $I->seeContentTypeIsBySpec();
        $I->seeResponseCodeIsServerError();
        $I->seeResponseIsJsonApiError();
        $I->seeResponseHasMeta();
        $I->seeResponseContainsJson(['title' => 'PHP Warning', 'details' => 'json_decode() expects parameter 1 to be string, array given']);
        $I->seeResponseContainsJson(['meta' => ['error_type' => "yii\\base\\ErrorException"]]);
    }
}