<?php

require_once("class/model/Rel.php");
require_once("function/php_input.php");
require_once("function/get_entity_rel.php");
require_once("class/api/PersistRel.php");

class PersistRelArrayApi extends PersistRelApi{
  
  /**
   * Comportamiento general de persistencia de elementos relacionados
   * 
   * Comportamiento por defecto
   * 1) Si existe el id para una determinada entidad, se considera actualizacion, sino insercion.
   * 2) Considera que la existencia de valores unicos debe hacerse en el cliente.
   */


  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    if(empty($this->params)) $this->params = php_input();
    $this->assignParams();
    return parent::main();
  }
  

  public function assignParams(){
    $data = [];
    foreach($this->params as $key => $value){
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
    $this->params = $data;
  }
}



