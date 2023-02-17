<?php

require_once("controller/Base.php");

class PersistRelSqlArray extends BaseController {
   
  /**
   * Persistencia de un array de datos 
   * Se recibe un unico array de datos (no multiple), con elemento de la entidad principal y relaciones, ejemplo
   *   ["nombres"=>"Val1", "inscripcion-cantidad"=>"Val2", "asignatura-modo"=>"Val3"]
   * Se acomodan los elementos del parametro recibido y se transforma en un array multiple
   *   [
   *     "persona"=>["nombres"=>"Val1"],
   *     "inscripcion"=>["cantidad"=>"Val2"],
   *     "asignatura"=>["modo"=>"Val3"],
   *   ]
   * Una vez realizada la transformacion se invoca a persist_rel_sql
   * 
   * (No se permiten relaciones UM)
   */

  public function main($data){
    $dataAux = [];
    foreach($data as $key => $value){
      $pos = strpos($key, "-");
      if($pos === false) {
        if(!array_key_exists($this->entityName, $dataAux)) $dataAux[$this->entityName] = [];
        $dataAux[$this->entityName][$key] = $value;
      } else {
        $k = substr($key, 0, $pos);
        if(!array_key_exists($k, $dataAux)) $dataAux[$k] = [];
        $dataAux[$k][substr($key, $pos+1)] = $value;
      }
    }

    return $this->container->controller("persist_rel_sql",$this->entityName)->main($dataAux);
  }

}



