<?php

/**
 * Consulta de campo unico
 */
class UniqueApi {
  

  public $entityName;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->auth()->authorize($this->entityName, $this->permission);
    
    $params = php_input();
    $row = $this->container->query($this->entityName)->unique($params)->fieldsTree()->one();
    return $this->container->tools($this->entityName)->json($row);
  }

}
