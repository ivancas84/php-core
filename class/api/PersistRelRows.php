<?php



require_once("class/model/Ma.php");

require_once("class/model/Sqlo.php");
require_once("class/tools/Validation.php");
require_once("function/php_input.php");

class PersistRowsApi {
  /**
   * Persistencia de una entidad y sus relaciones
   * Recibe un conjunto de tuplas de una entidad, cada tupla tiene datos de la entidad y sus relaciones fk
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

    foreach($data as $row){
      $persist = $this->container->getControllerEntity("persist_rel_sql_array", $this->entityName);
      $p = $persist->main($row);
      $sql .= $p["sql"];
      array_push($ids, $p["id"]);
      array_push($detail, $render->entityName.$p["id"]);
    }

    $this->container->getDb()->multi_query_transaction($sql);

    return ["ids" => $ids, "detail" => $detail];
  }
}



