<?php
require_once("function/php_input.php");


class GetApi {
  /**
   * Comportamiento general de all
   */

  public $entity_name;
  public $container;
  public $permission = "r";
  
  public function main() {
    $this->container->auth()->authorize($this->entity_name, $this->permission);
    
    $id = file_get_contents("php://input");
    if(empty($id)) throw new Exception("Identificador no definido");
    $row = $this->container->query($this->entity_name)->fieldsTree()->param("id",$id)->one();
    return $this->container->tools($this->entity_name)->json($row);
  }

}
