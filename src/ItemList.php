<?php

namespace whmcsEasyAddons;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

/**
 * Class for fast creating of lists with items from a database.
 */
class ItemList
{
    /** @var int The number of chosen page */
    public $page = 1;

    /** @var int The count of items on page */
    public $perpage = 20;

    /** @var string The name of column for sorting */
    public $order = 'id';

    /** @var string The direction of sorting */
    public $sort = 'DESC';

    /** @var int The sum count of items */
    public $count;

    /** @var int The count of pages */
    public $maxpage;

    /** @var int The count of pages showed in paginator before and after the current page */
    public $pages_in_paginator = 4;

    /** @var array The array of items */
    public $result = array();

    /** @var string The html-code of tablehead */
    public $tablehead;

    /** @var string The html-code of paginator */
    public $paginator;

    /** @var string The html-code of filter form */
    public $filter;

    /** @var array Filter params */
    private $filter_params;

    /** @var array Request params */
    private $sql_array;

    /**
     * The constructor.
     *
     * @param array $sql           The array of request params. It can contain:
     *                             'SELECT' - value part of SELECT statement.
     *                             'FROM' - value part of FROM statement.
     *                             'INNER JOIN' - value part of INNER JOIN statement.
     *                             'LEFT JOIN' - value part of LEFT JOIN statement.
     *                             'RIGHT JOIN' - value part of RIGHT JOIN statement.
     *                             'WHERE' - value part of WHERE statement.
     * @param array $filter_params The array of filters. Each filter must contain:
     *                             'name' - a name of a filtered parameter. It must match with the name in a database.
     *                             'value' - a value of a filtered parameter.
     *                             'description' - a description of filter.
     *                             'type' - A type of filter. It can be:
     *                             '' - (an empty value) will use a simple compare '='.
     *                             'LIKE' - will use '%LIKE%'.
     *                             'IN' - will use 'IN (...)'.
     */
    public function __construct($sql = array(), $filter_params = array())
    {
        $this->sql_array = $sql;
        $this->filter_params = $filter_params;
        $this->createList();
    }

    /**
     * Get items from a database and create a list.
     */
    public function createList()
    {

        // Get an information about sorting and chosen page.
        if ($_GET['page'] != null) {
            $this->page = $_GET['page'];
        }
        if ($_GET['order'] != null) {
            $this->order = $_GET['order'];
        }
        if ($_GET['sort'] != null) {
            $this->sort = $_GET['sort'];
        }

        // Include filters to request.
        if (count($this->filter_params) and is_array($this->filter_params)) {
            foreach ($this->filter_params as $param) {
                if ($param['value']) {
                    if ($param['type'] == 'LIKE') {
                        $this->sql_array['WHERE'] .= ' '.$param['name'].' LIKE "%'.$param['value'].'%" ';
                    } elseif ($param['type'] == 'IN') {
                        $this->sql_array['WHERE'] .= ' '.$param['name'].' IN ('.$param['value'].') ';
                    } else {
                        $this->sql_array['WHERE'] .= ' '.$param['name'].' = "'.$param['value'].'" ';
                    }
                }
            }
        }

        // Create SQL.
        $_sql =
            ' SELECT '.$this->sql_array['SELECT'].
            ' FROM '.$this->sql_array['FROM'].
            (isset($this->sql_array['INNER JOIN']) ? ' INNER JOIN '.$this->sql_array['INNER JOIN'] : '').
            (isset($this->sql_array['LEFT JOIN']) ? ' LEFT JOIN '.$this->sql_array['LEFT JOIN'] : '').
            (isset($this->sql_array['RIGHT JOIN']) ? ' RIGHT JOIN '.$this->sql_array['RIGHT JOIN'] : '').
            (isset($this->sql_array['WHERE']) ? ' WHERE '.$this->sql_array['WHERE'] : '').
            ' ORDER BY '.$this->order.' '.$this->sort.
            ' LIMIT '.(($this->page - 1) * $this->perpage).', '.$this->perpage.'
		';

        // Do SQL request.
        $result_data = full_query($_sql);
        $result = array();

        // Fetch answer.
        while ($line = mysql_fetch_assoc($result_data)) {
            $result[] = $line;
        }
        $this->result = $result;

        // Create concomitants.
        $this->createTablehead();
        $this->createPaginator();
        $this->createFilter();
    }

    /**
     * Create a tablehead with sort buttons.
     */
    private function createTablehead()
    {
        $th = '';
        if (isset($this->result[0]) and is_array($this->result[0])) {
            $th .= '<tr>';
            foreach (array_keys($this->result[0]) as $head) {
                $th .= '<th>';
                if ($this->order != $head) {
                    $th .= '<a href="'.$this->getUrl(array('order' => $head)).'">'.$head.'</a>';
                } else {
                    $th .= '<a href="'.$this->getUrl(array('order' => $head, 'sort' => $this->toggleSort($this->sort))).'">';
                    $th .= $head;
                    $th .= '<img class="absmiddle" src="images/'.strtolower($this->sort).'.gif">';
                    $th .= '</a>';
                }
                $th .= '</th>';
            }
            $th .= '</tr>';
        }
        $this->tablehead = $th;
    }

    /**
     * Create the paginator.
     */
    private function createPaginator()
    {

        // If sql_array doesn`t contain 'FROM' parameter, we can`t create the paginator.
        if ($this->sql_array['FROM']) {

            // Do request about a count of items in table
            $count = mysql_fetch_assoc(full_query('
				SELECT count(*) AS count 
				FROM '.$this->sql_array['FROM'].
                (isset($this->sql_array['INNER JOIN']) ? ' INNER JOIN '.$this->sql_array['INNER JOIN'] : '').
                (isset($this->sql_array['LEFT JOIN']) ? ' LEFT JOIN '.$this->sql_array['LEFT JOIN'] : '').
                (isset($this->sql_array['RIGHT JOIN']) ? ' RIGHT JOIN '.$this->sql_array['RIGHT JOIN'] : '').
                (isset($this->sql_array['WHERE']) ? ' WHERE '.$this->sql_array['WHERE'] : '')
            ));
            $this->count = $count['count'];

            // Count of pages
            $this->maxpage = floor($count['count'] / $this->perpage);

            // If we have an incomplete page in the end, when do maxpage+1
            if (ceil($count['count'] / $this->perpage) != $this->maxpage) {
                $this->maxpage += 1;
            }

            // Create html-code of the paginator.
            $res = '';

            // Previous page
            if ($this->page > 1) {
                $res .= '<a href="'.$this->getUrl(array('page' => ($this->page - 1))).'" class="paginator prevpage"><<</a>';
            }
            // First page
            if ($this->page - $this->pages_in_paginator > 1) {
                $res .= '<a href="'.$this->getUrl(array('page' => 1)).'" class="paginator firstpage">1</a>..';
            }
            // Simple pages
            for ($i = $this->page - $this->pages_in_paginator; $i <= $this->page + $this->pages_in_paginator; ++$i) {
                if (($i > 0) and ($i <= $this->maxpage)) {
                    // Simple page
                    if ($i != $this->page) {
                        $res .= '<a href="'.$this->getUrl(array('page' => $i)).'" class="paginator page">'.$i.'</a>';
                    // Chosen page
                    } else {
                        $res .= '<span class="paginator currentpage"><b>'.$i.'</b></span>';
                    }
                }
            }
            // Last page
            if ($this->page + $this->pages_in_paginator < $this->maxpage) {
                $res .= '..<a href="'.$this->getUrl(array('page' => ($this->maxpage))).'" class="paginator lastpage">'.$this->maxpage.'</a>';
            }
            // Next page
            if ($this->page < $this->maxpage) {
                $res .= '<a href="'.$this->getUrl(array('page' => ($this->page + 1))).'" class="paginator nextpage">>></a>';
            }

            $this->paginator = $res;
        } else {
            $this->paginator = 'You should pass the value "FROM"';
        }
    }

    /**
     * Create a filter-code.
     */
    private function createFilter()
    {
        $filter = '<form name="ak_filter" method="GET" action="" style="display:none">';

        $filter .= '<table class="ak_filter">';

        foreach ($this->filter_params as $param) {
            $filter .= '<tr>';
            $filter .= '<td>'.$param['description'].'</td><td><input type="text" value="'.$param['value'].'" name="filter['.$param['name'].']"></td>';
            $filter .= '</tr>';
        }

        foreach ($_GET as $name => $value) {
            if (($name != 'filter') and ($name != 'page')) {
                $filter .= '<input type="hidden" value="'.$value.'" name="'.$name.'">';
            }
        }

        $filter .= '<tr><td><input type="submit" value="Поиск"></td><td></td></tr>';

        $filter .= '</table>';

        $filter .= '</form>';

        $filter .= '<input type="button" id="show_filter" value="Поиск"><br>';

        $filter .= '<script>$(document).ready(function(){
			$("#show_filter").on("click", function(){
				$("#show_filter").hide();
				$("form[name=\'ak_filter\']").show();
			});
		});</script>';

        $this->filter = $filter;
    }

    /**
     * Create URL for a page with all sorting and filter parameters.
     * 
     * @param array $newdata The array of parameters, which values is different from default values.
     */
    public function getUrl($newdata = array())
    {
        global $customadminpath, $module;
        $data = array(
            'page' => $this->page,
            'order' => $this->order,
            'sort' => $this->sort,
        );
        foreach ($_GET as $k => $v) {
            $data[$k] = $v;
        }
        foreach ($newdata as $k => $v) {
            $data[$k] = $v;
        }
        $url = '/'.$customadminpath.'/addonmodules.php?';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $url .=    '&'.$key.'['.$k.']='.$v;
                }
            } else {
                $url .= '&'.$key.'='.$value;
            }
        }

        return $url;
    }

    /**
     * Toggle sort direction.
     *
     * @param string $sort The sort direction (DESC|ASC).
     *
     * @return string New sort direction.
     */
    public function toggleSort($sort)
    {
        if ($sort == 'DESC') {
            return 'ASC';
        }
        if ($sort == 'ASC') {
            return 'DESC';
        }

        return $sort;
    }
}
