<?php

require_once("model/entityOptions/EntityOptions.php");
require_once("tools/Format.php");
require_once("tools/SpanishDateTime.php");

/**
 * Manipular valores de una entidad
 * 
 * Cuidado con los metodos setDefault, solo setean el valor por defecto si el campo es UNDEFINED,
 * si es otro valor o null, son ignorados. 
 * El valor por defecto del id siempre se carga en null, el programador debe setearla (habitualmente utilizando la funcion uniqid)
 */
class ValueEntityOptions extends EntityOptions {
  /**
   * Logs de verificaciones
   */
  public $logs;

  /**
   * Conjunto de valores
   * 
   * Los valores se almacenan en un array asociativo
   * 
   * Ejemplo de elementos almacenados
   *   nombres
   *   nombres.max //valor maximo 
   */
  public $value = [];
  
  
  public function _getLogs(){ return $this->logs; }

  public function _equalTo(ValueEntityOptions $value){
    /**
     * Retorna true si es igual u otro valor si es diferente (false o string con el nombre del campo)
     * Comparación no estricta, no tiene en cuenta valores nulos o indefinidos
     */
    $a = $this->_toArray("sql");
    $b = $value->_toArray("sql");    

    foreach($a as $ka => $va) {
      if(is_null($va) || !key_exists($ka, $b)) continue;
      if($b[$ka] !== $va) return $ka;
    }
    return true;
  }

  public function _unset($field_name){
      unset($this->value[$field_name]);
  }
  
  public function _equalToStrict(ValueEntityOptions $value){
    /**
     * Retorna true si es igual u otro valor si es diferente (false o string con el nombre del campo)
     * Comparación estricta, los valores deben coincidir si son nulos 
     */
    $a = $this->_toArray("get");
    $b = $value->_toArray("get");    
    if($strict) return (empty(array_diff_assoc($a, $b)) && empty(array_diff_assoc($b, $a)))? true : false;
    
  }

  protected function _defineSet($field_name){
    $param = explode(".",$field_name);
    if(count($param) == 1) {
      switch($this->container->field($this->entity_name, $param[0])->getDataType()) {
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
      default: return $this->_defineSet($param[0]);
    }
  }

  public function _set($field_name, $value){
    /**
     * @example 
     *   _set("nombre", "something")
     *   _set("nombre.max", "something max");
     *   _set("nombre.count", 10);
     */
    $m = "set".snake_case_to("XxYy", str_replace(".","_",$field_name));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), [$value]);
    $m = $this->_defineSet($field_name);
    return call_user_func_array(array($this, $m), [$field_name, $value]); 
  }

  protected function _setDatetime($field_name, $p) {
    if(!empty($p) && !($p instanceof DateTime)) $p = new SpanishDateTime($p);
    if($p instanceof DateTime) $p->setTimeZone(new DateTimeZone(date_default_timezone_get()));
    return $this->value[$field_name] = $p;
  }

  protected function _setYear($field_name, $p){
      if(!empty($p) && !($p instanceof DateTime)) {
        $p = (strlen($p) == 4) ? SpanishDateTime::createFromFormat('Y', $p) : new SpanishDateTime($p);
      }
      if($p instanceof DateTime) $p->setTimeZone(new DateTimeZone(date_default_timezone_get()));
      return $this->value[$field_name] = $p;
  }

  protected function _setString($field_name, $p) {
    $this->value[$field_name] = (string)$p; 
    return $this->value[$field_name];
  }
  protected function _setInteger($field_name, $p) { return $this->value[$field_name] = (is_null($p)) ? null : intval($p); }
  protected function _setFloat($field_name, $p) { return $this->value[$field_name] = (is_null($p)) ? null : floatval($p); }
  protected function _setBoolean($field_name, $p) { return $this->value[$field_name] = settypebool($p); }

  protected function _defineFastSet($field_name){
    $param = explode(".",$field_name);
    if(count($param) == 1) {
      switch($this->container->field($this->entity_name, $param[0])->getDataType()) {
        case "year": return "_setYear";
        case "time": case "date": case "timestamp": return "_fastSetDatetime";
        case "integer": return "_fastSetInteger";
        case "float": return "_fastSetFloat";
        case "boolean": return "_fastSetBoolean";
        default: return "_fastSetString";
      }
    } 

    switch($param[1]){
      case "count": return "_fastSetInteger";      
      case "year": case "y": return "_setYear"; 
      case "date": case "ym": case "hm": case "time": return "_fastSetDatetime";     
      default: return $this->defineFastSet($param[0]);
    }
  }

  public function _fastSet($field_name, $value){
    /**
     * @example 
     *   _fastSet("nombre", "something")
     *   _fastSet("nombre.max", "something max");
     *   _fastSet("nombre.count", 10);
     */
    $m = "fastSet".snake_case_to("XxYy", str_replace(".","_",$field_name));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), [$value]);
    $m = $this->_defineFastSet($field_name);
    return call_user_func_array(array($this, $m), [$field_name, $value]); 
  }

  protected function _fastSetDatetime($field_name, DateTime $p = null) { return $this->value[$field_name] = $p; }  
  protected function _fastSetString($field_name, string $p = null) { return $this->value[$field_name] = $p; }  
  protected function _fastSetInteger($field_name, int $p = null) { return $this->value[$field_name] = $p; }  
  protected function _fastSetFloat($field_name, float $p = null) { return $this->value[$field_name] = $p; }  
  protected function _fastSetBoolean($field_name, bool $p = null) { return $this->value[$field_name] = $p; }  

  protected function _defineSetDefault($field_name){
    $param = explode(".",$field_name);
    if(count($param)>1) return null; //los atributos derivados o calculados no tienen valor por defecto (puede que no exista el field)
    
    $field = $this->container->field($this->entity_name, $param[0]);
    switch($field->getDataType()){
      case "date": case "timestamp": case "year": case "time": 
        return (strpos(strtolower($field->getDefault()), "cur") !== false) ? date('c') : $field->getDefault();
      default: return $field->getDefault();
    }
  }

  public function _setDefault($field_name){
    /**
     * @example 
     *   _setDefault("nombre")
     */
    if(!array_key_exists($field_name, $this->value)) {
      $m = "setDefault".snake_case_to("XxYy", str_replace(".","_",$field_name));
      if(method_exists($this, $m)) return call_user_func(array($this, $m));
      $value = $this->_defineSetDefault($field_name);    
      return $this->_set($field_name, $value);
    }
  }


  protected function _defineReset($field_name){  
    /**
     * Definir metodo de reset a ejecutar.
     * 
     * Busca la configuracion del field_name indicado, y selecciona el metodo 
     * mas adecuado.
     **/  
    $param = explode(".",$field_name);
    if(count($param)==1){
      switch($this->container->field($this->entity_name, $param[0])->getDataType()){
        case "string": case "text": return "_resetString"; break;
        default: return null; break;
      }
    }
    
    switch($param[1]){
      default: return $this->_defineReset($param[0]);
    }
  }

  public function _reset($field_name){
    /**
     * Reseteo de campo.
     * 
     * El reseteo da formato a un campo para ser almacenado correctamente, por
     * ejemplo, para una cadena de caracteres, _reset elimina espacios en
     * blanco duplicados y al principo y final de la cadena.
     */
    if(array_key_exists($field_name, $this->value)) { //reset se ejecuta solo si el campo existe en el conjunto de valores
      $m = "reset".snake_case_to("XxYy", str_replace(".","_",$field_name)); //definir metodo exclusivo
      if(method_exists($this, $m)) return call_user_func(array($this, $m)); //ejecutar, si existe, metodo exlusivo
      if($m = $this->_defineReset($field_name)) //buscar metodo predefinido en funcion de la configuracion del campo
        return call_user_func_array(array($this, $m), [$field_name]); //ejecutar metodo predefinido
    }
  }

  protected function _resetString($field_name){
    /**
     * Metodo de reseteo de strings
     */
    $this->value[$field_name] = preg_replace('/\s\s+/', ' ', trim($this->value[$field_name]));
  }

  public function _defineGet($field_name){
    $param = explode(".",$field_name);
    if(count($param) == 1) {
      switch($this->container->field($this->entity_name, $param[0])->getDataType()) {         
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

  public function _get($field_name, $format = null){
    /**
     * @example 
     *   _get("nombre", "Xx Yy")
     *   _get("nombre.max");
     */

    $m = "get".snake_case_to("XxYy", str_replace(".","_",$field_name));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), [$format]);
    if(!array_key_exists($field_name, $this->value)) return UNDEFINED;
    $m = $this->_defineGet($field_name);
    
    return call_user_func_array(array($this, $m), [$field_name, $format]); 
  }

  protected function _getDatetime($field_name, $format){ return Format::date($this->value[$field_name], $format); }
  protected function _getBoolean($field_name, $format){ return Format::boolean($this->value[$field_name], $format); }
  protected function _getString($field_name, $format){ return Format::convertCase($this->value[$field_name], $format); }
  protected function _getDefault($field_name, $format){ return $this->value[$field_name]; }

  public function _isEmpty($field_name){
    if(!array_key_exists($field_name, $this->value)) return true;
    $m = "isEmpty".snake_case_to("XxYy", str_replace(".","_",$field_name));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m));
    return (Validation::is_empty($this->value[$field_name])) ? true : false;
  }
  
  public function _defineJson($field_name){    
    $param = explode(".",$field_name);
    switch($this->container->field($this->entity_name, $param[0])->getDataType()) {         
      case "year": case "time": case "date": case "timestamp": return "_jsonDatetime";
      default: return "_jsonDefault";
    }
  }

  public function _json($field_name){
    /**
     * @example 
     *   _json("nombre")
     *   _json("nombre.max");
     */
    if(!array_key_exists($field_name, $this->value)) return UNDEFINED;
    $m = "json".snake_case_to("XxYy", str_replace(".","_",$field_name));
    if(method_exists($this, $m)) return call_user_func(array($this, $m));
    $m = $this->_defineJson($field_name);
    return call_user_func_array(array($this, $m), [$field_name]);
  }

  protected function _jsonDatetime($field_name){ return $this->_get($field_name, "c"); }
  protected function _jsonDefault($field_name){ return $this->_get($field_name); }

  protected function _defineSql($field_name){
    $param = explode(".",$field_name);
    if(count($param) == 1) {
      $field = $this->container->field($this->entity_name, $param[0]);
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
      case "date": return "_sqlDate";
      default: return $this->_defineSql($param[0]);
    }
  }

  public function _sql($field_name){
    /**
     * @example 
     *   _sql("nombre")
     *   _sql("nombre.max");
     */
    if(!array_key_exists($field_name, $this->value)) return UNDEFINED;
    $m = "sql".snake_case_to("XxYy", str_replace(".","_",$field_name));
    if(method_exists($this, $m)) return call_user_func(array($this, $m));
    $m = $this->_defineSql($field_name);
    return call_user_func_array(array($this, $m), [$field_name]);    
  }


  
  protected function _sqlDateTime($value, $format){
    if(Validation::is_undefined($value)) return UNDEFINED;
    if(Validation::is_empty($value)) return 'null';
    return "'" . $value->format($format) . "'";
  }

  protected function _sqlDate($field_name){ return $this->_sqlDateTime($this->value[$field_name], "Y-m-d"); }
  protected function _sqlTime($field_name){ return $this->_sqlDateTime($this->value[$field_name], "H:i:s"); }
  protected function _sqlTimestamp($field_name){ return $this->_sqlDateTime($this->value[$field_name], "Y-m-d H:i:s"); }
  protected function _sqlHm($field_name){ return $this->_sqlDateTime($this->value[$field_name], "H:i"); }
  protected function _sqlYm($field_name){ return $this->_sqlDateTime($this->value[$field_name], "Y-m"); }
  protected function _sqlY($field_name){ return $this->_sqlDateTime($this->value[$field_name], "Y"); }
  protected function _sqlBoolean($field_name){ 
    if(Validation::is_undefined($this->value[$field_name])) return UNDEFINED;
    return ( $this->value[$field_name] ) ? 'true' : 'false';
  }

  protected function _sqlNumber($field_name){ 
    if(Validation::is_undefined($this->value[$field_name])) return UNDEFINED;
    if(is_null($this->value[$field_name]) || $this->value[$field_name] === "") return "null";
    return $this->value[$field_name];
  }

  protected function _sqlString($field_name){ 
    if(Validation::is_undefined($this->value[$field_name])) return UNDEFINED;
    if(Validation::is_empty($this->value[$field_name])) return 'null';
    return "'" . $this->container->db()->escape_string($this->value[$field_name]) . "'";  
  }

  protected function _defineCheck($field_name){
    $param = explode(".",$field_name);
    $ret = [];
    if(count($param) == 1) {
      $field = $this->container->field($this->entity_name, $param[0]);
      if($field->isNotNull()) $ret["required"] = "required";
      switch($field->getDataType()){
        case "date": case "timestamp": case "year": case "time": $ret["type"] = "date"; break;
        case "boolean"; $ret["type"] = "boolean"; break;
        case "integer": break;
        default: 
          if($field->getLength()) $ret["maxLength"] = $field->getLength();
          if($field->getMin()) $ret["min"] = $field->getMin();
          if($field->getMax()) $ret["max"] = $field->getMax();
          return $ret;
      }
    } else {
      switch($param[1]){
        case "count": return ["type"=>"integer"];
        default: return $this->_defineCheck($param[0]);
      }
    }

    return $ret;
  }

  public function _check($field_name, $param = null){
    /**
     * chequear valor de un campo
     * el campo debe existir en valor para ser chequeado sino retorna null
     * Los metodos de chequeo definidos por el usuario, deben utilizar un solo parametro
     * En el caso de que se requieran varios parametros, utilizar uno solo definido como array
     * @example 
     *   _check("nombre");
     *   _check("nombre.max"); //funcion de agregacion
     *   _check("nombre_parecidos", $existente); //definido por el usuario
     * 
     * Los metodos definidos por el usuario, pueden llamarse directamente
     * En vez de _check("nombre_parecidos", $existente) se invoca checkNombresParecidos($existente);
     */
    
    $m = "check".snake_case_to("XxYy", str_replace(".","_",$field_name));
    if(method_exists($this, $m)) return call_user_func_array(array($this, $m), [$param]);
    /**
     * En primer lugar se verifica la existencia del metodo
     * Un metodo definido puede acceder a diferentes valores no indicados en field_name
     * Por ejemplo Persona->checkNombresParecidos accede a los valores "nombres" y "apellidos"
     */

    if(!array_key_exists($field_name, $this->value)) return null;
    /**
     * Si no existe metodo definido por el usuario 
     * se verifica la existencia de valor para el fieldname
     */
    
     $m = $this->_defineCheck($field_name);
     $this->logs->resetLogs($field_name);
    $v = Validation::getInstanceValue($this->value[$field_name]);

    foreach($m as $check => $value){
      switch($check) {
        case "type": case "required": 
          call_user_func(array($v, $value)); break;
        case "min": case "max": case "maxLength": 
          call_user_func_array(array($v, $check), [$value]); break;
      }
    }

    foreach($v->getErrors() as $error){ $this->logs->addLog($field_name, "error", $error); }
    return $v->isSuccess();
  }

  public function _toString() {
    $fields = [];
    foreach($this->_toArray("json") as $field){
        if(!Validation::is_empty($field)) array_push($fields, $field);
    }
    return implode(",",$fields);
  }

}