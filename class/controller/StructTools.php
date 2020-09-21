<?php

class StructTools {

  public static function getFieldsBySubtype($entity, $subtype){
    $fields = [];
    foreach($entity->getFields() as $field){
      if($field->getSubtype() == $subtype) array_push($fields, $field);
    }
    return $fields;
  }

  public static function getFieldsUniqueNoPk($entity){
    $unique = array();
    foreach($entity->getFieldsNoPk() as $field){
      if($field->isUnique()) array_push($unique, $field);
    }
    return $unique;
  }

  public static function getFieldsByName($entity, array $fieldNames){
    $fields = [];
    foreach($entity->getFields() as $field){
      if($field->getName() == $fieldName)
       array_push($fields, $field);
    }
    return $fields;
  }

  public static function getFieldNamesExclusive($entity){ //pk, nf, fk
    $names = [];
    foreach($entity->getFields() as $field) {
      if($field->isExclusive()) array_push($names, $field->getName());
    }
    return $names;
  }



  public static function getEntityRefBySubtypeSelect($entity){
    $entities = [];  
    foreach(self::getFieldsBySubtype($entity, "select") as $field){
        $entityName = $field->getEntityRef()->getName('XxYy');
        if(!key_exists($entityName, $entities)) array_push($entities, $field->getEntityRef());
      }
    return $entities;
  }

  public static function getEntityRefBySubtypeSelectUniqueMultiple($entity){
    $entities = [];  
    $fieldsSubtypeSelect = self::getFieldsBySubtype($entity, "select");
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