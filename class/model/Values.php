<?php

require_once("class/tools/SpanishDateTime.php");
require_once("class/tools/Validation.php");
require_once("class/tools/Format.php");
require_once("class/tools/Logs.php");

require_once("function/snake_case_to.php");


abstract class EntityValues {
  /**
   * Facilita la manipulacion de valores
   * Prioriza tipos básicos
   *   Ej. Para una fecha presenta dos metodos de seteo 
   *     setFecha, recibe un string
   *     _setFecha, recibe un DateTime
   * 
   * Define una estructura de firmas
   *   Los metodos sin prefijo ni sufijo se utilizan para manipular campos
   *   El prefijo _ en los atributos y metodos indica metodo auxiliar asociado a campos
   * 
   * Define una estructura de verificación
   *   Se basa en el uso de Logs y Validations
   *   Se ignoran los valores distintos de UNDEFINED
   *   Los chequeos se realizan principalmente al setear campos
   *   IMPORTANTE: si el campo no se setea, no se chequea.
   *   Para asegurarse de que todos los campos esten seteados, se puede utilizar el metodo _setDefault();
   *   Se recomienda al instanciar asignar valores por defecto y luego valores adicionales
   *     $v = EntityValues::getInstanceRequired("entity", DEFAULT_VALUES);
   *     $v->_fromArray($somthing);
   *   Una vez realizados los chequeos se puede utilizar el atributo logs para obtener el estado final
   *     $this->_logs()->isError(), $this->_logs()->isErrorKey("campo"), $this->_logs()->getLogs(), 
   */


  protected $_identifier = UNDEFINED; 
  /** 
   * El identificador puede estar formado por campos de la tabla actual o relacionadas
   */
  
  protected $_logs;
  /**
   * Logs de verificaciones
   */



  public function __construct(){
    $this->_logs = new Logs();
  }

  abstract public function _fromArray(array $row = NULL);
  abstract public function _isEmpty();
  abstract public function _setDefault();
  public function _logs(){ return $this->_logs; }


  public static function getInstance($values = NULL, $prefix = "") { //crear instancias de values
    $className = get_called_class();
    $instance = new $className;
    if(!empty($values)) $instance->_setValues($values, $prefix);
    return $instance;
  }
  
  final public static function getInstanceRequire($entity, $values= null, $prefix = "") {
    $dir = "class/model/values/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $prf = "";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      $prf = "_";
      require_once($dir.$prf.$name);
    }
    
    $className = $prf.snake_case_to("XxYy", $entity);
    return call_user_func_array("{$className}::getInstance", [$values, $prefix]);
  }

  protected function _setLogsValidation($field, Validation $validation){
    $this->_logs->resetLogs($field);
    foreach($validation->getErrors() as $data){ $this->_logs->addLog($field, "error", $data); }
    return $validation->isSuccess();
  }

  public function _setIdentifier($identifier){ $this->_identifier = $identifier; }
  public function _identifier($format = null){ return Format::convertCase($this->_identifier, $format); }

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


