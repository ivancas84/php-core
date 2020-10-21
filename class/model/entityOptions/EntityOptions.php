<?php

require_once("class/model/StructTools.php");

class EntityOptions {

  /**
   * Todos los metodos en general se ejecutan comparando el valor UNDEFINED
   */
  public $prefix = "";
  
  public function _pf(){ return (empty($this->prefix)) ?  ''  : $this->prefix . '_'; } 
  /**
   * prefijo fields
   */
  
  public function _pt(){ return (empty($this->prefix)) ?  $this->container->getEntity($this->entityName)->getAlias() : $this->prefix; }
  /**
   * prefijo tabla
   */

  function _callFields($fieldNames, $method = ""){
		foreach($fieldNames as $fieldName) call_user_func_array([$this, "_".$method],[$fieldName]);
    return $this;
  }

  function _call($method = ""){
    return $this->_callFields($this->container->getEntity($this->entityName)->getFieldNames(), $method);
  }

  function _toArrayFields($fieldNames, $method = ""){
    /**
     * Por cuestiones operativas, el array resultante no define prefijo
     */
    $row = [];
    foreach($fieldNames as $fieldName){
      $r = call_user_func_array([$this, "_".$method],[$fieldName]);
      if($r !== UNDEFINED) $row[$fieldName] = $r ;
    }

    return $row;
  }

  function _toArray($method = ""){
    return $this->_toArrayFields($this->container->getEntity($this->entityName)->getFieldNames(), $method);
  }

  function _fromArrayFields(array $row, $fieldNames, $method = ""){
    if(empty($row)) return $this;

    foreach($fieldNames as $fieldName){      
      if(array_key_exists($this->_pf().$fieldName, $row)) call_user_func_array([$this, "_".$method],[$fieldName, $row[$this->_pf().$fieldName]]);
    }

    return $this;
  }

  function _fromArray(array $row, $method = ""){
    return $this->_fromArrayFields($row, $this->container->getEntity($this->entityName)->getFieldNames(), $method);
  }
  
}