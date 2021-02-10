<?php

require_once("class/tools/EntityRecursive.php");

abstract class EntityRecursiveFk extends EntityRecursive { //Comportamiento comun recursivo
  
  protected function recursive(Entity $entity, array $tablesVisited = [], $prefix = ""){
    if(in_array($entity->getName(), $tablesVisited)) return;
    if (!empty($prefix)){
      $this->string .= $this->body($entity, $prefix); //Genera codigo solo para las relaciones
    }
    $this->fk($entity, $tablesVisited, $prefix);
  }

}
