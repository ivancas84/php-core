<?php

require_once("class/model/entityOptions/EntityOptions.php");
require_once("class/tools/Format.php");
require_once("class/tools/SpanishDateTime.php");

class ValueEntityOptions extends EntityOptions {

  /**
   * Cuidado con los metodos setDefault, solo setean el valor por defecto si el campo es UNDEFINED,
   * si es otro valor o null, son ignorados y no setean el valor por defecto.   
   */

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
  
  public $_value = [];

  
  public function _getLogs(){ return $this->_logs; }

  public function setIdentifier($identifier){ return $this->_identifier = $identifier; }
  public function identifier($format = null){ return Format::convertCase($this->identifier, $format); }
  public function jsonIdentifier($format = null){ return $this->identifier(); }
  public function sqlIdentifier(){ return $this->sql->string($this->identifier); }

  public function setCount($count){ return $this->count = $count; }
  public function count(){ return $this->count; }
  public function jsonCount(){ return $this->count(); }
  public function sqlCount(){ return $this->sql->number($this->count); }

  public function setLabel($label){ $this->label = $label; }
  public function label(){ return $this->label; }
  public function jsonLabel(){ return $this->label(); }
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

  protected function _defineSet($fieldName){
    $param = explode(".",$fieldName);
    if(count($param) == 1) {
      switch($this->container->getField($this->entityName, $param[0])->getDataType()) {
        case "year": return "_setYear";
        case "time": case "date": case "timestamp": return "_setDatetime";
        case "integer": return "_setInteger";
        case "float": return "_setFloat";
        case "boolean": return "_setBoolean";
        default: return "_setString";
      }
    } 

    switch($param[1]){
      case "count": return "_setInteger";      
      case "year": case "y": return "_setYear"; 
      case "date": case "ym": case "hm": case "time": return "_setDatetime"; 
      default: return $this->defineSet($param[0]);
    }
  }

  public function _set($fieldName, $value){
    /**
     * @example 
     *   _set("nombre", "something")
     *   _set("nombre.max", "something max");
     *   _set("nombre.count", 10);
     */
    $m = "set".snake_case_to("XxYy", str_replace(".","_",$fieldName));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), [$value]);
    $m = $this->_defineSet($fieldName);
    return call_user_func_array(array($this, $m), [$fieldName, $value]); 
  }

  protected function _setDatetime($fieldName, $p) {
    if(!is_null($p) && !($p instanceof DateTime)) $p = new SpanishDateTime($p);
    if($p instanceof DateTime) $p->setTimeZone(new DateTimeZone(date_default_timezone_get()));
    return $this->_value[$fieldName] = $p;
  }

  protected function _setYear($fieldName, $p){
      if(!is_null($p) && !($p instanceof DateTime)) {
        $p = (strlen($p) == 4) ? SpanishDateTime::createFromFormat('Y', $p) : new SpanishDateTime($p);
      }
      if($p instanceof DateTime) $p->setTimeZone(new DateTimeZone(date_default_timezone_get()));
      return $this->_value[$fieldName] = $p;
  }

  protected function _setString($fieldName, $p) { return $this->_value[$fieldName] = (string)$p; }
  protected function _setInteger($fieldName, $p) { return $this->_value[$fieldName] = (is_null($p)) ? null : intval($p); }
  protected function _setFloat($fieldName, $p) { return $this->_value[$fieldName] = (is_null($p)) ? null : floatval($p); }
  protected function _setBoolean($fieldName, $p) { return $this->_value[$fieldName] = settypebool($p); }

  protected function _defineFastSet($fieldName){
    $param = explode(".",$fieldName);
    if(count($param) == 1) {
      switch($this->container->getField($this->entityName, $param[0])->getDataType()) {
        case "year": return "_setYear";
        case "time": case "date": case "timestamp": return "_setFastDatetime";
        case "integer": return "_setFastInteger";
        case "float": return "_setFastFloat";
        case "boolean": return "_setFastBoolean";
        default: return "_setFastString";
      }
    } 

    switch($param[1]){
      case "count": return "_setFastInteger";      
      case "year": case "y": return "_setYear"; 
      case "date": case "ym": case "hm": case "time": return "_setFastDatetime";     
      default: return $this->defineFastSet($param[0]);
    }
  }

  public function _fastSet($fieldName, $value){
    /**
     * @example 
     *   _fastSet("nombre", "something")
     *   _fastSet("nombre.max", "something max");
     *   _fastSet("nombre.count", 10);
     */
    $m = "fastSet".snake_case_to("XxYy", str_replace(".","_",$fieldName));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), [$value]);
    $m = $this->_defineValueFastSet($fieldName);
    return call_user_func_array(array($this, $m), [$fieldName, $value]); 
  }

  protected function _fastSetDatetime($fieldName, DateTime $p = null) { return $this->_value[$fieldName] = $p; }  
  protected function _fastSetString($fieldName, string $p = null) { return $this->_value[$fieldName] = $p; }  
  protected function _fastSetInteger($fieldName, int $p = null) { return $this->_value[$fieldName] = $p; }  
  protected function _fastSetFloat($fieldName, float $p = null) { return $this->_value[$fieldName] = $p; }  
  protected function _fastSetBoolean($fieldName, boolean $p = null) { return $this->_value[$fieldName] = $p; }  

  protected function _defineSetDefault($fieldName){
    $param = explode(".",$fieldName);
    if(count($param)>1) return null; //los atributos derivados o calculados no tienen valor por defecto (puede que no exista el field)
    
    $field = $this->container->getField($this->entityName, $param[0]);
    switch($field->getDataType()){
      case "date": case "timestamp": case "year": case "time": 
        return (strpos(strtolower($field->getDefault()), "current") !== false) ? date('c') : $field->getDefault();      
      default: return $this->getDefault();
    }
  }

  public function _setDefault($fieldName){
    /**
     * @example 
     *   _setDefault("nombre")
     */
    if(!array_key_exists($fieldName, $this->_value)) {
      $m = "setDefault".snake_case_to("XxYy", str_replace(".","_",$fieldName));
      if(method_exists($this, $m)) return call_user_func(array($this, $m));
      $value = $this->_defineSetDefault($fieldName);    
      return $this->_set($fieldName, $value);
    }
  }


  protected function _defineReset($fieldName){    
    $param = explode(".",$fieldName);
    if(count($param)==1){
      switch($this->container->getField($this->entityName, $param[0])->getDataType()){
        case "string": case "text": return "_resetString"; break;
        default: return null; break;
      }
    }
    
    switch($param[1]){
      default: return $this->_defineReset($param[0]);
    }
  }

  public function _reset($fieldName){
    /**
     * @example 
     *   _setDefault("nombre")
     */
    if(array_key_exists($fieldName, $this->_value)) {
      $m = "reset".snake_case_to("XxYy", str_replace(".","_",$fieldName));
      if(method_exists($this, $m)) return call_user_func(array($this, $m));
      if($m = $this->_defineReset($fieldName)) return call_user_func_array(array($this, $m), [$fieldName]);
    }
  }

  protected function _resetString($fieldName){
    $this->_value[$fieldName] = preg_replace('/\s\s+/', ' ', trim($this->_value[$fieldName]));
  }

  public function _defineGet($fieldName){
    $param = explode(".",$fieldName);
    if(count($param) == 1) {
      switch($this->container->getField($this->entityName, $param[0])->getDataType()) {         
        case "year": case "time": case "date": case "timestamp": return "_getDatetime";
        case "boolean": return "_getBoolean";
        case "string": case "text": return "_getString";
        default: return "_getDefault";
      }
    } 

    switch($param[1]){
      case "count": return "_getDefault";      
      case "year": case "y": case "date": case "ym": case "hm": case "time": return "_getDatetime"; 
      default: return $this->defineGet($param[0]);
    }  
  }

  public function _get($fieldName, $format = null){
    /**
     * @example 
     *   _get("nombre", "Xx Yy")
     *   _get("nombre.max");
     */
    if(!array_key_exists($fieldName, $this->_value)) return UNDEFINED;
    $m = "get".snake_case_to("XxYy", str_replace(".","_",$fieldName));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), [$format]);
    $m = $this->_defineGet($fieldName);
    
    return call_user_func_array(array($this, "_get_".$m), [$fieldName, $format]); 
  }

  protected function _getDatetime($fieldName, $format){ return Format::date($this->_value[$fieldName], $format); }
  protected function _getBoolean($fieldName, $format){ return Format::boolean($this->_value[$fieldName], $format); }
  protected function _getString($fieldName, $format){ return Format::convertCase($this->_value[$fieldName], $format); }
  protected function _getDefault($fieldName, $format){ return $this->_value[$fieldName]; }

  public function _isEmpty($fieldName){
    if(!array_key_exists($fieldName, $this->_value)) return true;
    $m = "isEmpty".snake_case_to("XxYy", str_replace(".","_",$fieldName));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m));
    return (Validation::is_empty($this->_value[$fieldName])) ? true : false;
  }
  
  public function _defineJson($fieldName){    
    $param = explode(".",$fieldName);
    switch($this->container->getField($this->entityName, $param[0])->getDataType()) {         
      case "year": case "time": case "date": case "timestamp": return "_jsonDatetime";
      default: return "_jsonDefault";
    }
  }

  public function _json($fieldName){
    /**
     * @example 
     *   _json("nombre")
     *   _json("nombre.max");
     */
    if(!array_key_exists($fieldName, $this->_value)) return UNDEFINED;
    $m = "json".snake_case_to("XxYy", str_replace(".","_",$fieldName));
    if(method_exists($this, $m)) return call_user_func(array($this, $m));
    $m = $this->_defineJson($fieldName);
    return call_user_func_array(array($this, $m), [$fieldName]);
  }

  protected function _jsonDatetime($fieldName){ return $this->_get($fieldName, "c"); }
  protected function _jsonDefault($fieldName){ return $this->_get($fieldName); }

  protected function _defineSql($fieldName){
    $param = explode(".",$fieldName);
    if(count($param) == 1) {
      $field = $this->container->getField($this->entityName, $param[0]);
      switch($field->getDataType()){
        case "integer": case "float": return "_sqlNumber";
        case "boolean": return "_sqlBoolean";
        case "date": return "_sqlDate";
        case "year": return "_sqlY";
        case "time": return "_sqlTime";
        case "timestamp": return "_sqlTimestamp";
        default: return "_sqlString";
      }
    }

    switch($param[1]){
      case "count": return "_sqlNumber";      
      case "year": case "y": return "_sqlY"; 
      case "ym": return "_sqlYm"; 
      case "hm": return "_sqlHm"; 
      default: return $this->_defineSql($param[0]);
    }
  }

  public function _sql($fieldName){
    /**
     * @example 
     *   _sql("nombre")
     *   _sql("nombre.max");
     */
    if(!array_key_exists($fieldName, $this->_value)) return UNDEFINED;
    $m = "sql".snake_case_to("XxYy", str_replace(".","_",$fieldName));
    if(method_exists($this, $m)) return call_user_func(array($this, $m));
    $m = $this->_defineSql($fieldName);
    return call_user_func_array(array($this, $m), [$fieldName]);    
  }

  protected function _sqlDate($fieldName){ return $this->sql->dateTime($this->_value[$fieldName], "Y-m-d"); }
  protected function _sqlTime($fieldName){ return $this->sql->dateTime($this->_value[$fieldName], "H:i:s"); }
  protected function _sqlTimestamp($fieldName){ return $this->sql->dateTime($this->_value[$fieldName], "Y-m-d H:i:s"); }
  protected function _sqlHm($fieldName){ return $this->sql->dateTime($this->_value[$fieldName], "H:i"); }
  protected function _sqlYm($fieldName){ return $this->sql->dateTime($this->_value[$fieldName], "Y-m"); }
  protected function _sqlY($fieldName){ return $this->sql->dateTime($this->_value[$fieldName], "Y"); }
  protected function _sqlBoolean($fieldName){ return $this->sql->boolean($this->_value[$fieldName], "Y-m"); }
  protected function _sqlNumber($fieldName){ return $this->sql->number($this->_value[$fieldName], "Y-m"); }
  protected function _sqlString($fieldName){ return $this->sql->string($this->_value[$fieldName], "Y-m"); }

  protected function _defineCheck($fieldName){
    $param = explode(".",$fieldName);
    $ret = [];
    if(count($param) == 1) {
      $field = $this->container->getField($this->entityName, $param[0]);
      if($field->isNotNull()) $ret["required"] = "required";
      switch($field->getDataType()){
        case "date": case "timestamp": case "year": case "time": $ret["type"] = "date"; break;
        case "boolean"; $ret["type"] = "boolean"; break;
        case "integer": break;
        default: 
          if($field->getLength()) $ret["max"] = $l;
          if($field->getMinLength()) $ret["min"] = $l;
          return $ret;
      }
    } else {
      switch($param[1]){
        case "count": return ["type"=>"number"];
        default: return $this->_defineCheck($param[0]);
      }
    }

    return $ret;
  }

  public function _check($fieldName){
    /**
     * @example 
     *   _check("nombre")
     *   _check("nombre.max");
     */
    if(!array_key_exists($fieldName, $this->_value)) return null;
    $m = "check".snake_case_to("XxYy", str_replace(".","_",$fieldName));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), [$value]);

    $m = $this->_defineCheck($fieldName);
    $this->_logs->resetLogs($fieldName);
    $v = Validation::getInstanceValue($this->_value[$fieldName]);

    foreach($m as $check => $value){
      switch($check) {
        case "type": case "required": call_user_func(array($v, $value)); break;
        case "min": case "max": call_user_func_array(array($v, $key), [$value]); break;
      }
    }

    foreach($v->getErrors() as $error){ $this->_logs->addLog($fieldName, "error", $error); }
    return $v->isSuccess();
  }

}