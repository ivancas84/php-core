<?php

class TestApi {
  /**
   * Controlador de prueba
   **/

  public $entityName;
  public $container;
  public $permission = "read";

  public function main(){ 
    $this->container->getAuth()->authorize($entityName, $permission);

    return ["entity_name"=>$this->entityName]; 
  }

}
