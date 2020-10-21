<?php

require_once("class/model/entityOptions/EntityOptions.php");


class ConditionEntityOptions extends EntityOptions {

  public $mapping;
  public $value;

  protected function labelSearch($option, $value){
     /**
       * combinacion entre label y search
      */
    $cond1 =  $this->_("label",$option, $value);
    $cond2 =  $this->_("search", $option, $value);
    return "({$cond1} OR {$cond2})";
 
  }

  protected function _defineCondition($param){
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
        default: return $this->_defineCondition([$param[0]]);
      }
    }
  }

  public function _($fieldName, $option, $value){
    $m = snake_case_to("xxYy", str_replace(".","_",$fieldName));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), [$option, $value]);
    $param = explode(".",$fieldName);
    $m = $this->_defineCondition($param, $option, $value);
    return call_user_func_array(array($this, $m), [$fieldName,$option, $value]);
  }

  protected function _default($fieldName, $option, $value) { 
    $field = $this->mapping->_($fieldName);
    if($c = $this->sql->exists($field, $option, $value)) return $c;
    if($c = $this->sql->approxCast($field, $option, $value)) return $c;
    $this->value->_set($fieldName, $value);  
    if(!$this->value->_check($fieldName)) throw new Exception("Valor incorrecto al definir condicion _default: " . $this->entityName . " " .$fieldName . " " . $option . " " . $value);
    return "({$field} {$option} {$this->value->_sql($fieldName)}) ";  
  }

  protected function _string($fieldName, $option, $value) { 
    $field = $this->mapping->_($fieldName);
    if($c = $this->sql->exists($field, $option, $value)) return $c;
    if($c = $this->sql->approx($field, $option, $value)) return $c;
    $this->value->_set($fieldName, $value);  
    if(!$this->value->_check($fieldName)) throw new Exception("Valor incorrecto: " . $field . " ". $option . " " .$value);
    return "({$field} {$option} {$this->value->_sql($fieldName)}) ";  
  }

  protected function _boolean($fieldName, $option, $value) { 
    $field = $this->mapping->_($fieldName);
    if($c = $this->sql->exists($field, $option, $value)) return $c;
    $this->value->_set($fieldName, $value);
    if(!$this->value->_check($fieldName)) throw new Exception("Valor incorrecto: " . $field . " ". $option . " " .$value);
    return "({$field} {$option} {$this->value->_sql($fieldName)}) ";  
  }

  protected function _isSet($fieldName, $option, $value) { 
    $field = $this->mapping->_($fieldName);
    return $this->sql->exists($field, $option, settypebool($value));
  }
}