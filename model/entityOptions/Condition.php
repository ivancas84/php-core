<?php

require_once("model/entityOptions/EntityOptions.php");

/**
 * Definir condicion a traves de 3 elementos "field, option y value" donde value es un valor válido para el field.
 */
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
    $field = $this->container->mapping($this->entity_name, $this->prefix)->map("search");
    return $this->_approxCast($field, $option, $value);  
  }

  protected function _defineCondition($field_name){
    $param = explode(".",$field_name);
    $ret = [];
    if(count($param) == 1) {
      //traducir nombre de field sin funcion
      $field = $this->container->field($this->entity_name, $param[0]);
      switch ( $field->getDataType() ) {
        case "string": case "text": return "_string"; break;
        case "boolean": return "_boolean"; break;
        default: return "_default";
      }
    } else {
      //traducir funcion
      switch($param[1]) {
        case "count": return "_default";
        case "is_set": case "exists": return "_exists";
        default: return $this->_defineCondition([$param[0]]);
      }
    }
  }

  /**
   * @example Ejemplos de metodos redefinidos por el usuario
   * numeroDocumento($option, $value) //definicion de condicion para el field_name "numero_documento"
   * numeroDocumentoMax($option, $value) //definicion de condicion para el field_name "numero_documento.max"
   */
  public function _(string $field_name, $option, $value){
    $m = snake_case_to("xxYy", str_replace(".","_",$field_name));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), [$option, $value]);
    $m = $this->_defineCondition($field_name, $option, $value);
    return call_user_func_array(array($this, $m), [$field_name,$option, $value]);
  }

  protected function _default($field_name, $option, $value) { 
    $field = $this->container->mapping($this->entity_name, $this->prefix)->map($field_name);
    if($c = $this->_existsAux($field, $option, $value)) return $c;
    if($c = $this->_approxCast($field, $option, $value)) return $c;
    $v = $this->container->value($this->entity_name, $this->prefix);
    $v->_set($field_name, $value);  
    if(!$v->_check($field_name)) throw new Exception("Valor incorrecto al definir condicion _default: " . $this->entity_name . " " .$field_name . " " . $option . " " . $value);
    return "({$field} {$option} {$v->_sql($field_name)}) ";  
  }

  protected function _string($field_name, $option, $value) {
    $field = $this->container->mapping($this->entity_name, $this->prefix)->map($field_name);
    if($c = $this->_existsAux($field, $option, $value)) return $c;
    if($c = $this->_approx($field, $option, $value)) return $c;
    $v = $this->container->value($this->entity_name, $this->prefix);
    
    $v->_set($field_name, $value);  
    if(!$v->_check($field_name)) throw new Exception("Valor incorrecto al definir condicion _string: " . $this->entity_name . " " . $field_name . " ". $option . " " .$value);
    
    return "({$field} {$option} {$v->_sql($field_name)}) ";  
  }

  protected function _boolean($field_name, $option, $value) { 
    $field = $this->container->mapping($this->entity_name, $this->prefix)->map($field_name);
    $v = $this->container->value($this->entity_name, $this->prefix);
    $v->_set($field_name, $value);
    if(!$v->_check($field_name)) throw new Exception("Valor incorrecto al definir condicion _boolean: " . $this->entity_name . " " . $field_name . " ". $option . " " .$value);
    return "({$field} {$option} {$v->_sql($field_name)}) ";  
  }

  protected function _exists($field_name, $option, $value) { 
    $field = $this->container->mapping($this->entity_name, $this->prefix)->map($field_name);
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