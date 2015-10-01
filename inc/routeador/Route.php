<?php
namespace routeador;

abstract class Route extends AltoRouter{

	static function set_base_path($path) {
		self::get_instance()->setBasePath($path);
	}

	static function add($method, $path, $action, $name) {
		self::get_instance()->map($method, $path, $action, $name);
	}

	static function submit() {
		return self::get_instance()->match();
	}

	static function add_routes($array) {
		self::get_instance()->addRoutes($array);
	}

	static function generate_path($route_name, array $params = array()) {
		return self::get_instance()->generate($route_name, $params);
	}

	static function get_routes() {
		return self::get_instance()->getRoutes();
	}

	static function add_match_types($match_types) {
		self::get_instance()->addMatchTypes($match_types);
	}

	static function root_to($target) {
		self::get_instance()->map('GET', '/', $target, 'root');
	}

	static function resources($resource, $exceptions_array=NULL) {
		if( !empty($exceptions_array) ) {
			self::map_resources($resource, $exceptions_array);
		} else {
			self::map_resources($resource);
		}
	}

	static function call_target_method($match) {
		if($match) {
		  $target = explode('#', $match['target']);
			$controller = ucfirst($target[0]) . "Controller";
			$method = $target[1];
  		forward_static_call_array(array($controller, $method), array($target[0], $target[1]));
		} else {
  		require '404.php';
  		exit;
		}
	}

	private static function map_resources($resource, $exceptions_array=NULL) {
		$array = array();
		$actions = self::get_actions_array($resource);
		if( !empty($exceptions_array) ) {
			$actions = self::get_actions_array($resource, $exceptions_array);
		}

		foreach($actions as $action=>$method_path) {
			$method_path_array = explode('@', $method_path);
			$method = $method_path_array[0];
			$path = $method_path_array[1];

			$array[] = array(
				$method , 
				$path , 
				$resource . '#' . $action , 
				$resource . '_' . $action
			);
		}
		
		self::get_instance()->addRoutes($array);
	}

	/** 
		* @return associative array of resources actions and paths, if array pass will
		* unset based on action key from actions array when except pass as key and when 
		* only pass as key will unset from actions array whatever actions is not pass.
		*/
	private static function get_actions_array($resource, $exceptions_array=NULL) {
		//action name as key and method@path as value
		$actions = array(
			'index' => 'GET@/' . $resource,
			'new_' . substr($resource, 0, -1) => 'GET@/' . $resource . '/new',
			'create' => 'POST@/' . $resource,
			'show' => 'GET@/' . $resource . '/[i:id]',
			'edit' => 'GET@/' . $resource . '/[i:id]/edit',
			'update' => 'PATCH|POST@/' . $resource . '/[i:id]',
			'delete' => 'POST@/' . $resource . '/[i:id]/delete',
			'destroy' => 'DELETE@/' . $resource . '/[i:id]'
		);

		if( !empty($exceptions_array) ) {
			foreach($exceptions_array as $exception=>$action) {
				if($exception == "except") {
					foreach($action as $a) {
						unset($actions[$a]);
			 		}
				} else if($exception == "only") {
					foreach( $actions as $a=>$m_p ) {
						if( !in_array( $a, $action ) ) {
							unset($actions[$a]);
						}
					}
				} else {
					throw new \Exception("Wrong array key {$exception}, use only or except");
				}
			}
		}
		return $actions;
	}

}