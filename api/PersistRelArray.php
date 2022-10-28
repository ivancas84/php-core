<?php

require_once("api/PersistRel.php");

class PersistRelArrayApi extends PersistRelApi{
  /**
   * Persistencia de una entidad y sus relaciones
   * Recibe como parametro un array simple que es transformado en array multiple en el controlador
   */
  public $entityName; //entidad principal
  public $container;
  public $permission = "w";
  public $persistController = "id";
  public $persistRelController = "persist_rel_sql_array";

  public function main(){
    $this->container->auth()->authorize($this->entityName, $this->permission);

    $params = php_input();
    
    $persist = $this->container->controller("persist_rel_sql_array", $this->entityName);
    $p = $persist->main($params);
    $this->container->db()->multi_query_transaction($p["sql"]);
    return ["id" => $p["id"], "detail" => $p["detail"]];
  }
}



