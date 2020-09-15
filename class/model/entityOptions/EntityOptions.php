<?php

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

  protected function _switchFieldNames($fieldNames){
    if(is_array($fieldNames)) return $fieldNames;
    switch($fieldNames){
      case "EXCLUSIVE": return $this->entity->getFieldNamesExclusive();
      default: return $this->entity->getFieldNames();
    }
  }

  function _call($method = "", $fieldNames = null){
		foreach($this->_switchFieldNames($fieldNames) as $fieldName){
      $call = snake_case_to("xxYy", $method . "_" . $fieldName);
			$this->$call();
    }
    return $this;
  }

  function _toArray($method = "", $fieldNames = null){
    /**
     * Por cuestiones operativas, el array resultante no define prefijo
     */
    $fieldNames = $this->_switchFieldNames($fieldNames);

    $row = [];
    foreach($fieldNames as $fieldName){
      $call = snake_case_to("xxYy", $method . "_" . $fieldName);
      if($r = $this->$call() !== UNDEFINED) $row[$fieldName] = $r ;
    }

    return $row;
  }
  
  function _fromArray(array $row, $method = "", $fieldNames = null){
    $fieldNames = $this->_switchFieldNames($fieldNames);
    if(empty($row)) return;

    foreach($fieldNames as $fieldName){
      $call = snake_case_to("xxYy", $method . "_" . $fieldName);
      if(array_key_exists($this->_pf().$fieldNames, $row)) $this->$call($row[$this->_pf().$fieldName]);
    }

    return $this;
  }

  public function _callConcat($glue = ",", $fieldNames = null){
    $r = [];
    foreach($this->_switchFieldNames($fieldNames) as $fieldName) array_push($r, $this->_evals($fieldName));
    return implode($glue, $r);
  }

  function _eval($fieldName, array $params = []){
    $count = 1;
    /**
     * Si no se especifica count como variable independiente dispara el error Notice: Only variables should be passed by reference in C:\xampp\htdocs\call.php on line 33 field1
     */

    $method = snake_case_to("xxYy", str_replace($this->_pf(), "", $fieldName, $count));
    if(!method_exists($this, $method)) return;
    return call_user_func_array(array($this, $method), $params);
  }

  function _evals($fieldName, array $params = []){
    $count = 1;
    /**
     * Si no se especifica count como variable independiente dispara el error Notice: Only variables should be passed by reference in C:\xampp\htdocs\call.php on line 33 field1
     */

    $method = snake_case_to("xxYy", str_replace($this->_pf(), "", $fieldName, $count));
    return call_user_func_array(array($this, $method), $params);
  }

}