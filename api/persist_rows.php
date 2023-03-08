<?php

require_once("tools/Validation.php");
require_once("function/php_input.php");

class PersistRowsApi {
  /**
   * Persistencia de una entidad (sin considerar relaciones)
   * Recibe un conjunto de tuplas de una entidad
   */

  public $entity_name;
  public $container;
  public $permission = "w";

  public function main(){
    $this->container->auth()->authorize($this->entity_name, $this->permission);
    
    $data = php_input();
    if(empty($data)) throw new Exception("Se está intentando persistir un conjunto de datos vacío");
    
    $ids = [];
    $sql = "";
    $detail = [];

    foreach($data as $row){
        $persist = $this->container->controller("persist_sql", $this->entity_name)->main($row);
        $sql .= $persist["sql"];
        array_push($ids, $persist["id"]);
      array_push($detail, $this->entity_name.$row["id"]);
    }

    $this->container->db()->multi_query_transaction($sql);

    return ["ids" => $ids, "detail" => $detail];
  }
}



