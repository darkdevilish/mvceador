<?php
use routeador\Route;

function params($param) {
	if(is_array($param)) {
		$params = array();
		foreach($_POST as $parameter => $val) {
			foreach($param as $permit) {
				if($permit == $parameter) {
					$params[$permit] = $val;
				}
			}
		}
		return $params;
	} else {
		if( isset($_POST[$param]) ) {
			return $_POST[$param];
		}
	}

	if($param == 'id') {
		return (int) Route::submit()['params'][$param];
	} else {
		return Route::submit()['params'][$param];
	}
}

function redirect_to( $location = NULL ) {
  if ($location != NULL) {
    header("Location: {$location}");
    exit;
  }
}
