<?php
namespace whmcsaddonadminpagecontroller;

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

class PageController {

    public $view;
	public $action;
	public $modulelink;
	public $menu = array();
	
	public function __construct($vars) {
		global $templates_compiledir, $customadminpath, $module;;
	
		$this->view = new Smarty();
		$this->view->template_dir = $_SERVER['DOCUMENT_ROOT']."/modules/addons/".$module.'/templates/';
		$this->view->compile_dir = $templates_compiledir;
		$this->view->assign('_LANG', $LANG);
		$this->view->assign('_CONFIG', $CONFIG);
		$this->view->assign("csrfToken", generate_token("plain"));
		
		$this->vars = $vars;
		$this->view->assign('vars', $this->vars);
		$this->view->assign('customadminpath', $customadminpath);
		$this->modulelink = '/'.$customadminpath.'/'.$this->vars['modulelink'];
		if (isset($_REQUEST['action'])) $this->action = $_REQUEST['action'];
		$this->view->assign('action', $this->action);
	}
	
	public function run(){
		$this->_displayMenu();
		$methodName = $this->action.'Action';
		try {
			if(method_exists($this, $methodName)) {
				$result = $this->$methodName();
				echo $this->view->fetch($this->action.".tpl");
			}else throw new Exception('Нет контроллера для '.$this->action);
		} catch(Exception $e) {
			echo $e->getMessage();
		}	
	}
	
	private function _displayMenu(){
		if (count($this->menu)){
			$menu = '<div id="clienttabs"><ul class="nav nav-tabs client-tabs">';
			foreach ($this->menu as $title=>$element){
				if ($this->action == $element){
					$menu .= '<li class="active">';
				}else{
					$menu .= '<li class="tab">';
				}
				$menu .= '<a href="'.$this->modulelink.'&action='.$element.'" title="'.$title.'">'.$title.'</a>';
				$menu .= '</li>';
			}
			$menu .= '</ul></div>';
			echo $menu;
		}
	}
}
?>