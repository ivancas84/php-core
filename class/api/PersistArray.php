<?php



require_once("class/model/Ma.php");

require_once("class/model/Sqlo.php");
require_once("class/tools/Validation.php");

class PersistArrayApi {
  /**
   * Comportamiento general de persistencia
   */

  public $entityName;
  public $container;
  public $permission = "w";

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $data = Filter::jsonPostRequired();

    if(empty($data)) throw new Exception("Se está intentando persistir un conjunto de datos vacío");

    
    $ids = [];
    $sql = "";
    $detail = [];
    $p = $this->container->getPersist();

    foreach($data as $row){
      if($row["_delete"]){
        $sql .= $this->container->getSqlo($this->entityName)->delete($row["id"]);
      } else {
        $persist = $p->id($this->entityName, $row);
        $sql .= $persist["sql"];
        array_push($ids, $persist["id"]);
      }
      array_push($detail, $this->entityName.$row["id"]);
    }

    $this->container->getDb()->multi_query_transaction_log($sql);

    return ["ids" => $ids, "detail" => $detail];
  }
}



