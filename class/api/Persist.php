<?php



require_once("class/model/Ma.php");

require_once("class/model/Sqlo.php");
require_once("class/tools/Validation.php");

class PersistApi {
  /**
   * Comportamiento general de persistencia
   */

  public $entityName;
  public $container;
  public $permission = "w";

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $data = file_get_contents("php://input");
    if(!$data) throw new Exception("Error al obtener datos de input");
    $data = json_decode($data, true);


    if(empty($data)) throw new Exception("Se está intentando persistir un conjunto de datos vacío");
    
    $p = $this->container->getPersist();
    $persist = $p->id($this->entityName, $data);
    $this->container->getDb()->multi_query_transaction_log($persist["sql"]);
    return ["id" => $persist["id"], "detail" => [$this->entityName.$persist["id"]]];
  }
}



