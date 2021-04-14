<?php

require_once("class/model/Rel.php");
require_once("function/php_input.php");
require_once("function/get_entity_rel.php");

class PersistRelApi { //2
  /**
   * Comportamiento general de persistencia de elementos relacionados
   * 
   * Comportamiento por defecto
   * 1) Si existe el id para una determinada entidad, se considera actualizacion, sino insercion.
   * 2) Considera que la existencia de valores unicos debe hacerse en el cliente.
   */

  public $entityName; //entidad principal
  public $container;
  public $permission = "w";
  public $persistController = "id";
  public $persistRelController = "persist_rel_sql";

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);

    $params = php_input();
    
    $persist = $this->container->getControllerEntity($this->persistRelController, $this->entityName);
    $persist->persistController = $this->persistController;
    $p = $persist->main($params);
    print_r($p);
    $this->container->getDb()->multi_query_transaction($p["sql"]);
    return ["id" => $p["id"], "detail" => $p["detail"]];
  }
}



