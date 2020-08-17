<?php

class Logs {
  protected $logs = [];
  /**
   * Array asociativo de logs, cada elemento es tambien un array asociativo con los campos "status" y "data" y otros opcionales
   * [
   *   "asignatura" => [
   *     ["status" => "error", "data" => "No puede estar vacío"]
   *   ]
   *   "plan" => [
   *     ["status" => "warning", "data" => "No tiene cargas horarias asociadas"]
   *   ]
   *   "numero" => [
   *     ["status" => "error", "data" => "No es unico"]
   *     ["status" => "error", "data" => "Esta fuera del rango permitido"]
   *   ]
   * ]
   */
  
  public function resetLogs($key){
    /**
     * borrar todos los logs de una determinada llave
     */
    if(key_exists($key, $this->logs)) unset($this->logs[$key]);
  }

  public function clear(){
    $this->logs = [];
  }

  public function addLog($key, $status, $data){
    /**
     * agregar log a una determinada llave
     * los errores se agregan al inicio de los logs, el resto al final
     */
    if(!key_exists($key, $this->logs)) $this->logs[$key] = [];
    ($status == "error") ?
      array_unshift($this->logs[$key], ["status"=>$status, "data"=>$data]) :
      array_push($this->logs[$key], ["status"=>$status, "data"=>$data]);
  }

  public function isError(){
    /**
     * existen logs con estado error?
     */
    foreach($this->logs as $value){
      foreach($value as $v) if($v["status"]=="error") return true;
    }
    return false;
  }

  public function isErrorKey($key){
    /**
     * existen logs con estado error para una determinada llave?
     */
    if(!key_exists($key, $this->logs)) return false;
    foreach($this->logs[$key] as $v) if($v["status"]=="error") return true;
    return false;
  }
  
  public function getLogs(){
    return $this->logs;
  }

  public function toString(){
    return  implode(', ', array_map(function ($entry) {
     
    return implode(', ', $entry);
     
    }, $this->logs));
    
  }

}