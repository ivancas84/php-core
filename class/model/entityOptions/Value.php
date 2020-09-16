<?php

require_once("class/model/entityOptions/EntityOptions.php");

class ValueEntityOptions extends EntityOptions {
  public $_logs;
  /**
   * Logs de verificaciones
   */

  public $identifier = UNDEFINED; 
  /** 
   * El identificador puede estar formado por campos de la tabla actual o relacionadas
   */
  
  
  public function _getLogs(){ return $this->_logs; }

  public function setIdentifier($identifier){ $this->_identifier = $identifier; }
  public function identifier($format = null){ return Format::convertCase($this->identifier, $format); }

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

  public function _toString(){
    $fields = [];
    foreach($this->_toArray() as $field){
      if(!Validation::is_empty($field)) array_push($fields, $field);
    }
    return implode(", ",$fields);
  }

  public function _sqlDateTime($value, $format){
    if(Validation::is_empty($value)) return 'null';
    return "'" . $value->format($format) . "'";
  }

  public function _sqlBoolean($value){
    if(Validation::is_empty($value)) return 'null';
    return ( $value ) ? 'true' : 'false';
  }

  public function _sqlString($value){
    if(Validation::is_empty($value)) return 'null';
    return "'" . $this->container->getDb()->escape_string($value) . "'";
  }

  public function _sqlNumber($value){
    if(Validation::is_empty($value)) return 'null';
    return $value;
  }

}