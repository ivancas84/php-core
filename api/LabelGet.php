<?php
require_once("function/php_input.php");


class LabelGetApi {
  public $entityName;
  public $container;
  public $permission = "r";
  
  public function main() {
    $this->container->auth()->authorize($this->entityName, $this->permission);
    
    $id = file_get_contents("php://input");
    if(empty($id)) throw new Exception("Identificador no definido");

    /**
     * Consultar por id y retornar el id puede resultar redundante, pero es necesario para facilitar 
     * la implementacion de ciertos elementos como por ejemplo el autocomplete
     */
    return $this->container->query($this->entityName)->fields(["id","label"])->param("id", $id)->one();
  }

}
