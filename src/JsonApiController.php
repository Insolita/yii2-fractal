<?php

namespace insolita\fractal;

use insolita\fractal\providers\JsonApiDataProviderInterface;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\ContentNegotiator;
use yii\helpers\Url;
use yii\rest\Serializer;
use yii\web\Controller;
use yii\web\Response;

class JsonApiController extends Controller
{
    /**
     * @var string|array the configuration for creating the serializer that formats the response data.
     */
    public $serializer = Serializer::class;
    public $enableCsrfValidation = false;
    /**
     * @var Manager $manager
    */
    public $manager;

    public function init()
    {
        parent::init();
        $this->manager = (new Manager())->setSerializer(new JsonApiSerializer(Yii::$app->homeUrl));
    }

    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/vnd.api+json' => Response::FORMAT_JSON,
                ],
            ],
            'authenticator' => [
                'class' => CompositeAuth::class,
            ],
        ];
    }

    public function beforeAction($action)
    {
        if ($this->request->method === 'GET') {
            $this->manager->parseFieldsets($this->request->getQueryParam('fields', []))
                          ->parseIncludes($this->request->getQueryParam('include', ''))
                          ->parseExcludes($this->request->getQueryParam('exclude', ''));
        }
        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);
        return $this->serializeData($result);
    }
    /**
     * Declares the allowed HTTP verbs.
     * Please refer to [[VerbFilter::actions]] on how to declare the allowed verbs.
     * @return array the allowed HTTP verbs.
     */
    protected function verbs()
    {
        return [];
    }

    protected function serializeData($data)
    {
        if ($data instanceof JsonApiError && $data->status !== null && $data->status !== 0) {
            Yii::$app->response->setStatusCode($data->status);
        }
        if ($data instanceof JsonApiDataProviderInterface) {
            $data = $data->toCollection();
        }
        if ($data instanceof Item || $data instanceof Collection) {
            $data = $this->manager->createData($data)->toArray();
            if (isset($data['data']) && !isset($data['links']) && !isset($data['data']['links'])) {
                $data['links'] = ['self' => Url::current([], true)];
            }
        }
        return Yii::createObject($this->serializer)->serialize($data);
    }
}
