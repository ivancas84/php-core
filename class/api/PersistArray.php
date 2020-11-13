<?php



require_once("class/model/Ma.php");

require_once("class/model/Sqlo.php");
require_once("class/tools/Validation.php");
require_once("function/php_input.php");

class PersistArrayApi {
  /**
   * Comportamiento general de persistencia
   */

  public $entityName;
  public $container;
  public $permission = "w";

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $data = php_input();
    $render = $this->container->getControllerEntity("render_build", $this->entityName)->main();
    if(empty($data)) throw new Exception("Se está intentando persistir un conjunto de datos vacío");

    
    $ids = [];
    $sql = "";
    $detail = [];
    $p = $this->container->getPersist();

    foreach($data as $row){
      if($row["_delete"]){
        $sql .= $this->container->getSqlo($render->entityName)->delete($row["id"]);
      } else {
        $persist = $p->id($render->entityName, $row);
        $sql .= $persist["sql"];
        array_push($ids, $persist["id"]);
      }
      array_push($detail, $render->entityName.$row["id"]);
    }

    $this->container->getDb()->multi_query_transaction($sql);

    return ["ids" => $ids, "detail" => $detail];
  }
}



