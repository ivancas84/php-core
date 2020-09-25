<?php
require_once("class/model/Render.php");

class PersistSql {
  /**
   * Controlador asociado a entidad
   * Definir sql de persistencia
   * 
   */

  public $entityName;
  public $container;

  public function main($row) {
    $value = $this->container->getValue($this->entityName)->_fromArray($row, "set");

    $value->_call("reset")->_call("_check");
    if($value->_getLogs()->isError()) throw new Exception($value->_getLogs()->toString());
      
    //$row_ = $this->container->getDb()->unique($this->entityName, $data);
    //if (!empty($row_)){ $value->setId($row_["id"]);
    if(!Validation::is_empty($value->id())){
      $sql = $this->container->getSqlo($this->entityName)->update($value->_toArray("sql"));
    } else {
      $value->_call("setDefault");
      $value->setId(uniqid());
      $sql = $this->container->getSqlo($this->entityName)->insert($value->_toArray("sql"));
    }

    return["id" => $value->id(),"sql"=>$sql];
  }

}
