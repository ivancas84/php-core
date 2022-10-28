<?php

class TestApi {
  /**
   * Controlador de prueba
   **/

  public $entityName;
  public $container;
  public $permission = "r";

  public function main(){ 
    //$this->container->getAuth()->authorize($this->entityName, $this->permission);

    return ["entity_name"=>$this->entityName]; 
  }

}
