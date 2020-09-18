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
     * El identificador se define concatenando campos de la entidad principal y de entidades relacionadas que permitan una identificacion unica
     */
    $field = $this->mapping->identifier();
    if($c = $this->sql->exists($field, $option, $value)) return $c;
    if($c = $this->sql->approx($field, $option, $value)) return $c;
    $this->value->setIdentifier($value);
    return "({$field} {$option} {$this->value->sqlIdentifier()})";
  }

  public function count($option, $value){
    /**
     * Utilizar solo como condicion general
     * No utilizar prefijo para su definicion
     */
    $field = $this->mapping->count();
    if($c = $this->sql->exists($field, $option, $value)) return $c;
    $this->value->setCount($value);
    return "({$field} {$option} {$this->value->sqlCount()})";
  }

  public function label($option, $value){
    $field = $this->mapping->label();
    if($c = $this->sql->exists($field, $option, $value)) return $c;
    if($c = $this->sql->approx($field, $option, $value)) return $c;
    $this->value->setLabel($value);
    return "({$field} {$option} {$this->value->sqlLabel()})";
  }

  public function labelSearch($option, $value){
     /**
       * combinacion entre label y search
      */
    $cond1 =  $this->label($option, $value);
    $cond2 =  $this->search($option, $value);
    return "({$cond1} OR {$cond2})";
 
  }


}