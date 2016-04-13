<?php
namespace AKerbel;

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

class ItemList {
	
	/**
	 * номер показываемой страницы
	 */
	public $page = 1;
	
	/**
	 * элементов на странице
	 */
	public $perpage = 20;
	
	/**
	 * имя столбца для сортировки по умолчанию
	 */
	public $order = 'id';
	
	/**
	 * направление сортировки по умолчанию
	 */
	public $sort = 'DESC';
	
	/**
	 * колличество элементов всего
	 */
	public $count;
	
	/**
	 * количество страниц
	 */
	public $maxpage;
	
	/**
	 * количество страниц до и после текущей, отрисовываемых в пагинаторе
	 */
	public $pages_in_paginator = 4;
	
	/**
	 * массив элементов
	 */
	public $result = array();
	
	/**
	 * код заголовков таблицы с кнопками сортировки и указателями
	 */
	public $tablehead;
	
	/**
	 * пагинатор
	 */
	public $paginator;
	
	/**
	 * настройки из WHMCS
	 */
	public $vars;
	
	/**
	 * фильтр
	 */
	public $filter;
	
	/**
	 * параметры фильтра
	 */
	private $filter_params;
	
	/**
	 * параметры запроса
	 */
	private $sql_array;
	
	/**
	 * конструктор
	 */
	public function __construct($sql = array(), $filter_params = array(), $tablehead_array = array()){
		$this->sql_array = $sql;
		$this->filter_params = $filter_params;
		$this->createList();
	}
	
	/**
	 * создает массив элементов
	 * @param array $sql - массив из элементов которого формируется запрос, оставлен для обратной совместимости со старыми версиями
	 */
	public function createList($sql = array()){
		if (!count($sql))$sql = $this->sql_array;
		
		if ($_GET['page'] != NULL) $this->page = $_GET['page'];
		if ($_GET['order'] != NULL) $this->order = $_GET['order'];	
		if ($_GET['sort'] != NULL) $this->sort = $_GET['sort'];
		
		if (count($this->filter_params) and is_array($this->filter_params)){
			foreach ($this->filter_params as $param){
				if ($param['value']){
					if ($param['type'] == 'LIKE'){
						$sql['WHERE'] .= ' '.$param['name'].' LIKE "%'.$param['value'].'%" ';
					}elseif($param['type'] == 'IN'){
						$sql['WHERE'] .= ' '.$param['name'].' IN ('.$param['value'].') ';
					}else{
						$sql['WHERE'] .= ' '.$param['name'].' = "'.$param['value'].'" ';
					}
				}
			}		
		}
		$this->sql_array = $sql;
		
		//формируем запрос
		$_sql = 
			" SELECT ".$this->sql_array['SELECT'] .
			" FROM ".$this->sql_array['FROM'].
			(isset($this->sql_array['INNER JOIN']) ? " INNER JOIN ".$this->sql_array['INNER JOIN'] : "").
			(isset($this->sql_array['LEFT JOIN']) ? " LEFT JOIN ".$this->sql_array['LEFT JOIN'] : "").
			(isset($this->sql_array['RIGHT JOIN']) ? " RIGHT JOIN ".$this->sql_array['RIGHT JOIN'] : "").
			(isset($this->sql_array['WHERE']) ? " WHERE ".$this->sql_array['WHERE'] : "").
			" ORDER BY ".$this->order." ".$this->sort.
			" LIMIT ".(($this->page-1)*$this->perpage).", ".$this->perpage."
		";
		$result_data = full_query($_sql);
		$result = array();
		while ($line = mysql_fetch_assoc($result_data)){
			$result[] = $line;
		}
		$this->result = $result;
		$this->_createTablehead();
		$this->_createPaginator();
		$this->_createFilter();
	}
	
	/**
	 * создает $tablehead - html-код заголовков таблицы вместе с кнопками и указателями сортировки
	 */
	private function _createTablehead(){
		$th = '';
		if (isset($this->result[0]) and is_array($this->result[0])) {
			$th .= '<tr>';
			foreach (array_keys($this->result[0]) as $head){
				$th .= '<th>';
				if ($this->order != $head){ 
					$th .= '<a href="'.$this->getUrl(array('order'=>$head)).'">'.$head.'</a>';
				}else{
					$th .= '<a href="'.$this->getUrl(array('order'=>$head, 'sort'=>$this->toggleSort($this->sort))).'">';
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
	 * создает $paginator - html-код пагинатора
	 */
	private function _createPaginator(){
		//если не указана таблица, то ничего не получится
		if ($this->sql_array['FROM']){
			//узнаем количество элементов всего
			$count = mysql_fetch_assoc(full_query("
				SELECT count(*) AS count 
				FROM ".$this->sql_array['FROM'].
				(isset($this->sql_array['INNER JOIN']) ? " INNER JOIN ".$this->sql_array['INNER JOIN'] : "").
				(isset($this->sql_array['LEFT JOIN']) ? " LEFT JOIN ".$this->sql_array['LEFT JOIN'] : "").
				(isset($this->sql_array['RIGHT JOIN']) ? " RIGHT JOIN ".$this->sql_array['RIGHT JOIN'] : "").
				(isset($this->sql_array['WHERE']) ? " WHERE ".$this->sql_array['WHERE'] : "")
			));
			$this->count = $count['count'];
			//узнаем количество страниц
			$this->maxpage = floor($count['count']/$this->perpage);
			//если у нас есть неполная страничка в конце, прибавляем к количеству страниц единичку
			if (ceil($count['count']/$this->perpage) != $this->maxpage) $this->maxpage += 1;
			//генерируем код пагинатора из полученных данных о страницах
			$res = '';
			if ($this->page>1)
				$res .= '<a href="'.$this->getUrl(array('page' => ($this->page-1))).'" class="paginator prevpage"><<</a>';
			if ($this->page-$this->pages_in_paginator > 1)
				$res .= '<a href="'.$this->getUrl(array('page' => 1)).'" class="paginator firstpage">1</a>..';
			for ($i = $this->page-$this->pages_in_paginator; $i <= $this->page+$this->pages_in_paginator; $i++) {
				if (($i>0) and ($i<=$this->maxpage)){
					if ($i!= $this->page) 
						$res .= '<a href="'.$this->getUrl(array('page'=>$i)).'" class="paginator page">'.$i.'</a>';
					else
						$res .= '<span class="paginator currentpage"><b>'.$i.'</b></span>';
				}
			}
			if ($this->page+$this->pages_in_paginator < $this->maxpage)
				$res .= '..<a href="'.$this->getUrl(array('page' => ($this->maxpage))).'" class="paginator lastpage">'.$this->maxpage.'</a>';
			if ($this->page<$this->maxpage)
				$res .= '<a href="'.$this->getUrl(array('page' => ($this->page+1))).'" class="paginator nextpage">>></a>';
			
			$this->paginator = $res;
		}else{
			$this->paginator = 'You have to pass the value "FROM"';
		}
	}
	
	/**
	 * создает html-код формы фильтра
	 * @param array $params - массив массивов с элементами
	 *	name - техническое имя параметра, как в базе
	 *	value - значение параметра
	 *	description - описание параметра
	 *	type - тип поиска. Если элемент пуст, то используется простое сравнение.
	 *		возможнные значения элемента type:
	 *			LIKE - будет использоватся операнд %LIKE%
	 *			IN - будет использоватся операнд IN (...)
	 */
	private function _createFilter(){
		$filter = '<form name="ak_filter" method="GET" action="" style="display:none">';
		$filter .= '<table class="ak_filter">';
		foreach ($this->filter_params as $param){
			$filter .= '<tr>';
				$filter .= '<td>'.$param['description'].'</td><td><input type="text" value="'.$param['value'].'" name="filter['.$param['name'].']"></td>';
			$filter .= '</tr>';
		}
		foreach ($_GET as $name=>$value){
			if ($name != 'filter'){
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
	 * генерирует адрес страницы со всеми сортировками и выбранными страницами
	 * @param array $newdata - массив параметров, значение которых отличается от значения по умолчанию
	 */
	public function getUrl($newdata = array()){
		global $customadminpath, $module;
		$data = array(
			'page' => $this->page,
			'order' => $this->order,
			'sort' => $this->sort,
		);
		foreach ($_GET as $k=>$v){
			$data[$k] = $v;
		}
		foreach ($newdata as $k=>$v){
			$data[$k] = $v;
		}
		$url = '/'.$customadminpath.'/addonmodules.php?';
		foreach ($data as $key=>$value){
			if (is_array($value)){
				foreach ($value as $k=>$v){
					$url .=	'&'.$key.'['.$k.']='.$v;
				}
			}else{
				$url .= '&'.$key.'='.$value;
			}
		}
		return $url;
	}
	
	/**
	 * Превращает одну сортировку в другую
	 * @param string $sort - изначальная сортировка
	 */
	public function toggleSort($sort){
		if ($sort == 'DESC') return 'ASC';
		if ($sort == 'ASC') return 'DESC';
		return $sort;
	}
}
?>