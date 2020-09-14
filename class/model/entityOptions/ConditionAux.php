<?php

require_once("class/model/entityOptions/EntityOptions.php");

class ConditionAuxEntityOptions extends EntityOptions {

  public $mapping;
  public $format;

  public function _pf(){ return $this->mapping->_pf(); } 
  /**
   * prefijo fields
   */
  
  public function _pt(){  return $this->mapping->_pt(); }
  /**
   * prefijo tabla
   */
  
  public function compare($option, $value) {
    /** USO SOLO COMO CONDICION GENERAL */  
    $f1 = $this->mapping->eval($value[0]);
    $f2 = $this->mapping->eval($value[1]);
    return "({$f1} {$option} {$f2})";
  }

}