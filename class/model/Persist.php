<?php


class Persist {
  /**
   * Definir SQL de persistencia
   */

  public $container;

  public function value($entityName, $row){
    $value = $this->container->getValue($entityName)->_fromArray($row, "set");

    $value->_call("reset")->_call("_check");
    if($value->_getLogs()->isError()) throw new Exception($value->_getLogs()->toString());

    return $value;
  }

  public function id($entityName, $row) {
    $value = $this->value($entityName, $row);
      
    if(!Validation::is_empty($value->id())){
      $sql = $this->container->getSqlo($entityName)->update($value->_toArray("sql"));
    } else {
      $value->_call("setDefault");
      $sql = $this->container->getSqlo($entityName)->insert($value->_toArray("sql"));
    }

    return["id" => $value->id(),"sql"=>$sql];
  }

  public function unique($entityName, $row) {
    $value = $this->value($entityName, $row);

    $row = $this->container->getDb()->unique($entityName, $value->_toArray("sql"));
    if (!empty($row)){ 
      $value->setId($row_["id"]);
      $sql = $this->container->getSqlo($entityName)->update($value->_toArray("sql"));
    } else {
      $value->_call("setDefault");
      $sql = $this->container->getSqlo($entityName)->insert($value->_toArray("sql"));
    }

    return["id" => $value->id(),"sql"=>$sql];
  }
}