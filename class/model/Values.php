<?php

require_once("function/snake_case_to.php");
require_once("class/SpanishDateTime.php");
require_once("class/Check.php");

abstract class EntityValues { //manipulacion de valores de una entidad
  /**
   * Facilita la manipulacion de valores
   * Prioriza tipos básicos
   * Ej. Para una fecha presenta dos metodos de seteo (setFecha y _setFecha), el primero recibe un string y el segundo un DateTime
   * Los metodos sin prefijo ni sufijo se utilizan para manipular campos
   * Se utiliza el prefijo _ en los atributos y metodos para indicar metodo auxiliar asociado a campos
   * Se puede utilizar _logs para definir chequeos
   * En caso de incompatibilidad, define el valor con la constante UNDEFINED.
   * Los chequeos se realizan al setear campos
   * Se ignoran los valores distintos de UNDEFINED
   */

  /**
  public $_warnings = [];
   * @deprecated utilizar $_logs
   */
  
  /**
  public $_errors = [];
   * @deprecated utilizar $_logs
   */

  protected $_identifier = UNDEFINED; //el identificador puede estar formado por campos de la tabla actual o relacionadas
  
  protected $_logs;
  /**
   * Chequeos
   */

  /**
  public function _addWarning($warning) { array_push($this->_warnings, $warning); }
  /**
   * @deprecated utilizar $_logs
   */

  /**
  public function _addError($error) { array_push($this->_errors, $error); }
  /**
   * @deprecated utilizar $_logs
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

  public static function getInstanceString($entity, $values = NULL, $prefix = "") { //crear instancias de values
    $className = snake_case_to("XxYy", $entity) . "";
    $instance = call_user_func_array("{$className}::getInstance",[$values, $prefix]);
    return $instance;
  }

  final public static function getInstanceRequire($entity, $values = null, $prefix = "") {
    require_once("class/model/values/" . snake_case_to("xxYy", $entity) . "/" . snake_case_to("XxYy", $entity) . ".php");
    return self::getInstanceString($entity, $values, $prefix);
    
  }

  public function _setLogsValidation($field, Validation $validation){
    $this->_logs->reset($field);
    foreach($validation->getErrors() as $data){ $this->_logs->add($field, "error", $data); }
    return $validation->isSuccess();
  }


  public function _setIdentifier($identifier){ $this->_identifier = $identifier; }
  public function _identifier($format = null){ return $this->format->string($this->_identifier, $format); }

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


