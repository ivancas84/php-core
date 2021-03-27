<?php



require_once("function/php_input.php");

class PersistUniqueApi {
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
    
    $persist = $this->container->getControllerEntity("persist_sql", $render->entityName)->unique($data);
    $this->container->getDb()->multi_query_transaction($persist["sql"]);
    return ["id" => $persist["id"], "detail" => [$this->entityName.$persist["id"]]];
  }
}



