<?php

require_once("class/model/entityOptions/EntityOptions.php");

class ConditionEntityOptions extends EntityOptions {

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
    /**
     * Utilizar solo como condicion general
     */
    $f = $this->mappingField($field);
    return $this->format->conditionText($this->mapping->label($field), $value, $option);
  }

  public function labelSearch($option, $value){
     /**
       * combinacion entre label y search
      */
    $f = $this->mappingField($p."_label");
    $cond1 =  $this->format->conditionText($f, $value, $option);
    $cond2 =  $this->search($option, $value);
    return "({$cond1} OR {$cond2})";
 
  }
}