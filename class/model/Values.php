<?php

require_once("function/snake_case_to.php");
require_once("class/SpanishDateTime.php");


abstract class EntityValues { //manipulacion de valores de una entidad
  /**
   * Facilita la manipulacion de valores
   * Prioriza tipos básicos
   *  Ej. Para una fecha presenta dos metodos de seteo (setFecha y _setFecha), el primero recibe un string y el segundo un DateTime
   * Los metodos sin prefijo ni sufijo se utilizan para manipular campos
   * Se utiliza el prefijo _ en los atributos y metodos para indicar metodo auxiliar asociado a campos
   * Por defecto, en caso de incompatibilidad, define el valor como NULL.
   *   Se puede utilizar el metodo _check para definir chequeos, solo deberá verificar los valores distintos de UNDEFINED.
   *   Si se necesitan chequeos adicionales se puede verificar al setear campos y utilizar los métodos _addWarning, _addError y _addCheck.
   */

  public $_warnings = [];
  /**
   * @deprecated utilizar $_checks
   */
  public $_errors = [];
  /**
   * @deprecated utilizar $_checks
   */

  public $_identifier = UNDEFINED; //el identificador puede estar formado por campos de la tabla actual o relacionadas
  
  public $_checks = [];
  /**
   * Chequeos
   */

  public function _addWarning($warning) { array_push($this->_warnings, $warning); }
  /**
   * @deprecated utilizar $_checks
   */

  public function _addError($error) { array_push($this->_errors, $error); }
  /**
   * @deprecated utilizar $_checks
   */

  public function _setCheck($id, $key, $status, $data){
    if(!key_exists($id, $this->checks)) $this->checks[$id] = [];
    $this->checks[$id][$key] = ["status" => $status, "data" => $data];
  }

  public function _checkStatus($id){
    if(!key_exists($id, $this->checks)) return UNDEFINED;

    $status = UNDEFINED;
    
    foreach($this->checks as $key => $value){
      switch ($value["status"]) {
        case "error": return "error"; 
        default: $status = $value["status"];        
      }              
    }

    return $status;
  }

  public function _check($id){
    if(!key_exists($id, $this->checks)) return UNDEFINED;
    return $this->checks[$id];

  }

  public function _checkKey($id, $key){
    if(!key_exists($id, $this->checks)) return UNDEFINED;
    if(!key_exists($key, $this->checks[$id])) return UNDEFINED;
    return $this->checks[$id][$key];
  }

  abstract public function _fromArray(array $row = NULL);
  abstract public function _isEmpty();


  //abstract public function setDefault();

  public static function getInstance($values = NULL, $prefix = "") { //crear instancias de values
    $className = get_called_class();
    $instance = new $className;
    if(!empty($values)) $instance->_setValues($values, $prefix);
    return $instance;
  }

  public static function getInstanceString($entity, $values = NULL, $prefix = "") { //crear instancias de values
    $className = snake_case_to("XxYy", $entity) . "";
    $instance = call_user_func_array("{$className}::getInstance",[$values, $prefix]);
    return $instance;
  }

  final public static function getInstanceRequire($entity, $values = null, $prefix = "") {
    require_once("class/model/values/" . snake_case_to("xxYy", $entity) . "/" . snake_case_to("XxYy", $entity) . ".php");
    return self::getInstanceString($entity, $values, $prefix);
    
  }

  public function _isUndefinedValue($value) { //es indefinido
    return ($value === UNDEFINED) ? true : false;
  }

  public function _isEmptyValue($value) { //esta vacio
    return ($value === UNDEFINED || empty($value)) ? true : false;
  }

  protected function _formatDate($value, $format = 'd/m/Y'){
    if($this->_isEmptyValue($value)) return null;
    if(gettype($value) === "string") $value = SpanishDateTime::createFromFormat("Y-m-d", $value);
    return ($value) ? $value->format($format) : null;
  }

  protected function _formatString($value, $format = null){
    if($this->_isEmptyValue($value)) return null;
    switch($format){
      case "XxYy": return str_replace(" ", "", ucwords(mb_strtolower($value, "UTF-8")));
      case "xxyy": case "xy": case "x": return str_replace(" ", "", mb_strtolower($value, "UTF-8"));
      case "Xx Yy": return ucwords(mb_strtolower($value, "UTF-8"));
      case "Xx yy": case "X y": return ucfirst(mb_strtolower($value, "UTF-8"));
      case "xxYy": return str_replace(" ", "", lcfirst(ucwords(mb_strtolower($value, "UTF-8"))));
      case "xx-yy": case "x-y": return mb_strtolower(str_replace(" ", "-", $value), "UTF-8");
      case "XX YY": case "X Y": case "X": return mb_strtoupper($value, "UTF-8");
      case "XY": case "XXYY": return str_replace(" ", "", mb_strtoupper($value, "UTF-8"));
      case "xx yy": case "x y": return mb_strtolower($value, "UTF-8");

      default: return $value;
    }
  }

  protected function _formatBoolean($value, $format = null){

    if($this->_isUndefinedValue($value)) return null;
    switch($format){
      case strpos(mb_strtolower($format), "si") !== false:
      case strpos(mb_strtolower($format), "sí") !== false:
      case strpos(mb_strtolower($format), "no") !== false:   
        return (settypebool($value)) ? "Sí" : "No";
      case strpos(mb_strtolower($format), "s") !== false:
      case strpos(mb_strtolower($format), "n") !== false:              
        return (settypebool($value)) ? "S" : "N";
      default:         
        return $value;
    }
  }

  public function _setIdentifier($identifier){ $this->_identifier = $identifier; }
  public function _identifier($format = null){ return $this->_formatString($this->_identifier, $format); }
  
  public function _setValues($values, $prefix = ""){
    if(is_string($values) && ($values == DEFAULT_VALUE || $values == "DEFAULT") ) $this->_setDefault();
    elseif(is_array($values)) $this->_fromArray($values, $prefix);
  }

  public function _equalTo(EntityValues $entityValues, $strict = false){
    $a = $this->_toArray();
    $b = $entityValues->_toArray();    
    if($strict) return (empty(array_diff_assoc($a, $b)) && empty(array_diff_assoc($b, $a)))? true : false;
    foreach($a as $ka => $va) {
      if(is_null($va) || !key_exists($ka, $b)) continue;
      if($b[$ka] !== $va) return false;
      
    }
    return true;
  }

}
