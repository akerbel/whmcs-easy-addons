WHMCS Easy Addons
=================

WHMCS Easy Addons is a package for fast creating of addon admin pages.
Homepage: https://github.com/akerbel/whmcs-easy-addons

How to use:
==============
I. Admin tabs.
1. Your "_output" function should look like this:
```php
function EasyAddonsSkeleton_output($vars) {
	global $module;
    include_once(ROOTDIR."/modules/addons/".$module.'/src/output.php'); 
}
```
2. Create 'src' folder.
3. Create output.php file in 'src' folder.
output.php:
```php
namespace EasyAddonsSkeleton;
global $module;
include_once(ROOTDIR."/modules/addons/".$module.'/vendor/autoload.php');

$EasyAddonsSkeleton = new EasyAddonsSkeletonController($vars);
$EasyAddonsSkeleton->run();
```
4. Create EasyAddonsSkeletonController.php in 'src' folder.
EasyAddonsSkeletonController.php:
```php
namespace EasyAddonsSkeleton;

use whmcsEasyAddons;

class EasyAddonsSkeletonController extends PageController {
    // This tab will be chosen by dafault
    public $action = 'firsttab';
	
    // This is array of your tabs and their names.
	public $menu = array(
		'First Tab' => 'firsttab',
        'Second Tab' => 'secondtab',
	);
	
	public function firsttabAction(){
		
        // Your code for "First tab"
		
	}
    
    public function secondtabAction(){
		
        // Your code for "Second tab"
        
	}
}
```
5. Create folder 'templates'.
6. Create .tpl file for each your tab in 'templates' folder with names like 'firsttab.tpl' and 'secondtab.tpl'.
7. You can assing your php variables in each 'Action' method:
```php
$this->view->assign('smarty_variable', $your_php_variable);
```

====================
II. Fast item lists.
1. Example code:
```php
    $list = new ItemList(
    
        // SQL params array. Read more in ItemList.php
        array(
            'SELECT' => '*',
            'FROM' => 'tblmodulelog',
        ),
        
        // Filters array. Read more in ItemList.php
        array(
            array(
                'name' => 'module', 'value' => $_GET['filter']['module'], 'description' => 'module'
            ),
            array(
                'name' => 'action', 'value' => $_GET['filter']['action'], 'description' => 'action'
            ),
            array(
                'name' => 'request', 'value' => $_GET['filter']['request'], 'description' => 'request', 'type' => 'LIKE'
            ),
            array(
                'name' => 'response', 'value' => $_GET['filter']['response'], 'description' => 'response', 'type' => 'LIKE'
            ),
        )
    );
    $this->view->assign('result', $list->result);
    $this->view->assign('paginator', $list->paginator);
    $this->view->assign('tablehead', $list->tablehead);
    $this->view->assign('filter', $list->filter);
```
2. Now you can use this variables in your .tpl file^
$filter - show a filter form.
$paginator - show a page navigation.
$tablehead - show a tablehead with sort buttons.
$result - show an array of items.