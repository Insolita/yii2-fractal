<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class ApiTester extends \Codeception\Actor
{
    use _generated\ApiTesterActions;

    public function haveValidContentType()
    {
        $this->haveHttpHeader('Content-Type', "application/vnd.api+json");
        $this->haveHttpHeader('Accept', "application/vnd.api+json");
    }
    public function seeContentTypeIsBySpec()
    {
        $this->canSeeHttpHeader('Content-Type', "application/vnd.api+json; charset=UTF-8");
    }

    public function seeResponseIsJsonApiResource()
    {
        $this->seeResponseJsonMatchesJsonPath('$.data.type');
        $this->seeResponseJsonMatchesJsonPath('$.data.id');
        $this->seeResponseJsonMatchesJsonPath('$.data.attributes');
    }

    public function seeResponseHasRelationships()
    {
        $this->seeResponseJsonMatchesJsonPath('$.data.relationships');
    }

    public function seeResponseCollectionHasRelationships()
    {
        $this->seeResponseJsonMatchesJsonPath('$.data[*].relationships');
    }

    public function seeResponseHasIncludes()
    {
        $this->seeResponseJsonMatchesJsonPath('$.included');
    }

    public function dontSeeResponseHasIncludes()
    {
        $this->dontSeeResponseJsonMatchesJsonPath('$.included');
    }

    public function dontSeeResponseHasRelationships()
    {
        $this->dontSeeResponseJsonMatchesJsonPath('$.data.relationships');
    }

    public function dontSeeResponseCollectionHasRelationships()
    {
        $this->dontSeeResponseJsonMatchesJsonPath('$.data[*].relationships');
    }

    public function seeResponseIsJsonApiError()
    {
        $this->seeResponseJsonMatchesJsonPath('$.errors[*]');
        $this->dontSeeResponseJsonMatchesJsonPath('$.data[*]');
        $this->dontSeeResponseJsonMatchesJsonPath('$.data');
    }

    public function seeResponseHasMeta()
    {
        $this->seeResponseJsonMatchesJsonPath('$.meta');
    }

    public function dontSeeResponseHasMeta()
    {
        $this->seeResponseJsonMatchesJsonPath('$.meta');
    }

    public function seeResponseHasMetaPagination()
    {
        $this->seeResponseJsonMatchesJsonPath('$.meta.pagination');
    }

    public function dontSeeResponseHasMetaPagination()
    {
        $this->dontSeeResponseJsonMatchesJsonPath('$.meta.pagination');
    }

    public function seeResponseIsJsonApiCollection()
    {
        $this->seeResponseJsonMatchesJsonPath('$.data[*].type');
        $this->seeResponseJsonMatchesJsonPath('$.data[*].id');
        $this->seeResponseJsonMatchesJsonPath('$.data[*].attributes');
    }

    public function seeResponseEqualsPretty($expected)
    {
        $expected = json_encode($expected, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
        $this->seeResponseEquals($expected);
    }
}
