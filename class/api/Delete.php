<?php

require_once("class/tools/Filter.php");
require_once("class/model/Ma.php");

require_once("class/model/Sqlo.php");
require_once("class/tools/Validation.php");

class DeleteApi {
  /**
   * Comportamiento general de eliminacion
   * UTILIZAR CON PRECAUCION
   */

  public $entityName;
  public $container;
  public $permission = "write";

  public function concat($id) {
    return($this->entityName . $id);
  }  

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $ids = Filter::jsonPostRequired();
    $sql = $this->container->getSqlo($this->entityName)->deleteAll($ids);
    $this->container->getDb()->multi_query_transaction_log($sql);
    $detail = array_map(array($this, 'concat'), $ids);    

    return ["ids" => $ids, "detail" => $detail];
  }
}



