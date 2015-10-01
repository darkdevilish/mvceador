<?php
require_once __DIR__.DS.'config.php';
require_once __DIR__.DS.'..'.DS.'inc'.DS.'mappeador'.DS.'Database.php';

function autoload($className){
	/**
	 *With namespace autoload will take the whole namespace name including the
	 *classname, so you need to replace \ with DS to take the namespace as the directory
	 *to autoload, but first you need the directory path.
	 */
	$inc_path = __DIR__.DS.'..'.DS.'inc';
	$controllers_path = __DIR__.DS.'..'.DS.'app'.DS.'controllers';
	$models_path = __DIR__.DS.'..'.DS.'app'.DS.'models';
	$filename = str_replace('\\', DS, $className).'.php';
	if(file_exists($inc_path.DS.$filename) ){
    require_once $inc_path.DS.$filename;
    return true;
  }
  if(file_exists($controllers_path.DS.$filename) ){
    require_once $controllers_path.DS.$filename;
    return true;
  }
  if(file_exists($models_path.DS.$filename) ){
    require_once $models_path.DS.$filename;
    return true;
  }
  	return false; 
}

spl_autoload_register("autoload");

//Load Helpers
require_once __DIR__.DS.'..'.DS.'inc'.DS.'helpers'.DS.'app_helpers.php';
require_once __DIR__.DS.'..'.DS.'inc'.DS.'helpers'.DS.'controller_helpers.php';
require_once __DIR__.DS.'..'.DS.'inc'.DS.'helpers'.DS.'model_helpers.php';
require_once __DIR__.DS.'..'.DS.'inc'.DS.'helpers'.DS.'view_helpers.php';

//Load Routes
require_once __DIR__.DS.'..'.DS.'config'.DS.'routes.php';