<?php


class PersistSqlValue { //2
  /**
   * Definir SQL de persistencia a partir de una instancia de EntityValues
   */

  public $container;
  public $entityName;

  public function id(&$value) {
    if(!Validation::is_empty($value->_get("id"))){
      $value->_call("reset")->_call("check");
      if($value->logs->isError()) throw new Exception($value->logs->toString());
      $sql = $this->container->getEntitySqlo($this->entityName)->update($value->_toArray("sql"));
    } else {
      $value->_call("setDefault");
      $value->_set("id",uniqid()); //id habitualmente esta en null y no se asigna al definir valores por defecto
      $value->_call("reset")->_call("check");
      if($value->logs->isError()) throw new Exception($value->logs->toString());
      $sql = $this->container->getEntitySqlo($this->entityName)->insert($value->_toArray("sql"));
    }

    return["id" => $value->_get("id"),"sql"=>$sql];
  }

  public function unique(&$value) {
    $row = $this->container->getDb()->unique($this->entityName, $value->_toArray("json"));
    if (!empty($row)){ 
      $value->_set("id",$row["id"]);
      $value->_call("reset")->_call("check");
      if($value->logs->isError()) throw new Exception($value->logs->toString());
      $sql = $this->container->getEntitySqlo($this->entityName)->update($value->_toArray("sql"));
    } else {
      $value->_call("setDefault");
      $value->_set("id",uniqid()); //id habitualmente esta en null y no se asigna al definir valores por defecto
      $value->_call("reset")->_call("check");
      if($value->logs->isError()) throw new Exception($value->logs->toString());
      $sql = $this->container->getEntitySqlo($this->entityName)->insert($value->_toArray("sql"));
    }
    return["id" => $value->_get("id"),"sql"=>$sql];
  }
}