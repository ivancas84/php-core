<?php

require_once("class/model/entityOptions/EntityOptions.php");


class ConditionEntityOptions extends EntityOptions {

  protected function labelSearch($option, $value){
     /**
       * combinacion entre label y search
      */
    $cond1 =  $this->_("label",$option, $value);
    $cond2 =  $this->_("search", $option, $value);
    return "({$cond1} OR {$cond2})";
 
  }

  public function search($option, $value){
    if($option == "=") $option = "=~";
    elseif($option == "!=") $option = "!=~";
    if(($option != "!=~") && ($option != "=~")) throw new Exception("Opción no válida para 'search'");
    $field = $this->container->mapping($this->entityName, $this->prefix)->_("search");
    return $this->_approxCast($field, $option, $value);  
  }

  protected function _defineCondition($param){
    $ret = [];
    if(count($param) == 1) {
      $field = $this->container->field($this->entityName, $param[0]);
      switch ( $field->getDataType() ) {
        case "string": case "text": return "_string"; break;
        case "boolean": return "_boolean"; break;
        default: return "_default";
      }
    } else {
      switch($param[1]) {
        case "count": return "_default";
        case "is_set": case "exists": return "_exists";
        default: return $this->_defineCondition([$param[0]]);
      }
    }
  }

  public function _(string $fieldName, $option, $value){
    $m = snake_case_to("xxYy", str_replace(".","_",$fieldName));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), [$option, $value]);
    $param = explode(".",$fieldName);
    $m = $this->_defineCondition($param, $option, $value);
    return call_user_func_array(array($this, $m), [$fieldName,$option, $value]);
  }

  protected function _default($fieldName, $option, $value) { 
    $field = $this->container->mapping($this->entityName, $this->prefix)->_($fieldName);
    if($c = $this->_existsAux($field, $option, $value)) return $c;
    if($c = $this->_approxCast($field, $option, $value)) return $c;
    $v = $this->container->value($this->entityName, $this->prefix);
    $v->_set($fieldName, $value);  
    if(!$v->_check($fieldName)) throw new Exception("Valor incorrecto al definir condicion _default: " . $this->entityName . " " .$fieldName . " " . $option . " " . $value);
    return "({$field} {$option} {$v->_sql($fieldName)}) ";  
  }

  protected function _string($fieldName, $option, $value) { 
    $field = $this->container->mapping($this->entityName, $this->prefix)->_($fieldName);
    if($c = $this->_existsAux($field, $option, $value)) return $c;
    if($c = $this->_approx($field, $option, $value)) return $c;
    $v = $this->container->value($this->entityName, $this->prefix);
    $v->_set($fieldName, $value);  
    if(!$v->_check($fieldName)) throw new Exception("Valor incorrecto al definir condicion _string: " . $this->entityName . " " . $fieldName . " ". $option . " " .$value);
    return "({$field} {$option} {$v->_sql($fieldName)}) ";  
  }

  protected function _boolean($fieldName, $option, $value) { 
    $field = $this->container->mapping($this->entityName, $this->prefix)->_($fieldName);
    $v = $this->container->value($this->entityName, $this->prefix);
    $v->_set($fieldName, $value);
    if(!$v->_check($fieldName)) throw new Exception("Valor incorrecto al definir condicion _boolean: " . $this->entityName . " " . $fieldName . " ". $option . " " .$value);
    return "({$field} {$option} {$v->_sql($fieldName)}) ";  
  }

  protected function _exists($fieldName, $option, $value) { 
    $field = $this->container->mapping($this->entityName, $this->prefix)->_($fieldName);
    return $this->_existsAux($field, $option, settypebool($value));
  }


  protected function _existsAux($field, $option, $value) {
    if($value == "" || is_null($value) || $value == "true" || $value == "false" || is_bool($value) ) {
      if (($option != "=") && ($option != "!=")) throw new Exception("La combinacion field-option-value no está permitida para definir existencia: " . $field. " " . $option . " " . $value, 404);

      switch(settypebool($value)){
        case true:
          return ($option == "=") ? "({$field} IS NOT NULL) " : "({$field} IS NULL) ";
        default:
          return ($option == "=") ? "({$field} IS NULL) " : "({$field} IS NOT NULL) ";
      }
    }
  }

  public function _approxCast($field, $option, $value) {
    if($option == "=~") return "(CAST({$field} AS CHAR) LIKE '%{$value}%' )";
    if($option == "!=~") return "(CAST({$field} AS CHAR) NOT LIKE '%{$value}%' )";
  }

  public function _approx($field, $option, $value) {
    if($option == "=~") return "(lower({$field}) LIKE lower('%{$value}%'))";
    if($option == "!=~") return "(lower({$field}) NOT LIKE lower('%{$value}%'))";
  }
}