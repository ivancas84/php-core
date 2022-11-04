<?php
require_once("function/php_input.php");


class LabelGetApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;
  public $container;
  public $permission = "r";
  
  public function main() {
    $this->container->auth()->authorize($this->entityName, $this->permission);
    
    $id = file_get_contents("php://input");
    if(empty($id)) throw new Exception("Identificador no definido");
    return $this->container->query($this->entityName)->fields(["id","label"])->param("id", $id)->one();
  }

}
