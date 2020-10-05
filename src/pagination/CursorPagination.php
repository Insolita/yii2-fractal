<?php

/**
 * @copyright Copyright (c) 2018 Carsten Brandt <mail@cebe.cc> and contributors
 * @license https://github.com/cebe/yii2-openapi/blob/master/LICENSE
 */

namespace insolita\fractal\pagination;

use League\Fractal\Pagination\Cursor;
use Yii;
use yii\base\BaseObject;

class CursorPagination extends BaseObject
{
    public const CURSOR_PARAM = 'cursor';
    public const PREVIOUS_PARAM = 'previous';
    public const LIMIT_PARAM = 'limit';
    /**
     * @var int number of items for current page
     */
    public $defaultPageSize = 20;

    /**
     * @var int the default page size. This property will be returned by [[pageSize]] when page size
     * cannot be determined by [[pageSizeParam]] from [[params]].
     */
    public $pageSizeLimit = [1, 100];

    private $nextCursor;
    private $itemsCount;

    public function getCurrentCursor():?int
    {
        $cursor = (int)Yii::$app->request->get(self::CURSOR_PARAM, null);
        if ($cursor && $cursor < 0) {
            $cursor = null;
        }
        return $cursor;
    }

    public function getPreviousCursor():?int
    {
        $cursor = (int)Yii::$app->request->get(self::PREVIOUS_PARAM, null);
        if ($cursor && $cursor < 0) {
            $cursor = null;
        }
        return $cursor;
    }

    public function getNextCursor():?int
    {
        return $this->nextCursor;
    }

    public function setNextCursor(?int $cursor):void
    {
        $this->nextCursor = $cursor;
    }

    public function getCount(): int
    {
        return $this->itemsCount;
    }

    public function setCount(int $count): void
    {
        $this->itemsCount = $count;
    }

    public function getLimit(): int
    {
        $limit = (int)Yii::$app->request->get(self::LIMIT_PARAM, $this->defaultPageSize);
        if ($limit && $limit < 1) {
            $limit = $this->defaultPageSize;
        }
//        if (!empty($this->pageSizeLimit) && \is_array($this->pageSizeLimit) && \count($this->pageSizeLimit) === 2) {
//            $limit = \max($limit, $this->pageSizeLimit[0]);
//            $limit = \min($limit, $this->pageSizeLimit[1]);
//        }
        return $limit;
    }



    public function toFractalCursor(): Cursor
    {
        return new Cursor(
            $this->getCurrentCursor(),
            $this->getPreviousCursor(),
            $this->getNextCursor(),
            $this->getCount()
        );
    }
}
