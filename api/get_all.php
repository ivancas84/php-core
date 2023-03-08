<?php
require_once("function/php_input.php");


class GetAllApi {
  /**
   * Comportamiento general de all
   */

  public $entity_name;
  public $container;
  public $permission = "r";
  
  public function main() {
    $this->container->auth()->authorize($this->entity_name, $this->permission);
    
    $ids = php_input();
    if(empty($ids)) throw new Exception("Identificadores no definidos");
    $rows = $this->container->query($this->entity_name)->param("id",$ids)->size(0)->fieldsTree()->all();
    $rel = $this->container->tools($this->entity_name);
    foreach($rows as &$row) $row = $rel->json($row);
    return $rows;
    
  }

}
