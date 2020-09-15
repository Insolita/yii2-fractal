<?php

namespace insolita\fractal\actions;

use insolita\fractal\DefaultTransformer;
use League\Fractal\TransformerAbstract;
use Yii;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\helpers\Html;

/**
 * @mixin \insolita\fractal\actions\JsonApiAction
 **/
trait HasRelationResourceMap
{
    /**
     * List with allowed relations and it transformers data
     * @example
     *   'relationMap' => [
     *       'author' => ['authors' => AuthorTransformer::class], //resourceKey 'authors',
     *       'album' => ['albums' => null ],//resourceKey - 'albums', \insolita\fractal\DefaultTransformer will be used
     *       'comments' => RelatedCommentTransformer::class  //resourceKey same as relation 'comments'
     *       'rates' => null   //resourceKey - 'rates', \insolita\fractal\DefaultTransformer will be used
     *    ]
     * @var array
     */
    public $relationMap = [];

    /**
     * @var callable a PHP callable that will be called when running an action to determine
     * if the current user has the permission to execute the action. If not set, the access
     * check will not be performed. The signature of the callable should be as follows,
     * ```php
     * function ($action, $model, $relationName, $method, $relatedModel = null) {
     *     // $model is the requested model instance.
     *     // $relationName is the requested relation
     *     // $method - request method GET/POST/DELETE/PUT
     *     // $relatedModel is related model for hasOne relation type and null for hasMany relation type
     * }
     * ```
     */
    public $checkAccessRelation;

    protected $resourceKey;

    protected $transformer;

    /**
     * @param string $relationName
     * @throws \yii\base\InvalidConfigException
     */
    protected function resolveResource(string $relationName):void
    {
        if (!array_key_exists($relationName, $this->relationMap)) {
            throw new InvalidCallException('Relation ' . Html::encode($relationName) . ' not allowed');
        }

        $resourceData = $this->relationMap[$relationName];
        if ($resourceData === null) {
            $this->resourceKey = $relationName;
            $this->transformer = new DefaultTransformer();
        }
        if (is_string($resourceData)) {
            $transformer = Yii::createObject($resourceData);
            if (!$transformer instanceof TransformerAbstract) {
                throw new InvalidConfigException('Transformer for ' . $relationName
                    . ' must be an instance of TransformerAbstract');
            }
            $this->resourceKey = $relationName;
            $this->transformer = $transformer;
        }

        if (is_array($resourceData)) {
            $resourceKey = key($resourceData);
            $transformer = $resourceData[$resourceKey] !== null
                ? Yii::createObject($resourceData[$resourceKey])
                : new DefaultTransformer();
            if (!$transformer instanceof TransformerAbstract) {
                throw new InvalidConfigException('Transformer for ' . $relationName
                    . ' must be an instance of TransformerAbstract');
            }
            $this->resourceKey = $resourceKey;
            $this->transformer = $transformer;
        }
        if (!$this->transformer || !$this->resourceKey) {
            throw new InvalidConfigException('Unexpected format of relationMap property');
        }
    }
}
