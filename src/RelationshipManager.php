<?php

/**
 * @copyright Copyright (c) 2020 Insolita <webmaster100500@ya.ru> and contributors
 * @license https://github.com/insolita/yii2-fractal/blob/master/LICENSE
 */

namespace insolita\fractal;

use Throwable;
use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use function array_diff;
use function call_user_func;
use function count;
use function gettype;
use function implode;
use function is_numeric;

/**
 * @see https://jsonapi.org/format/#crud-updating-to-one-relationships
 * @see https://jsonapi.org/format/#crud-updating-to-many-relationships
 **/
class RelationshipManager
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

    /**
     * @var string
     */
    private $idType;

    /**
     * @var callable
     */
    private $idValidateCallback;

    public function __construct(ActiveRecordInterface $model, string $relationName, ?array $data, string $idType = 'integer')
    {
        $this->model = $model;
        $this->relationName = $relationName;
        $this->data = $data;
        $this->idType = $idType;
    }

    /**
     * Link new relationships to model
     * @return ActiveQueryInterface|\yii\db\ActiveQuery
     * @throws \Throwable
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\Exception
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\HttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function attach()
    {
        /**
         * @var \yii\db\ActiveQuery|\yii\db\ActiveQueryInterface $relation
         **/
        $relation = $this->model->getRelation($this->relationName);

        if (!$relation->multiple) {
            throw new ForbiddenHttpException('Create relationship allowed only for to-many relations', 403);
        }

        if ($this->data === [] || $this->data === null) {
            return $relation;
        }
        /**@var ActiveRecord $relatedModelClass */
        $relatedModelClass = $relation->modelClass;

        $ids = ArrayHelper::getColumn($this->data, 'id');
        if (empty($ids)) {
            throw new HttpException(422, 'Missing ids for create relationship');
        }
        if ($this->idValidateCallback !== null) {
            call_user_func($this->idValidateCallback, $this->model, $ids);
        } else {
            $this->validateIdType($ids, false);
        }
        $pkAttribute = $this->resolvePkAttribute($relation);
        $alreadyRelatedIds = $relation->select($pkAttribute)->column();
        $forLink = array_diff($ids, $alreadyRelatedIds);
        if (empty($forLink)) {
            return $relation;
        }
        $forLinkModels = $relatedModelClass::find()->where([$pkAttribute => $forLink])->all();
        if (count($forLinkModels) !== count($forLink)) {
            throw new NotFoundHttpException(
                'Records with ids '. implode(',', array_diff($forLink, $forLinkModels)).' not found',
                404
            );
        }
        $transact = $this->model::getDb()->beginTransaction();
        try {
            foreach ($forLinkModels as $linkModel) {
                $this->model->link($this->relationName, $linkModel);
            }
            $transact->commit();
        } catch (Throwable $e) {
            $transact->rollBack();
            throw $e;
        }
        return $relation;
    }


    /**
     * @param bool $unlinkOnly if true, relations will be unlinked but related records will be not touched
     * @throws \Throwable
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\Exception
     */
    public function delete(bool $unlinkOnly = true):void
    {
        /**
         * @var \yii\db\ActiveQuery|\yii\db\ActiveQueryInterface $relation
         **/
        $relation = $this->model->getRelation($this->relationName);

        if (!$relation->multiple) {
            throw new ForbiddenHttpException('Delete relationship allowed only for to-many relations', 403);
        }

        if ($this->data === [] || $this->data === null) {
            return;
        }

        $ids = ArrayHelper::getColumn($this->data, 'id');
        if ($this->idValidateCallback !== null) {
            call_user_func($this->idValidateCallback, $this->model, $ids);
        } else {
            $this->validateIdType($ids, false);
        }
        $records = $relation->where([$this->resolvePkAttribute($relation) => $ids])->all();
        $recordIds = ArrayHelper::getColumn($records, 'id');
        if (count($recordIds) !== count($ids)) {
            throw new HttpException(422, 'Some of ids does not belongs to current model');
        }
        $transaction = $this->model::getDb()->beginTransaction();
        try {
            foreach ($records as $record) {
                $this->model->unlink($this->relationName, $record, !$unlinkOnly);
            }
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * @param false $unlinkOnly if true, relations will be unlinked but related records will be not touched
     * @throws \Throwable
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\Exception
     * @throws \yii\web\HttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function patch($unlinkOnly = true):void
    {
        /**
         * @var \yii\db\ActiveQuery|\yii\db\ActiveQueryInterface $relation
         **/
        $relation = $this->model->getRelation($this->relationName);

        if (!$relation->multiple) {
            $this->patchSingle($relation, $unlinkOnly);
            return;
        }
        if ($this->data === []) {
            if ($relation->count() > 0) {
                $this->model->unlinkAll($this->relationName, !$unlinkOnly);
                return;
            }
            return;
        }
        /**@var ActiveRecord $relatedModelClass */
        $relatedModelClass = $relation->modelClass;
        $ids = ArrayHelper::getColumn($this->data, 'id');
        if (empty($ids)) {
            throw new HttpException(422, 'Missing ids for update relationship');
        }

        if ($this->idValidateCallback !== null) {
            call_user_func($this->idValidateCallback, $this->model, $ids);
        } else {
            $this->validateIdType($ids, false);
        }
        $pkAttribute = $this->resolvePkAttribute($relation);
        $alreadyRelatedIds = $relation->select($pkAttribute)->column();
        $forUnlink = array_diff($alreadyRelatedIds, $ids);
        $forLink = array_diff($ids, $alreadyRelatedIds);
        if (empty($forUnlink) && empty($forLink)) {
            return;
        }
        $transact = $this->model::getDb()->beginTransaction();
        try {
            if (!empty($forUnlink)) {
                $forUnlinkModels = $relation->where([$pkAttribute => $forUnlink])->all();
                foreach ($forUnlinkModels as $unlinkModel) {
                    $this->model->unlink($this->relationName, $unlinkModel, !$unlinkOnly);
                }
            }
            if (!empty($forLink)) {
                $forLinkModels = $relatedModelClass::find()->where([$pkAttribute => $forLink])->all();
                foreach ($forLinkModels as $linkModel) {
                    $this->model->link($this->relationName, $linkModel);
                }
            }
            $transact->commit();
        } catch (Throwable $e) {
            $transact->rollBack();
            throw $e;
        }
    }

    /**
     * @param mixed $idValidateCallback
     * @return RelationshipManager
     */
    public function setIdValidateCallback($idValidateCallback)
    {
        $this->idValidateCallback = $idValidateCallback;
        return $this;
    }

    /**
     * @param \yii\db\ActiveQueryInterface $relation
     * @param bool                         $unlinkOnly
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\web\HttpException
     */
    protected function patchSingle(ActiveQueryInterface $relation, bool $unlinkOnly):void
    {
        $newRelationId = $this->data['id'] ?? null;

        if ($this->idValidateCallback !== null) {
            call_user_func($this->idValidateCallback, $this->model, [$newRelationId]);
        } else {
            $this->validateIdType([$newRelationId], true);
        }

        $relatedModel = $relation->one();
        $alreadyRelatedIds = $relatedModel ? $relatedModel->getPrimaryKey(): [];
        if (!$newRelationId && $relatedModel) {
            $this->model->unlink($this->relationName, $relatedModel, !$unlinkOnly);
            Yii::$app->response->setStatusCode(204);
            return;
        }

        if ($newRelationId && $relatedModel && $relatedModel->primaryKey === $newRelationId) {
            Yii::$app->response->setStatusCode(200);
            return;
        }

        /**@var \yii\db\ActiveRecordInterface $modelClass */
        $modelClass = $relation->modelClass;
        $newRelatedModel = $modelClass::findOne($newRelationId);
        if (!$newRelatedModel) {
            throw new NotFoundHttpException('Object for link not found');
        }
        if ($relatedModel) {
            $this->model->unlink($this->relationName, $relatedModel, !$unlinkOnly);
        }
        $this->model->link($this->relationName, $newRelatedModel);
        Yii::$app->response->setStatusCode(200);
    }

    /**
     * @param \yii\db\ActiveQueryInterface $relation
     * @return string
     * @throws \yii\base\NotSupportedException
     */
    protected function resolvePkAttribute(ActiveQueryInterface $relation):string
    {
        $modelClass = $relation->modelClass;
        $pks = $modelClass::primaryKey();
        if (count($pks) === 1) {
            $pk = $pks[0];
        } else {
            throw new NotSupportedException('Composite primary key not supported');
        }
        return $pk;
    }

    /**
     * @param array $ids
     * @param bool  $allowNull
     * @throws \yii\web\HttpException
     */
    protected function validateIdType(array $ids, bool $allowNull = false):void
    {
        foreach ($ids as $id) {
            $type = gettype($id);
            if (($this->idType === 'integer' && is_numeric($id))
                ||($type === $this->idType)
                || ($allowNull && $type === 'NULL')) {
                continue;
            }
            throw new HttpException(422, 'Data contains ids with invalid type');
        }
    }

    /**
     * @param \yii\db\ActiveRecord $model
     * @param array                $relationships
     * @param array                $allowed
     * @throws \yii\web\ForbiddenHttpException
     */
    public static function validateRelationships(ActiveRecord $model, array $relationships, array $allowed): void
    {
        if (empty($relationships)) {
            return;
        }
        if (empty($allowed)) {
            throw new ForbiddenHttpException('Operations with relationships is not allowed');
        }
        $relationNames = array_keys($relationships);
        foreach ($relationNames as $relationName) {
            if (!array_key_exists($relationName, $allowed)) {
                throw new ForbiddenHttpException('Operation with relationship "'.$relationName.'" is not allowed');
            }
            $model->getRelation($relationName);
        }
    }
}
