<?php



require_once("function/php_input.php");

class PersistApi {
  /**
   * Persistencia de una entidad (sin considerar relaciones)
   * @todo falta identificar el controlador (solo trabaja con id actualmente)
   */

  public $entityName;
  public $container;
  public $permission = "w";

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $data = php_input();
    $render = $this->container->query($this->entityName);
    
    $persist = $this->container->getControllerEntity("persist_sql", $render->entityName)->main($data);
    $this->container->getDb()->multi_query_transaction($persist["sql"]);
    return ["id" => $persist["id"], "detail" => [$this->entityName.$persist["id"]]];
  }
}



