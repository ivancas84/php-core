<?php

require_once("class/controller/PersistRelSql.php");

class PersistRelSqlArray extends PersistRelSql{
  
  /**
   * Persistencia de un array de datos 
   * Se recibe un unico array de datos (no multiple), con elemento de la entidad principal y relaciones, ejemplo
   *   ["nombres"=>"Val1", "per-cantidad"=>"Val2", "asi_per-modo"=>"Val3"]
   * Se acomodan los elementos del parametro recibido y se transforma en un array multiple
   *   [
   *     "entityName"=>["nombres"=>"Val1"],
   *     "per"=>["cantidad"=>"Val2"],
   *     "asi-per"=>["modo"=>"Val3"],
   *   ]
   * Una vez realizada la transformacion se invoca a persist_rel_sql
   * 
   * (No se permiten relaciones UM)
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



