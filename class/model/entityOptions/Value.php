<?php

require_once("class/model/entityOptions/EntityOptions.php");

class ValueEntityOptions extends EntityOptions {
  public $_logs;
  /**
   * Logs de verificaciones
   */

  public $sql;
  /**
   * SqlTools
   */

  public $identifier = UNDEFINED; 
  /** 
   * El identificador puede estar formado por campos de la tabla actual o relacionadas
   */

  public $count = UNDEFINED;
  /**
   * Field count
   */

  public $label = UNDEFINED;
  /**
   * Field count
   */
  
  
  public function _getLogs(){ return $this->_logs; }

  public function setIdentifier($identifier){ return $this->_identifier = $identifier; }
  public function identifier($format = null){ return Format::convertCase($this->identifier, $format); }
  public function sqlIdentifier(){ return $this->sql->string($this->identifier); }

  public function setCount($count){ return $this->count = $count; }
  public function count(){ return $this->count; }
  public function sqlCount(){ return $this->sql->number($this->count); }

  public function setLabel($label){ $this->label = $label; }
  public function label(){ return $this->label; }
  public function sqlLabel(){ return $this->sql->string($this->count); }

  public function _equalTo(EntityValues $entityValues){
    /**
     * Retorna true si es igual u otro valor si es diferente (false o string con el nombre del campo)
     * Comparación no estricta, no tiene en cuenta valores nulos o indefinidos
     */
    $a = $this->_toArray();
    $b = $entityValues->_toArray();    
    foreach($a as $ka => $va) {
      if(is_null($va) || !key_exists($ka, $b)) continue;
      if($b[$ka] !== $va) return $ka;
    }
    return true;
  }

  public function _equalToStrict(EntityValues $entityValues){
    /**
     * Retorna true si es igual u otro valor si es diferente (false o string con el nombre del campo)
     * Comparación estricta, los valores deben coincidir si son nulos 
     */
    $a = $this->_toArray();
    $b = $entityValues->_toArray();    
    if($strict) return (empty(array_diff_assoc($a, $b)) && empty(array_diff_assoc($b, $a)))? true : false;
    
  }



}