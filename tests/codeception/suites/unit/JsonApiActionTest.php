<?php
use app\models\User;
use app\transformers\UserTransformer;
use Codeception\AssertThrows;
use Codeception\Test\Unit;
use insolita\fractal\actions\JsonApiAction;
use insolita\fractal\actions\ViewAction;
use insolita\fractal\DefaultTransformer;
use insolita\fractal\JsonApiController;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\web\Controller;

class JsonApiActionTest extends Unit
{
    use AssertThrows;

    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testInit()
    {
        $this->assertThrows(InvalidConfigException::class,
            function() {
                $controller = new class ('test', 'default') extends JsonApiController {
                };
                new ViewAction('test', $controller);
            });
        $this->assertThrows(InvalidCallException::class,
            function() {
                $controller = new class ('test', 'default') extends Controller {
                };
                new ViewAction('test', $controller, [
                    'modelClass' => User::class,
                ]);
            });
        $this->assertThrows(InvalidConfigException::class,
            function() {
                $controller = new class ('test', 'default') extends JsonApiController {
                };
                new ViewAction('test', $controller, [
                    'modelClass' => User::class,
                    'parentIdParam' => 'some_id',
                ]);
            });
        $this->assertThrows(InvalidConfigException::class,
            function() {
                $controller = new class ('test', 'default') extends JsonApiController {
                };
                new ViewAction('test', $controller, [
                    'modelClass' => User::class,
                    'parentIdAttribute' => 'some_id',
                ]);
            });

        $controller = new class ('test', 'default') extends JsonApiController {
        };
        $action = new JsonApiAction('test', $controller, [
            'modelClass' => User::class,
        ]);
        expect($action)->isInstanceOf(JsonApiAction::class);
    }

    public function testInitWithTransformer()
    {
        $controller = new class ('test', 'default') extends JsonApiController {
        };
        $action = new ViewAction('test', $controller, [
            'modelClass' => User::class,
        ]);
        expect($action->transformer)->isInstanceOf(DefaultTransformer::class);
        expect($action->resourceKey)->equals('users');

        $action = new ViewAction('test', $controller, [
            'modelClass' => User::class,
            'resourceKey'=>'me',
            'transformer'=>UserTransformer::class
        ]);
        expect($action->transformer)->isInstanceOf(UserTransformer::class);
        expect($action->resourceKey)->equals('me');

        $this->assertThrows(InvalidConfigException::class,
            function() {
                $controller = new class ('test', 'default') extends JsonApiController {
                };
                new ViewAction('test', $controller, [
                    'modelClass' => User::class,
                    'transformer' => new class(){}
                ]);
            });
    }
}