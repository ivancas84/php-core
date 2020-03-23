<?php

class StructTools {
  public static function getEntityRefBySubtypeSelectNoAdmin($entity){
    $entities = [];  
    foreach($entity->getFieldsBySubtype("select") as $field){
        if(!$field->isAdmin()) continue;
        $entityName = $field->getEntityRef()->getName('XxYy');
        if(!key_exists($entityName, $entities)) array_push($entities, $field->getEntityRef());
      }
    return $entities;
  }
}