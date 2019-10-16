<?php

class Logs {
  protected $logs = [];
  
  public function resetLogs($key){
    if(key_exists($key, $this->logs[$key])) unset($this->logs[$key]);
  }

  public function addLog($key, $status, $data){
    /**
     * los errores se agregan al inicio de los logs, el resto al final.
     */
    if(!key_exists($key, $this->logs[$key])) $this->logs[$key] = [];
    ($status == "error") ? 
      array_unshift($this->logs[$key], ["status"=>$status, "data"=>$data]) :
      array_pop($this->logs[$key], ["status"=>$status, "data"=>$data]);
  }

  public function isError(){
    foreach($this->logs as $value){
      foreach($value as $v) if($v["status"]=="error") return true;
    }
    return false;
  }
  
  public function getLogs(){
    return $this->logs;
  }
  

  /*public function getLogsStatus($status){
    $logs = [];
    foreach($this->logs as $key => $value){
      foreach($value as $v) {
        if($v["status"]==$status) {
          if(!key_exists($key, $logs)) $logs[$key] = [];
          array_push($logs[$key], $v);
        }
      } 
    }
    return $logs;
  }*/

}