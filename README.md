WHMCS Easy Addons
=================

WHMCS Easy Addons is a package for fast creating of addon admin pages.

Homepage: https://github.com/akerbel/whmcs-easy-addons

How to install:
===============
The recommended way to install WHMCS Easy Addons is through Composer.

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Next, run the Composer command to install the latest stable version of WHMCS Easy Addons:

```bash
php composer.phar require akerbel/whmcs-easy-addons
```

How to use:
==============
I. Admin tabs.
- Install Composer
```bash
curl -sS https://getcomposer.org/installer | php
```
- Create composer.json file inside your addon:
```json
{
    "name": "EasyAddonsSkeleton",
    "version": "1.0.0",
    "description": "An example addon for WHMCS Easy Addons",
    "autoload": {
        "psr-4": {
            "EasyAddonsSkeleton\\": "src/"
        }
    },
    "require": {
        "akerbel/whmcs-easy-addons": "1.*"
    }
}
```
- Your "_output" function should look like this:
```php
function EasyAddonsSkeleton_output($vars) {
	global $module;
    include_once(ROOTDIR."/modules/addons/".$module.'/src/output.php'); 
}
```
- Create 'src' folder.
- Create output.php file in 'src' folder.

output.php:
```php
namespace EasyAddonsSkeleton;
global $module;
include_once(ROOTDIR."/modules/addons/".$module.'/vendor/autoload.php');

$EasyAddonsSkeleton = new EasyAddonsSkeletonController($vars);
$EasyAddonsSkeleton->run();
```
- Create EasyAddonsSkeletonController.php in 'src' folder.

EasyAddonsSkeletonController.php:
```php
namespace EasyAddonsSkeleton;

use whmcsEasyAddons;

class EasyAddonsSkeletonController extends whmcsEasyAddons\PageController {
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
- Create folder 'templates'.
- Create .tpl file for each your tab in 'templates' folder with names like 'firsttab.tpl' and 'secondtab.tpl'.
- You can assing your php variables in each 'Action' method:
```php
$this->view->assign('smarty_variable', $your_php_variable);
```

====================
II. Fast item lists.
- Example code:
```php
    $list = new whmcsEasyAddons\ItemList(
    
        // SQL params array. Read more in ItemList.php
        array(
            'SELECT' => '*',
            'FROM' => 'tblmodulelog',
        ),
        
        // Filters array. Read more in ItemList.php . Optional.
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
        ), 
        
        // Paginators type. Read more in a changelog for v1.2 . Optional.
        'dafault'
    );
    $this->view->assign('result', $list->result);
    $this->view->assign('paginator', $list->paginator);
    $this->view->assign('tablehead', $list->tablehead);
    $this->view->assign('filter', $list->filter);
```
- Now you can use this variables in your .tpl file:

1. $filter - show a filter form.
2. $paginator - show a page navigation.
3. $tablehead - show a tablehead with sort buttons.
4. $result - show an array of items.

New in v1.1:
==============
1. Now JOIN parameters can be arrays. It means, now you can use few similar JOINs in your query.
2. ORDER and SORT parameters was added . If you use JOINs in your query, you must define you ORDER parameter, because 'ORDER BY' statement will use default 'id' and it will not work with JOINs.

Fixes in v1.1:
==============
1. The bug with namespaces of Exception class was fixed.
2. Spacebars was added between pages in a paginator.

New in v1.2:
==============
1. Types of the paginator added. Enabled 'default' and 'short'. 
'default' type is an old paginator with numbers.
'short' type is a new fast paginator. Use it if you work with a very big table. It includes only two buttons: previous page and next page. Stylized as a default WHMCS module logs` paginator.