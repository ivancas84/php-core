<?php


class PersistSql { //2
  /**
   * Definir SQL de persistencia
   */

  public $container;
  public $entityName;

  public function main(&$row){
    $mode = (array_key_exists("_mode",$row) && !empty($row["_mode"])) ? $row["_mode"] : "id"; 
 
    switch($mode){
      case "delete":
        $sql = $this->container->getEntityPersist($this->entityName)->delete([$row["id"]]);
        return ["id" => $row["id"],"sql"=>$sql, "mode"=>"delete"];
      break;
      case "id":
        return $this->id($row);
      break;
      case "unique":
        return $p->unique($row);
      break;
    } 
  }

  public function id(&$row) {
    $value = $this->container->getValue($this->entityName)->_fromArray($row, "set");

    if(!Validation::is_empty($value->_get("id"))){
      $value->_call("reset")->_call("check");
      if($value->logs->isError()) throw new Exception($value->logs->toString());
      $sql = $this->container->getEntityPersist($this->entityName)->update($value->_toArray("sql"));
    } else {
      $value->_call("setDefault");
      $value->_set("id",uniqid()); //id habitualmente esta en null y no se asigna al definir valores por defecto
      $row["id"] = $value->_get("id");
      $value->_call("reset")->_call("check");
      if($value->logs->isError()) throw new Exception($value->logs->toString());
      $sql = $this->container->getEntityPersist($this->entityName)->insert($value->_toArray("sql"));
    }

    return["id" => $value->_get("id"),"sql"=>$sql, "mode"=>"id"];
  }

  public function unique(&$row) {
    $value = $this->container->getValue($this->entityName)->_fromArray($row, "set");

    $row = $this->container->getDb()->unique($this->entityName, $value->_toArray("json"));
    if (!empty($row)){ 
      $value->_set("id",$row["id"]);
      $value->_call("reset")->_call("check");
      if($value->logs->isError()) throw new Exception($value->logs->toString());
      $sql = $this->container->getEntityPersist($this->entityName)->update($value->_toArray("sql"));
    } else {
      $value->_call("setDefault");
      $value->_set("id",uniqid()); //id habitualmente esta en null y no se asigna al definir valores por defecto
      $row["id"] = $value->_get("id");
      $value->_call("reset")->_call("check");
      if($value->logs->isError()) throw new Exception($value->logs->toString());
      $sql = $this->container->getEntityPersist($this->entityName)->insert($value->_toArray("sql"));
    }
    return["id" => $value->_get("id"),"sql"=>$sql, "mode"=>"unique"];
  }
}