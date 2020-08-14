<?php

require_once("class/tools/SpanishDateTime.php");
require_once("class/tools/Validation.php");
require_once("class/tools/Format.php");
require_once("class/tools/Logs.php");
require_once("function/snake_case_to.php");

abstract class EntityValues {
  /**
   * Manipulación de valores de una entidad
   * 
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
   *   Se define un método independiente de chequeo para evitar un doble chequeo si se manipulan valores de la base de datos
   *     Se supone que si estan en la base es porque fueron chequeados.
   *   
   *   IMPORTANTE: si el campo no se setea, no se chequea.
   *   Para asegurarse de que todos los campos esten seteados, se puede utilizar el metodo _setDefault();
   *     $v = EntityValues::getInstanceRequired("entity")->setDefault()->_fromArray($something);
   *     $v->_fromArray($somthing);
   * 
   *   Una vez realizados los chequeos se puede utilizar el atributo logs para obtener el estado final
   *     $v->_check()->_getLogs()->isError();
   *     $v->_getLogs()->isErrorKey("campo")
   *     $this->_getLogs()->getLogs(), 
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
  
  abstract public function _reset();
    /**
     * el reseteo consiste en redefinir un valor al atributo en base a ciertas condiciones
     * no implica que el valor este erroneo, sino que puede ser mejor formateado
     * el seteo realiza un formato inicial, el reseteo un formato adicional que requiere mas tiempo de ejecucion
     * por ejemplo el usuario ingresa el nombre en mayusculas y conviene que este primero con mayusculas y despues con minusculas
     * el reseteo debería hacerse antes del chequeo de errores, un valor sin resetear puede ser considerado erroneo
     * antes de resetear si no esta vacio o indefinido
     * No se realiza el reseteo directamente en el seteo porque demanda tiempo de ejecucion,
     * sobre todo si el valor se obtiene de la base de datos
     * si un valor se setea de la base de datos se supone que esta bien
     * por eso el reseteo se define como un metodo aparte que debe ser invocado si corresponde
     */

  abstract public function _check();
  abstract public function _fromArray(array $row = NULL, string $prf = "");
  abstract public function _toArray(string $prf = "");
  
  abstract public function _isEmpty();
  abstract public function _setDefault();
  public function _getLogs(){ return $this->_logs; }

  public function _setIdentifier($identifier){ $this->_identifier = $identifier; }
  public function _getIdentifier($format = null){ return Format::convertCase($this->_identifier, $format); }

  public function _setValues($values, $prefix = ""){
    if(is_string($values) && ($values == DEFAULT_VALUE || $values == "DEFAULT") ) $this->_setDefault();
    elseif(is_array($values)) $this->_fromArray($values, $prefix);
  }

  public function _equalTo(EntityValues $entityValues, $strict = false){
    /**
     * Retorna true si es igual u otro valor si es diferente (false o string con el nombre del campo)
     */
    $a = $this->_toArray();
    $b = $entityValues->_toArray();    
    if($strict) return (empty(array_diff_assoc($a, $b)) && empty(array_diff_assoc($b, $a)))? true : false;
    foreach($a as $ka => $va) {
      if(is_null($va) || !key_exists($ka, $b)) continue;
      if($b[$ka] !== $va) return $ka;
      
    }
    return true;
  }

}