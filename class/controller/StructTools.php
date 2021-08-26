<?php

class StructTools {

  public $container; 

  public function getFieldsBySubtype($entity, $subtype){
    $fields = [];
    foreach($entity->getFields() as $field){
      if($field->getSubtype() == $subtype) array_push($fields, $field);
    }
    return $fields;
  }

  public function getFieldsMainNoPk($entity){
    $fields = array();
    foreach($entity->getFieldsNoPk() as $field){
      if($field->isMain()) array_push($fields, $field);
    }
    return $fields;
  }

  public function getFieldsByName($entity, array $fieldNames){
    $fields = [];
    foreach($entity->getFields() as $field){
      if($field->getName() == $fieldName)
       array_push($fields, $field);
    }
    return $fields;
  }

  public function getFieldNamesExclusive($entity){ //pk, nf, fk
    $names = [];
    foreach($entity->getFields() as $field) {
      if($field->isExclusive()) array_push($names, $field->getName());
    }
    return $names;
  }

  public function getFieldNamesExclusiveAdmin($entity){ //pk, nf, fk
    $names = [];
    foreach($entity->getFields() as $field) {
      if($field->isExclusive() && $field->isAdmin()) array_push($names, $field->getName());
    }
    return $names;
  }



  public function getEntityRefBySubtypeSelect($entity){
    $entities = [];  
    foreach($this->getFieldsBySubtype($entity, "select") as $field){
        $entityName = $field->getEntityRef()->getName('XxYy');
        if(!key_exists($entityName, $entities)) array_push($entities, $field->getEntityRef());
      }
    return $entities;
  }

  public function getEntityRefBySubtypeSelectUniqueMultiple($entity){
    $entities = [];  
    $fieldsSubtypeSelect = $this->getFieldsBySubtype($entity, "select");
    $fieldsUniqueMultiple = $entity->uniqueMultiple;
    
    foreach($fieldsUniqueMultiple as $fieldNameUM){
      foreach($fieldsSubtypeSelect as $fieldSS) {
        if($fieldNameUM != $fieldSS->getName()) continue;
        $fieldUM = $this->container->getField($entity, $fieldNameUM);
        $entityName = $fieldUM->getEntityRef()->getName('XxYy');
        if(!key_exists($entityName, $entities)) array_push($entities, $fieldUM->getEntityRef());
      }
    }
    
    return $entities;
  }

}