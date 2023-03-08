<?php

class TestApi {
  /**
   * Controlador de prueba
   **/

  public $entity_name;
  public $container;
  public $permission = "r";

  public function main(){ 
    //$this->container->getAuth()->authorize($this->entity_name, $this->permission);

    return ["entity_name"=>$this->entity_name]; 
  }

}
