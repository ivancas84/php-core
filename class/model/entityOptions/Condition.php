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

  public function _defineMethod($param){
    $ret = [];
    if(count($param) == 1) {
      $field = $this->container->getField($this->entityName, $param[0]);
      switch ( $field->getDataType() ) {
        case "string": case "text": return "_string"; break;
        case "boolean": return "_boolean"; break;
        default: return "_default";
      }
    } else {
      switch($param[1]) {
        case "count": return "_default";
        case "is_set": return "_isSet";
        default: return $this->_defineMethod([$param[0]]);
      }
    }
  }

  public function _($fieldName, $option, $value){
    $m = snake_case_to("xxYy", str_replace(".","_",$fieldName));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), [$option, $value]);
    $param = explode(".",$fieldName);
    $m = $this->_defineMethod($param, $option, $value);
    return call_user_func_array(array($this, $m), [$fieldName,$option, $value]);
  }

  public function _default($fieldName, $option, $value) { 
    $field = $this->mapping->_($fieldName);
    if($c = $this->sql->exists($field, $option, $value)) return $c;
    if($c = $this->sql->approxCast($field, $option, $value)) return $c;
    $this->value->_set($fieldName, $value);
    if(!$this->value->_check($fieldName)) throw new Exception("Valor incorrecto al definir condicion _default: " . $this->entityName . " " .$fieldName . " " . $option . " " . $value);
    return "({$field} {$option} {$this->value->_sql($fieldName)}) ";  
  }

  public function _string($fieldName, $option, $value) { 
    $field = $this->mapping->_($fieldName);
    if($c = $this->sql->exists($field, $option, $value)) return $c;
    if($c = $this->sql->approx($field, $option, $value)) return $c;
    $this->value->_set($fieldName, $value);
    if(!$this->value->_check($fieldName)) throw new Exception("Valor incorrecto: " . $field . " ". $option . " " .$value);
    return "({$field} {$option} {$this->value->_sql($fieldName)}) ";  
  }

  public function _boolean($fieldName, $option, $value) { 
    $field = $this->mapping->_($fieldName);
    if($c = $this->sql->exists($field, $option, $value)) return $c;
    $this->value->_set($fieldName, $value);
    if(!$this->value->_check($fieldName)) throw new Exception("Valor incorrecto: " . $field . " ". $option . " " .$value);
    return "({$field} {$option} {$this->value->_sql($fieldName)}) ";  
  }

  public function _isSet($fieldName, $option, $value) { 
    $field = $this->mapping->_($fieldName);
    return $this->sql->exists($field, $option, settypebool($value));
  }

 

}