<?php

require_once("class/model/entityOptions/EntityOptions.php");

class MappingEntityOptions extends EntityOptions {

  public function id() { return $this->prt() . ".id"; }

  public function count(){ return "COUNT(*)"; }
  
  public function identifier(){ 
    if(empty($this->entity->getIdentifier())) throw new Exception ("Identificador no definido en la entidad ". $this->entity->getName()); 
    $identifier = [];
    foreach($this->entity->getIdentifier() as $id) array_push($identifier, $this->id());
    return "CONCAT_WS(\"". UNDEFINED . "\"," . implode(",", $identifier) . ")
";
  }

}