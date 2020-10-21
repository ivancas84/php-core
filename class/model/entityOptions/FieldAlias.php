<?php

require_once("class/model/entityOptions/EntityOptions.php");

class FieldAliasEntityOptions extends EntityOptions {
  public $mapping;

  public function _($fieldName, array $params = []){
    /**
     * @example 
     *   _("nombre")
     *   _("fecha_alta.max");
     *   _("edad.avg")
     */
    $m = snake_case_to("xxYy", $fieldName);
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), $params);
    return $this->mapping->_($fieldName) . " AS " . $this->_pf() . str_replace(".","_",$fieldName); 
  }

  function _toArrayFields($fieldNames, $method = ""){
    /**
     * Por cuestiones operativas, el array resultante no define prefijo
     */
    $row = [];
    foreach($fieldNames as $fieldName){
      $r = $this->_($fieldName);
      if($r !== UNDEFINED) $row[$fieldName] = $r ;
    }

    return $row;
  }
}