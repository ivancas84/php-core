<?php


class EntityOptions {

  /**
   * Todos los metodos en general se ejecutan comparando el valor UNDEFINED
   */
  public $prefix = "";
  
  public function _pf(){ return (empty($this->prefix)) ?  ''  : $this->prefix . '_'; } 
  /**
   * prefijo fields
   */
  
  public function _pt(){ return (empty($this->prefix)) ?  $this->container->entity($this->entityName)->getAlias() : $this->prefix; }
  /**
   * prefijo tabla
   */

  function _callFields(array $fieldNames, $method = ""){
    /**
     * Ejecutar metodo en los fields indicados
     */
		foreach($fieldNames as $fieldName) call_user_func_array([$this, "_".$method],[$fieldName]);
    return $this;
  }

  function _call($method = ""){
    /**
     * Llamar a _callFields utilizando los fieldNames definidos en la entidad.
     */
    return $this->_callFields($this->container->entity($this->entityName)->getFieldNames(), $method);
  }

  function _toArrayFields($fieldNames, $method = ""){
    /**
     * Ejecutar metodo y almacenar resultado en un array de fields
     * 
     * Por cuestiones operativas, no se utiliza el prefijo
     */
    $row = [];
    foreach($fieldNames as $fieldName){
      $r = call_user_func_array([$this, "_".$method],[$fieldName]);
      if($r !== UNDEFINED) $row[$fieldName] = $r ;
    }

    return $row;
  }

  function _toArray($method = ""){
    /**
     * Ejecutar _toArrayFields para los campos definidos en la configuracion de la entidad principal
     */
    return $this->_toArrayFields($this->container->entity($this->entityName)->getFieldNames(), $method);
  }

  function _fromArrayFields(array $row, $fieldNames, $method = ""){
    /**
     * Ejecutar metodo y almacenar resultado en atributos del objeto
     * 
     * Utiliza prefijo si esta definido
     */
    if(empty($row)) return $this;

    foreach($fieldNames as $fieldName){      
      if(array_key_exists($this->_pf().$fieldName, $row)) call_user_func_array([$this, "_".$method],[$fieldName, $row[$this->_pf().$fieldName]]);
    }

    return $this;
  }

  function _fromArray(array $row, $method = ""){
    /**
     * Ejecutar _fromArrayFields para los atributos definidos en la configuracion de la entidad principal
     */
    return $this->_fromArrayFields($row, $this->container->entity($this->entityName)->getFieldNames(), $method);
  }
  
}