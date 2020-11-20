<?php


class PersistSql {
  /**
   * Definir SQL de persistencia
   */

  public $container;

  public function id($entityName, $row) {
    $value = $this->container->getValue($entityName)->_fromArray($row, "set");

    if(!Validation::is_empty($value->_get("id"))){
      $value->_call("reset")->_call("check");
      if($value->logs->isError()) throw new Exception($value->logs->toString());
      $sql = $this->container->getSqlo($entityName)->update($value->_toArray("sql"));
    } else {
      $value->_call("setDefault");
      $value->_set("id",uniqid()); //id habitualmente esta en null y no se asigna al definir valores por defecto
      $value->_call("reset")->_call("check");
      if($value->logs->isError()) throw new Exception($value->logs->toString());
      $sql = $this->container->getSqlo($entityName)->insert($value->_toArray("sql"));
    }

    return["id" => $value->_get("id"),"sql"=>$sql];
  }

  public function unique($entityName, $row) {
    $value = $this->container->getValue($entityName)->_fromArray($row, "set");

    $row = $this->container->getDb()->unique($entityName, $value->_toArray());
    if (!empty($row)){ 
      $value->_set("id",$row["id"]);
      $value->_call("reset")->_call("check");
      if($value->logs->isError()) throw new Exception($value->logs->toString());
      $sql = $this->container->getSqlo($entityName)->update($value->_toArray("sql"));
    } else {
      $value->_call("setDefault");
      $value->_set("id",uniqid()); //id habitualmente esta en null y no se asigna al definir valores por defecto
      $value->_call("reset")->_call("check");
      if($value->logs->isError()) throw new Exception($value->logs->toString());
      $sql = $this->container->getSqlo($entityName)->insert($value->_toArray("sql"));
    }
    return["id" => $value->_get("id"),"sql"=>$sql];
  }
}