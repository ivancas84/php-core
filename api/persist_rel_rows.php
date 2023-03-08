<?php



require_once("tools/Validation.php");
require_once("function/php_input.php");

class PersistRelRowsApi {
  /**
   * Persistencia de una entidad y sus relaciones
   * Recibe un conjunto de tuplas de una entidad, cada tupla tiene datos de la entidad y sus relaciones fk
   * Cuidado con la eleccion del controlador (mode), por defecto se utiliza _mode = "id", debe existir el id de la relacion fk, sino se considerara insercion
   */

  public $entity_name;
  public $container;
  public $permission = "w";

  public function main(){
    $this->container->auth()->authorize($this->entity_name, $this->permission);
    
    $data = php_input();
    $render = $this->container->query($this->entity_name);
    if(empty($data)) throw new Exception("Se está intentando persistir un conjunto de datos vacío");
    
    $ids = [];
    $sql = "";
    $detail = [];

    foreach($data as $row){
      $persist = $this->container->controller("persist_rel_sql_array", $this->entity_name);
      $p = $persist->main($row);
      $sql .= $p["sql"];
      array_push($ids, $p["id"]);
      array_push($detail, $render->entity_name.$p["id"]);
    }

    $this->container->db()->multi_query_transaction($sql);

    return ["ids" => $ids, "detail" => $detail];
  }
}



