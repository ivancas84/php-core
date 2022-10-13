<?php

require_once("class/model/Rel.php");
require_once("function/php_input.php");

class PersistRelApi {
  /**
   * Persistencia de una entidad y sus relaciones
   * Recibe como parametro un array multiple
   */

  public $entityName; //entidad principal
  public $container;
  public $permission = "w";

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);

    $params = php_input();
    
    $persist = $this->container->getControllerEntity("persist_rel_sql", $this->entityName);
    $p = $persist->main($params);
    $this->container->getDb()->multi_query_transaction($p["sql"]);
    return ["id" => $p["id"], "detail" => $p["detail"]];
  }
}



