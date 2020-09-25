<?php

require_once("class/Container.php");


class PersistLog {
  public $container;

  public $logs = [];
  /**
   * Cada elemento de logs es un array con la siguiente informacion 
   * sql
   * detail
   */

  public function getSql() {
    $sql = "";
    foreach($this->logs as $log) {
      if (!empty($log["sql"])) $sql .= $log["sql"];
    }
    return $sql;
  }

  public function getDetail() {
    $detail = [];
    foreach($this->logs as $log) {
      if (!empty($log["detail"])) $detail = array_merge($detail, $log["detail"]);
    }
    return $detail;
  }

  public function getLogsKeys($keys){
    $logs = [];
    foreach($this->logs as $log) {
      $l = [];
      foreach($keys as $key) $l[$key] = $log[$key];
      array_push($logs, $l);
    }
    return $logs;
  }

  public function insert($entity, $row) {
    $sql = $this->container->getSqlo($entity)->insert($row);
    array_push($this->logs, ["sql"=>$sql, "detail"=>[$entity.$row["id"]]]);
    return $row["id"];
  }

  public function update($entity, $row) {
    $sql = $this->container->getSqlo($entity)->update($row);
    array_push($this->logs, ["sql"=>$sql, "detail"=>[$entity.$row["id"]]]);
    return $row["id"];
  }

}