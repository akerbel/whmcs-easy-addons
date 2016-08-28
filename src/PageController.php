<?php

namespace whmcsEasyAddons;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

/**
 * Class for fast creating of tabs on module admin pages.
 */
class PageController
{
    /** @var \Smarty Contain a smarty instance */
    public $view;

    /** @var string Contain chosen tabs name */
    public $action;

    /** @var string Contain a module link */
    public $modulelink;

    /** @var array Contain an array of module tabs */
    public $menu = array();

    /**
     * Constructor.
     *
     * @param array $vars "vars" array from WHCMS.
     */
    public function __construct($vars = array())
    {
        global $templates_compiledir, $customadminpath, $module, $_LANG, $CONFIG;

        // Create smarty
        $this->view = new \Smarty();
        $this->view->template_dir = ROOTDIR.'/modules/addons/'.$module.'/templates/';
        $this->view->compile_dir = $templates_compiledir;

        // Assing WHMCS system params
        $this->view->assign('_LANG', $_LANG);
        $this->view->assign('_CONFIG', $CONFIG);
        $this->view->assign('csrfToken', generate_token('plain'));

        // Assing our module params
        $this->vars = $vars;
        $this->view->assign('vars', $this->vars);
        $this->view->assign('customadminpath', $customadminpath);
        $this->modulelink = '/'.$customadminpath.'/addonmodules.php?module='.$module;
        $this->view->assign('modulelink', $this->modulelink);
        if (isset($_REQUEST['action'])) {
            $this->action = $_REQUEST['action'];
        }
        $this->view->assign('action', $this->action);
    }

    /**
     * Execute chosen tabs action.
     *
     * @throws \Exception If it is not possible to find a method for chosen action.
     */
    public function run()
    {
        // Show tabs
        $this->_displayMenu();

        // Try to execute action method
        $methodName = $this->action.'Action';
        try {
            if (method_exists($this, $methodName)) {
                $this->$methodName();
                echo $this->view->fetch($this->action.'.tpl');
            } else {
                throw new \Exception('No controller for '.$this->action);
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Generate and echo html-code for tabs.
     */
    private function _displayMenu()
    {
        if (count($this->menu)) {
            $menu = '<div id="clienttabs"><ul class="nav nav-tabs client-tabs">';
            foreach ($this->menu as $title => $element) {
                if ($this->action == $element) {
                    $menu .= '<li class="active">';
                } else {
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
