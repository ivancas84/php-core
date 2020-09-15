<?php

require_once("class/model/entityOptions/EntityOptions.php");

class ConditionEntityOptions extends EntityOptions {

  public $mapping;
  public $value;
  
  public function search($option, $value){
    /**
     * define la misma condicion y valor para todos los campos de la entidad
     */
    if(($option != "=~") && ($option != "=")) throw new Exception("Opción no permitida para condición " . $this->entity->getName("XxYy") . "Sql._conditionSearch([\"_search\",\"{$option}\",\"{$value}\"]). Solo se admite opcion = o =~");
    $option = "=~";
    //condicion estructurada de busqueda que involucra a todos los campos estructurales (excepto booleanos)
    $conditions = [];
    foreach($this->entity->getFieldsNf() as $field){
      if($field->getDataType() == "boolean") continue;
      $method = $field->getName("xxYy");
      $c = $this->$method($option,$value);
      array_push($conditions, $c);
    }

    return implode(" OR ", $conditions);
  }

  public function identifier($option, $value){
    /**
       * Utilizar solo como condicion general
       * El identificador se define a partir de campos de la entidad principal y de entidades relacionadas
       * No utilizar prefijo para su definicion
       */
    return $this->format->conditionText($this->mapping->identifier($field), $value, $option);
  }

  public function count($option, $value){
    /**
       * Utilizar solo como condicion general
       * No utilizar prefijo para su definicion
       */
    return $this->format->conditionNumber($this->mapping->count($field), $value, $option);
  }

  public function label($option, $value){
    return $this->format->conditionText($this->mapping->label($field), $value, $option);
  }

  public function labelSearch($option, $value){
     /**
       * combinacion entre label y search
      */
    $cond1 =  $this->label($option, $value);
    $cond2 =  $this->search($option, $value);
    return "({$cond1} OR {$cond2})";
 
  }

  protected function _exists($field, $option, $value) {
    if(empty($value) || $value == "true" || $value == "false" || is_bool($value) ) {
      if (($option != "=") && ($option != "!=")) throw new Exception("La combinacion field-option-value no está permitida");

      $field = $this->mapping->_eval($field);
      switch(settypebool($value)){
        case true:
          return ($option == "=") ? "({$field} IS NOT NULL) " : "({$field} IS NULL) ";
        default:
          return ($option == "=") ? "({$field} IS NULL) " : "({$field} IS NOT NULL) ";
      }
    }
  }

  protected function _approxCast($field, $option, $value) {
    if($option == "=~") return "lower(CAST({$field}) AS CHAR) LIKE lower('%{$value}%') )";
    if($option == "!=~") return "lower(CAST({$field}) AS CHAR) NOT LIKE lower('%{$value}%') )";
  }

  protected function _approx($field, $option, $value) {
    if($option == "=~") return "(lower({$field}) LIKE lower('%{$value}%'))";
    if($option == "!=~") return "(lower({$field}) NOT LIKE lower('%{$value}%'))";
  }

}