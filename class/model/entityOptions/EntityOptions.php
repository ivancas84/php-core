<?php

require_once("class/controller/StructTools.php");

class EntityOptions {

  public $prefix = "";
  public $entity;
  
  public function _pf(){ return (empty($this->prefix)) ?  ''  : $this->prefix . '_'; } 
  /**
   * prefijo fields
   */
  
  public function _pt(){ return (empty($this->prefix)) ?  $this->entity->getAlias() : $this->prefix; }
  /**
   * prefijo tabla
   */

  function _callFields($fieldNames, $method = ""){
		foreach($fieldNames as $fieldName){
      $call = snake_case_to("xxYy", $method . "_" . $fieldName);
      if(!method_exists($this, $call)) continue;
			$this->$call();
    }
    return $this;
  }

  function _call($method = ""){
    return $this->_callFields($this->entity->getFieldNames(), $method);
  }

  function _toArrayFields($fieldNames, $method = ""){
    /**
     * Por cuestiones operativas, el array resultante no define prefijo
     */
    $row = [];
    foreach($fieldNames as $fieldName){
      $call = snake_case_to("xxYy", $method . "_" . $fieldName);
      if(!method_exists($this, $call)) continue;
      $r = $this->$call();
      if($r !== UNDEFINED) $row[$fieldName] = $r ;
    }

    return $row;
  }

  function _toArray($method = ""){
    return $this->_toArrayFields($this->entity->getFieldNames(), $method);
  }

  function _fromArrayFields(array $row, $fieldNames, $method = ""){
    if(empty($row)) return $this;

    foreach($fieldNames as $fieldName){
      $call = snake_case_to("xxYy", $method . "_" . $fieldName);
      if(!method_exists($this, $call)) continue;
      if(array_key_exists($this->_pf().$fieldName, $row)) $this->$call($row[$this->_pf().$fieldName]);
    }

    return $this;
  }

  function _fromArray(array $row, $method = ""){
    return $this->_fromArrayFields($row, $this->entity->getFieldNames(), $method);
  }

  function _eval($fieldName, array $params = []){
    /**
     * @todo No deberia retornar UNDEFINED? no estoy seguro
     */
    $count = 1;
    /**
     * Si no se especifica count como variable independiente dispara el error Notice: Only variables should be passed by reference in C:\xampp\htdocs\call.php on line 33 field1
     */

    $method = snake_case_to("xxYy", str_replace($this->_pf(), "", $fieldName, $count));
    if(!method_exists($this, $method)) return;
    return call_user_func_array(array($this, $method), $params);
  }


}