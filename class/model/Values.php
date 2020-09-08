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
   *     $v = $this->container->getValues("entity")->setDefault()->_fromArray($something);
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
  
  public $_logs;
  /**
   * Logs de verificaciones
   */




  abstract public function _reset();
    /**
     * El reseteo consiste en redefinir un valor al atributo en base a ciertas condiciones,
     * no implica que el valor este erroneo, sino que puede ser mejor formateado, 
     * El reseteo debería hacerse antes del chequeo de errores
     * en ocasiones el valor puede ser erroneo y al reformatearlo es valido.
     * El seteo realiza un formato inicial, el reseteo un formato adicional que requiere mas tiempo de ejecucion
     * por ejemplo el usuario ingresa el nombre en mayusculas
     * y conviene que este primero con mayusculas y despues con minusculas.
     * Antes de resetear verificar si no esta vacio o indefinido
     * No se realiza el reseteo directamente en el seteo porque demanda tiempo de ejecucion,
     * sobre todo si el valor se obtiene de la base de datos.
     * Si un valor se setea de la base de datos se supone que esta bien
     * por eso el reseteo se define como un metodo aparte que debe ser invocado si corresponde.
     * Un valor correctamente formateado que es reformateado debe conservar su valor original.
     * En ocasiones el reset puede marcar el valor como UNDEFINED para facilitar la persistencia,
     * sobre todo cuando se procesan grandes cantidades de datos de fuentes no confiables
     * en donde se incrementa la probabilidad de cometer errores.
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

  public function _toString(){
    $fields = [];
    foreach($this->_toArray() as $field){
      if(!Validation::is_empty($field)) array_push($fields, $field);
    }
    return implode(", ",$fields);
  }

}