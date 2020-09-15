<?php

namespace insolita\fractal;

use insolita\fractal\exceptions\ValidationException;
use Throwable;
use yii\base\NotSupportedException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use function array_diff;
use function count;
use function reset;

/**
 * @see https://jsonapi.org/format/#crud-updating-to-one-relationships
 * @see https://jsonapi.org/format/#crud-updating-to-many-relationships
**/
class RelationPatcher
{
    /**
     * @var \yii\db\ActiveRecord
     */
    private $model;

    /**
     * @var string
     */
    private $relationName;

    /**
     * @var array|null
     */
    private $data;

    public function __construct(ActiveRecord $model, string $relationName, ?array $data)
    {
        $this->model = $model;
        $this->relationName = $relationName;
        $this->data = $data;
    }

    public function create()
    {
        /**
         * @var \yii\db\ActiveQuery|\yii\db\ActiveQueryInterface $relation
         **/
        $relation = $this->model->getRelation($this->relationName);

        if (!$relation->multiple) {
            throw new NotSupportedException('Delete relationship allowed only for to-many relations', 403);
        }

        if ($this->data === [] || $this->data === null) {
            return false;
        }
        $attributes = ArrayHelper::getColumn($this->data, 'attributes');
        if (empty($attributes)) {
            return false;
        }
        /**@var ActiveRecord $relatedModelClass*/
        $relatedModelClass = $relation->modelClass;
        /**@var array|ActiveRecord $models **/
        $models = \array_map(function () use ($relatedModelClass) {
            return new $relatedModelClass;
        }, $attributes);
        if ($relatedModelClass::loadMultiple($models, $attributes, '') && $relatedModelClass::validateMultiple($models)) {
            foreach ($models as $model) {
                $this->model->link($this->relationName, $model);
            }
            return true;
        }
        $firstModel = reset($models);
        throw new ValidationException($firstModel->errors);
    }

    /**
     * @param false $unlinkOnly  if true, relations will be unlinked but related records will be not touched
     * @return bool  true if any db changes happens
     * @throws \Throwable
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\Exception
     */
    public function delete($unlinkOnly = true):bool
    {
        /**
         * @var \yii\db\ActiveQuery|\yii\db\ActiveQueryInterface $relation
         **/
        $relation = $this->model->getRelation($this->relationName);

        if (!$relation->multiple) {
            throw new NotSupportedException('Delete relationship allowed only for to-many relations', 403);
        }

        if ($this->data === [] || $this->data === null) {
            return false;
        }

        $ids = ArrayHelper::getColumn($this->data, 'id');
        /**@var ActiveRecord $relatedModelClass*/
        $relatedModelClass = $relation->modelClass;
        $keys = $relatedModelClass::primaryKey();
        if (count($keys) > 1) {
            throw new NotSupportedException('Updating relations with composite pk not supported yet');
        }
        $idKey = reset($keys);
        $forUnlinkModels = $relation->where([$idKey => $ids])->all();
        if (empty($forUnlinkModels)) {
            return false;
        }
        $transaction = $this->model::getDb()->beginTransaction();
        try {
            foreach ($forUnlinkModels as $unlinkModel) {
                $this->model->unlink($this->relationName, $unlinkModel, !$unlinkOnly);
            }
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
        return true;
    }

    /**
     * @param false $unlinkOnly  if true, relations will be unlinked but related records will be not touched
     * @return bool  true if any db changes happens
     * @throws \Throwable
     * @throws \insolita\fractal\exceptions\ValidationException
     * @throws \yii\base\NotSupportedException
     * @throws \yii\web\NotFoundHttpException
     */
    public function patch($unlinkOnly = true):bool
    {
        /**
         * @var \yii\db\ActiveQuery|\yii\db\ActiveQueryInterface $relation
         **/
        $relation = $this->model->getRelation($this->relationName);

        if (!$relation->multiple) {
            throw new NotSupportedException('Patch relationship allowed only for to-many relations', 403);
        }
        if ($this->data === []) {
            if ($relation->count() > 0) {
                $this->model->unlinkAll($this->relationName, !$unlinkOnly);
                return true;
            }
            return false;
        }
        /**@var ActiveRecord $relatedModelClass*/
        $relatedModelClass = $relation->modelClass;
        $ids = ArrayHelper::getColumn($this->data, 'id');
        if (empty($ids)) {
            throw new ValidationException(['id' => ['Missing ids for update relationship']]);
        }
        $keys = $relatedModelClass::primaryKey();
        if (count($keys) > 1) {
            throw new NotSupportedException('Updating relations with composite pk not supported yet');
        }
        $idKey = reset($keys);
        $alreadyRelatedIds = $relation->select($idKey)->column();
        $forUnlink = array_diff($alreadyRelatedIds, $ids);
        $forLink = array_diff($ids, $alreadyRelatedIds);
        if (empty($forUnlink) && empty($forLink)) {
            return false;
        }
        $transact = $this->model::getDb()->beginTransaction();
        $hasChanges = false;
        try {
            if (!empty($forUnlink)) {
                $forUnlinkModels = $relation->where([$idKey => $forUnlink])->all();
                foreach ($forUnlinkModels as $unlinkModel) {
                    $hasChanges = true;
                    $this->model->unlink($this->relationName, $unlinkModel, !$unlinkOnly);
                }
            }
            if (!empty($forLink)) {
                $forLinkModels = $relatedModelClass::find()->where([$idKey => $forLink])->all();
                foreach ($forLinkModels as $linkModel) {
                    $hasChanges = true;
                    $this->model->link($this->relationName, $linkModel);
                }
            }
            $transact->commit();
        } catch (Throwable $e) {
            $transact->rollBack();
            throw $e;
        }
        return $hasChanges;
    }
}
