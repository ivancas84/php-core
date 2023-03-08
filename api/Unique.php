<?php

/**
 * Consulta de campo unico
 */
class UniqueApi {
  

  public $entity_name;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->auth()->authorize($this->entity_name, $this->permission);
    
    $params = php_input();
    $row = $this->container->query($this->entity_name)->unique($params)->fieldsTree()->one();
    return $this->container->tools($this->entity_name)->json($row);
  }

}
