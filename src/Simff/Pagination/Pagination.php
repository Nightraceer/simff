<?php

namespace Simff\Pagination;

use Simff\Helpers\Creator;
use Simff\Helpers\SmartProperties;
use Simff\Main\Simff;
use Simff\Template\Renderer;

/**
 * Class Pagination
 *
 * @property $data array
 *
 * @package Phact\Pagination
 */
class Pagination
{
    use SmartProperties, Renderer;

    protected $_page;

    protected $_defaultPage = 1;

    protected $_id = 1;

    protected $_total = null;

    protected $_lastPage = null;

    public $source = [];

    public $pageKeyTemplate = 'Pagination_{id}';

    public $pageSize = 10;
    
    public function __construct($options = [])
    {
        Creator::configure($this, $options);
    }

    public function getId()
    {
        return $this->_id;
    }

    public function setId($id)
    {
        $this->_id = $id;
    }

    public function getPage()
    {
        return $this->_page;
    }

    public function setPage($page)
    {
        $this->_page = $page;
    }

    public function getPageSize()
    {
        return $this->pageSize;
    }

    public function getDefaultPage()
    {
        return $this->getFirstPage();
    }

    public function fetchPage()
    {
        if (!$this->getPage()) {
            $page = $this->getRequestPage();
            if (!$page) {
                $page = $this->getDefaultPage();
            }
            if ($page <= 0) {
                $page = 1;
            } elseif ($page > $this->getLastPage()) {
                $page = $this->getLastPage();
            }
            $this->setPage($page);
        }
        return $this->getPage();
    }

    public function getRequestPage()
    {
        $key = $this->getRequestPageKey();

        return $this->getRequestValue($key);
    }

    public function getRequestPageKey()
    {
        return strtr($this->pageKeyTemplate, [
            '{id}' => $this->getId()
        ]);
    }


    public function getRequestValue($key, $default = null)
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    public function getFirstPage()
    {
        return 1;
    }

    public function getFirstPageUrl()
    {
        return $this->getUrl($this->getFirstPage());
    }

    public function getLastPage()
    {
        if (is_null($this->_lastPage)) {
            $pageSize = $this->getPageSize();
            $total = $this->getTotal();
            $result = ceil($total / $pageSize);
            $this->_lastPage = $result >= 1 ? $result : 1;
        }
        return $this->_lastPage;
    }

    public function getLastPageUrl()
    {
        return $this->getUrl($this->getLastPage());
    }

    public function getPreviousPage()
    {
        $page = $this->fetchPage() - 1;
        if ($this->hasPage($page)) {
            return $page;
        }
        return null;
    }

    public function hasPreviousPage()
    {
        return (bool) $this->getPreviousPage();
    }

    public function getPreviousPageUrl()
    {
        return $this->hasPreviousPage() ? $this->getUrl($this->getPreviousPage()) : null;
    }

    public function getNextPage()
    {
        $page = $this->fetchPage() + 1;
        if ($this->hasPage($page)) {
            return $page;
        }
        return null;
    }

    public function hasNextPage()
    {
        return (bool) $this->getNextPage();
    }

    public function getNextPageUrl()
    {
        return $this->hasNextPage() ? $this->getUrl($this->getNextPage()) : null;
    }

    public function hasPage($page)
    {
        $lastPage = $this->getLastPage();
        if ($page >= 1 && $page <= $lastPage) {
            return true;
        }
        return false;
    }

    public function getUrl($page)
    {
        $query = Simff::app()->request->getQueryArray();
        $query[$this->getRequestPageKey()] = $page;
        return Simff::app()->request->getPath() . '?' . http_build_query($query);
    }

    public function getTotal()
    {
        if (is_null($this->_total)) {
            $this->_total = count($this->source);
        }
        return $this->_total;
    }

    public function getData()
    {
        $page = $this->fetchPage();
        $pageSize = $this->getPageSize();

        $limit = $pageSize;
        $offset = ($page - 1) * $pageSize;

        return array_slice($this->source, $offset, $limit);
    }

    public function render($template = 'pagination/default.tpl')
    {
        return $this->renderTemplate($template, [
            'pagination' => $this
        ]);
    }
}