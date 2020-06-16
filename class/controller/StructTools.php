<?php

class StructTools {
  public static function getEntityRefBySubtypeSelect($entity){
    $entities = [];  
    foreach($entity->getFieldsBySubtype("select") as $field){
        $entityName = $field->getEntityRef()->getName('XxYy');
        if(!key_exists($entityName, $entities)) array_push($entities, $field->getEntityRef());
      }
    return $entities;
  }

  public static function getEntityRefBySubtypeSelectUniqueMultiple($entity){
    $entities = [];  
    $fieldsSubtypeSelect = $entity->getFieldsBySubtype("select");
    $fieldsUniqueMultiple = $entity->getFieldsUniqueMultiple();
    
    foreach($fieldsUniqueMultiple as $fieldUM){
      foreach($fieldsSubtypeSelect as $fieldSS) {
        if($fieldUM->getName() != $fieldSS->getName()) continue;
        $entityName = $fieldUM->getEntityRef()->getName('XxYy');
        if(!key_exists($entityName, $entities)) array_push($entities, $fieldUM->getEntityRef());
      }
    }
    
    return $entities;
  }

}