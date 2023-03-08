<?php



require_once("function/php_input.php");

class PersistApi {
  /**
   * Persistencia de una entidad (sin considerar relaciones)
   * @todo falta identificar el controlador (solo trabaja con id actualmente)
   */

  public $entity_name;
  public $container;
  public $permission = "w";

  public function main(){
    $this->container->auth()->authorize($this->entity_name, $this->permission);
    
    $data = php_input();
    $persist = $this->container->controller("persist_sql", $this->entity_name)->main($data);
    $this->container->db()->multi_query_transaction($persist["sql"]);
    return ["id" => $persist["id"], "detail" => [$this->entity_name.$persist["id"]]];
  }
}



