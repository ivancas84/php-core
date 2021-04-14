<?php

require_once("class/controller/PersistRelSql.php");

class PersistRelSqlArray extends PersistRelSql{
  
  /**
   * Comportamiento general de persistencia de elementos relacionados
   * 
   * Comportamiento por defecto
   * 1) Si existe el id para una determinada entidad, se considera actualizacion, sino insercion.
   * 2) Considera que la existencia de valores unicos debe hacerse en el cliente.
   */


  public function main($params){
    $params = $this->assignParams($params);
    return parent::main($params);
  }
  

  public function assignParams($params){
    $data = [];
    foreach($params as $key => $value){
      $pos = strpos($key, "-");
      if($pos === false) {
        if(!array_key_exists($this->entityName, $data)) $data[$this->entityName] = [];
        $data[$this->entityName][$key] = $value;
      } else {
        $k = substr($key, 0, $pos);
        if(!array_key_exists($k, $data)) $data[$k] = [];
        $data[$k][substr($key, $pos+1)] = $value;
      }
    }
    return $data;
  }
}



