<?php

namespace insolita\fractal\pagination;

use League\Fractal\Pagination\PaginatorInterface;
use Yii;
use yii\data\Pagination;
use yii\web\Request;

/**
 * Instead of zero-based yii pagination, first page number is 1 and last equals pageCount
 * Resolve page params from page queryParam - GET /controller/action?page[number]=2&page[size]=10
 * @property-read  int $count
 * @property-read  int $currentPage
 * @property-read  int $lastPage
 * @property-read  int $offset
 * @property-read  int $pageCount
 * @property-read  int $perPage
 * @property-read  int $total
 * @property-write   int $itemsCount
 * @property  int $pageSize
 * @property int $page
 */
class JsonApiPaginator extends Pagination implements PaginatorInterface
{
    public $forcePageParam = false;

    /**
     * @var int total number of items.
     */
    public $totalCount = 0;

    /**
     * @var int number of items for current page
     */
    public $itemsCount = 0;
    /**
     * @var int the default page size. This property will be returned by [[pageSize]] when page size
     * cannot be determined by [[pageSizeParam]] from [[params]].
     */
    public $defaultPageSize = 20;
    /**
     * @var array|false the page size limits. The first array element stands for the minimal page size, and the second
     * the maximal page size. If this is false, it means [[pageSize]] should always return the value of [[defaultPageSize]].
     */
    public $pageSizeLimit = [1, 100];

    /**
     * @var boolean
     * Indicate, should paginator provide absolute urls or relative
    */
    public $absoluteUrls = true;

    /**
     * @var int calculated current page number
    */
    private $_page;

    public function init()
    {
        parent::init();
        $this->pageSizeParam = 'size';
        $this->pageParam = 'number';
    }

    public function getCurrentPage()
    {
        return $this->getPage();
    }

    public function getLastPage()
    {
        return $this->getPageCount();
    }

    public function getTotal()
    {
        return $this->totalCount;
    }

    public function getCount()
    {
        return $this->itemsCount;
    }

    public function getPerPage()
    {
        return $this->getPageSize();
    }

    public function getUrl($page)
    {
        return $this->createUrl($page);
    }

    public function setItemsCount(int $number)
    {
        $this->itemsCount = $number;
    }


    /**
     * Returns the 1-based current page number.
     * @param bool $recalculate whether to recalculate the current page based on the page size and item count.
     * @return int the zero-based current page number.
     */
    public function getPage($recalculate = false)
    {
        if ($this->_page === null || $recalculate) {
            $page = (int) $this->getQueryParam($this->pageParam, 1);
            if ($page === 0) {
                $page = 1;
            }
            $this->setPage($page, true);
        }

        return $this->_page;
    }

    /**
     * Sets the current page number.
     * @param int $value the one-based index of the current page.
     * @param bool $validatePage whether to validate the page number. Note that in order
     * to validate the page number, both [[validatePage]] and this parameter must be true.
     */
    public function setPage($value, $validatePage = false)
    {
        if ($value === null) {
            $this->_page = null;
        } else {
            $value = (int) $value;
            if ($validatePage && $this->validatePage) {
                $pageCount = $this->getPageCount();
                if ($value > $pageCount) {
                    $value = $pageCount;
                }
            }
            if ($value < 1) {
                $value = 1;
            }
            $this->_page = $value;
        }
    }

    public function getOffset()
    {
        $pageSize = $this->getPageSize();
        $zeroBasedPage = $this->getPage() -1;
        return $pageSize < 1 ? 0 : $zeroBasedPage * $pageSize;
    }

    public function createUrl($page, $pageSize = null, $absolute = false)
    {
        $page = (int) $page;
        $pageSize = (int) $pageSize;

        if (($params = $this->params) === null) {
            $request = Yii::$app->getRequest();
            $params = $request instanceof Request ? $request->getQueryParams() : [];
        }
        if (!isset($params['page'])) {
            $params['page'] = [];
        }
        /**
         * Page numerations starts from 1
         * @see vendor/league/fractal/src/Serializer/JsonApiSerializer.php:124
         */
        if ($page === 0) {
            $page = 1;
        }
        if ($page > 1 || ($page === 1 && $this->forcePageParam)) {
            $params['page'][$this->pageParam] = $page;
        } else {
            unset($params['page'][$this->pageParam]);
        }

        if ($pageSize <= 0) {
            $pageSize = $this->getPageSize();
        }
        if ($pageSize !== $this->defaultPageSize) {
            $params['page'][$this->pageSizeParam] = $pageSize;
        } else {
            unset($params['page'][$this->pageSizeParam]);
        }

        $params[0] = $this->route ?? Yii::$app->controller->getRoute();
        $urlManager = $this->urlManager ?? Yii::$app->getUrlManager();
        if ($this->absoluteUrls) {
            return $urlManager->createAbsoluteUrl($params);
        }

        return $urlManager->createUrl($params);
    }

    protected function getQueryParam($name, $defaultValue = null)
    {
        if (($params = $this->params) === null) {
            $request = Yii::$app->getRequest();
            $params = $request instanceof Request ? $request->getQueryParams() : [];
        }

        $pageParams = $params['page'] ?? [];

        return isset($pageParams[$name]) && is_scalar($pageParams[$name]) ? $pageParams[$name] : $defaultValue;
    }
}
