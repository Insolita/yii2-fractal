<?php

namespace app\controllers;

use app\models\Category;
use app\models\User;
use insolita\fractal\DefaultTransformer;
use insolita\fractal\JsonApiController;
use insolita\fractal\JsonApiError;
use League\Fractal\Resource\Item;
use Yii;
use yii\base\NotSupportedException;
use yii\helpers\VarDumper;
use yii\web\ForbiddenHttpException;
use function json_decode;

class DefaultController extends JsonApiController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        return $behaviors;
    }

    public function actionIndex()
    {
        return new Item(['id' => $this->action->id, 'content' => 'hello world!'], new DefaultTransformer(), $this->id);
    }

    public function actionArray()
    {
        return ['foo' => 'bar', 'bar' => 'baz'];
    }

    public function actionString()
    {
        return 'some string output';
    }

    public function actionNull()
    {
        return null;
    }

    public function actionEmpty()
    {
        \str_replace('foo', 'bar', 'foobar');
    }

    public function actionModel()
    {
        return User::findOne(1);
    }

    public function actionJsonError()
    {
        return new JsonApiError(['title' => 'custom error', 'code' => 422, 'status' => 422, 'detail' => 'Just for test']);
    }

    public function actionForbidden()
    {
        throw new ForbiddenHttpException('Force thrown exception', 403);
    }

    public function actionException()
    {
        throw new NotSupportedException('Force thrown exception', 501);
    }

    public function actionFatal()
    {
        return json_decode(['wrong']);
    }

    public function actionMedia()
    {
        Yii::warning(VarDumper::dumpAsString(Yii::$app->request->getRawBody()));
        return null;
    }

}