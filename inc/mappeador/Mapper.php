<?php
namespace mappeador;
use mappeador\MySQLDatabase;
use mappeador\DatabaseObject;

abstract class Mapper extends DatabaseObject{

  /**
   * Assings property values from array keys if array passed.
   */
  function __construct($params=NULL) {    
    if( !empty($params) ) {
      foreach($params as $param => $val) {
        $this->{$param} = $val;
      }
    }
  }

  function save() {
    //A new record won't have an id
    if( isset($this->id) ) {
      $this->validate('update');
      $this->validate_with('update');
      if( !Validator::errors() ) {
        return $this->update();
      }
    } else {
      $this->validate('create');
      $this->validate_with('create');
      if( !Validator::errors() ) {
        return $this->create();
      }
    }
    $this->set_old_input();
    $_SESSION['errors'] = Validator::errors();
    return false;
  }

  function create() {
    $db = MySQLDatabase::getInstance();
    
    $attrs = $this->attributes();
    unset($attrs['id']);//no need to insert id mysql does it automatically
    $sql = $this->prepared_sql();
    $stmt = $db->prepared_stmt($sql);
    $params_type = static::check_bind_params_type($attrs);
    $bind_result = $stmt->bind_param($params_type, join(", ", $attrs));
    $db->confirm_bind_result($bind_result, $stmt);
    $db->execute($stmt);
    $this->id = $db->insert_id();
    $stmt->free_result();
    $stmt->close();
    return true;
  }

  function update() {
    $db = MySQLDatabase::getInstance();
    
    $attrs = $this->attributes();
    $attrs_pairs = array();
    foreach($attrs as $key => $value){
      $attrs_pairs[] = "{$key}='{$value}'";
    }
    $sql = "UPDATE ".static::$table_name." SET ";
    $sql .= join(", ", $attrs_pairs);
    $sql .= " WHERE id=". "?";
    $stmt = $db->prepared_stmt($sql);
    $bind_result = $stmt->bind_param("i", $this->id);
    $db->confirm_bind_result($bind_result, $stmt);
    $db->execute($stmt);
    $affected_rows = $stmt->affected_rows;
    $stmt->free_result();
    $stmt->close();
    return ($affected_rows == 1) ? true : false;
  }

  function delete() {
    $db = MySQLDatabase::getInstance();

    $sql = "DELETE FROM ".static::$table_name;
    $sql .= " WHERE id=". "?";
    $sql .= " LIMIT 1";
    $stmt = $db->prepared_stmt($sql);
    $bind_result = $stmt->bind_param("i", $this->id);
    $db->confirm_bind_result($bind_result, $stmt);
    $db->execute($stmt);
    $affected_rows = $stmt->affected_rows;
    $stmt->free_result();
    $stmt->close();
    return ($affected_rows == 1) ? true : false;
  }

  protected function attributes() {
    $attributes = array();
    foreach(static::get_db_tbl_fields() as $field) {
      if(property_exists($this, $field)) {
        $attributes[$field] = $this->$field;
      }
    }
    return $attributes;
  }

  private function prepared_attrs() {
    $attrs = array();

    foreach($this->attributes() as $key => $value){
      $attrs[$key] = "?";
    }
    return $attrs;
  }
  
  private function prepared_sql() {
    $attributes = $this->prepared_attrs();
    unset($attributes['id']);//no need to insert id mysql does it automatically
    $sql = "INSERT INTO ".static::$table_name." (";
    $sql .= join(", ", array_keys($attributes));
    $sql .= ") VALUES (";
    $sql .= join(", ", array_values($attributes));
    $sql .= ")";
    return $sql;
  } 

  /**
   * Validates before save if validate property exists, it will loop the associative
   * array and loop the value of key and call dynamically Validator method, if value
   * of key is array it will loop the associative array. 
   */
  private function validate($action) {
    $class_name = get_called_class();
    if( property_exists($class_name, 'validate') ) {
      foreach(static::$validate as $property => $method) {
        foreach($method as $method) {
          if( is_array($method) ) {
            foreach($method as $m => $options) {
              $get_options = array(
                array($property => $options), 
                array($property => $this->{$property})
              );

              $this->call_validation_to_action($m, $get_options, $action);
            }
          } else {
            if(preg_match('/confirmation/', $method)){
              $this->call_confirmation($property, $method, $action);
            } else if(preg_match('/uniqueness/', $method)) {
              $this->call_uniqueness($property, $method, $action);
            } else {
              $options = array( array($property => $this->{$property}) );
              $this->call_validation_to_action($method, $options, $action);
            }
          }
        }
      }
    }
  }

  private function confirmation($property) {
    $input_confirmation = trim(params("{$property}-confirmation"));
    if($input_confirmation !== "") {
      $msg = array("{$property}" => "doesn't match confirmation");
      if($input_confirmation !== $this->{$property}) {
        Validator::add_error($msg);
      }
    } else {
      $msg = array("{$property}" => "confirmation can't be blank");
      Validator::add_error($msg);
    }
  }

  private function validate_with($action) {
    $class_name = get_called_class();
    if( property_exists($class_name, 'validate_with') ) {
      foreach(static::$validate_with as $method) {
        $this->call_validation_with_to_action($class_name, $method, $action);
      }
    }
  }

  private function call_uniqueness($property, $method, $action) {
    $msg = array("{$property}" => "has already been taken");
    if(preg_match('/\[:create\]/', $method) && $action == 'create') {
      if(static::uniqueness($property)) {
        Validator::add_error($msg);
      }
    } else if(preg_match('/\[:update\]/', $method) && $action == 'update') {
      if(static::uniqueness($property)) {
        Validator::add_error($msg);
      }
    } else if(preg_match('/\[:create\]/', $method) && $action == 'update') {
      //do nothing
    } else if(preg_match('/\[:update\]/', $method) && $action == 'create') {
      //do nothing
    } else if($action == 'create') {
      if(static::uniqueness($property)) {
        Validator::add_error($msg);
      }
    }
  }

  private function call_confirmation($property, $method, $action) {
    if(preg_match('/\[:create\]/', $method) && $action == 'create') {
      $this->confirmation($property);
    } else if(preg_match('/\[:update\]/', $method) && $action == 'update') {
      $this->confirmation($property);
    } else if(preg_match('/\[:create\]/', $method) && $action == 'update') {
      //do nothing
    } else if(preg_match('/\[:update\]/', $method) && $action == 'create') {
      //do nothing
    } else if($action == 'create') {
      $this->confirmation($property);
    } else if($action == 'update') {
      $this->confirmation($property);
    }
  }

  private function call_validation_with_to_action($class_name, $method, $action) {
    if(preg_match('/\[:create\]/', $method) && $action == 'create') {
      $this->call_validation_with_method_if_exists($class_name, preg_replace('/\[:create\]/', "", $method));
    } else if(preg_match('/\[:update\]/', $method) && $action == 'update') {
      $this->call_validation_with_method_if_exists($class_name, preg_replace('/\[:update\]/', "", $method));
    } else if(preg_match('/\[:create\]/', $method) && $action == 'update') {
      //do nothing
    } else if(preg_match('/\[:update\]/', $method) && $action == 'create') {
      //do nothing
    } else if($action == 'create') {
      $this->call_validation_with_method_if_exists($class_name, $method);
    } else if($action == 'update') {
      $this->call_validation_with_method_if_exists($class_name, $method);
    }
  }

  private function call_validation_to_action($method, $options, $action) {
    if(preg_match('/\[:create\]/', $method) && $action == 'create') {
      $this->call_validation_method_if_exists(preg_replace('/\[:create\]/', "", $method), $options);
    } else if(preg_match('/\[:update\]/', $method) && $action == 'update') {
      $this->call_validation_method_if_exists(preg_replace('/\[:update\]/', "", $method), $options);
    } else if(preg_match('/\[:create\]/', $method) && $action == 'update') {
      //do nothing
    } else if(preg_match('/\[:update\]/', $method) && $action == 'create') {
      //do nothing
    } else if($action == 'create'){
      $this->call_validation_method_if_exists($method, $options);
    } else if($action == 'update') {
      $this->call_validation_method_if_exists($method, $options);
    }
  }

  private function call_validation_method_if_exists($method, $options) {
    if( method_exists('validator\Validator', "validate_{$method}") ) {
      forward_static_call_array(array('validator\Validator', "validate_{$method}"), $options );
    } else {
      throw new \Exception("Method validator\Validator::validate_{$method} does not exist");
    }
  }

  private function call_validation_with_method_if_exists($class_name, $method) {
    if( method_exists($class_name, $method)) {
      forward_static_call_array(array($class_name, $method), array());
    } else {
      throw new \Exception("Method {$class_name}::{$method} does not exist");
    }
  }

  private function set_old_input() {
    $old_input = array();
    foreach(static::get_db_tbl_fields() as $field) {
      $old_input[$field] = $this->{$field};
    }
    $_SESSION['old_input'] = $old_input;
  }

}
