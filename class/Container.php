<?php

require_once("class/model/Ma.php");
require_once("function/snake_case_to.php");
require_once("class/tools/Logs.php");

class Container {
  static $db = null;
  static $sqlo = [];
  static $entity = [];
  static $field = [];
  static $structure = false; //flag para indicar que se generaron todas las entidades

  public function getDb() {
    if (isset(self::$db)) return self::$db;
    $c = new Ma();
    $c->container = $this;
    return self::$db = $c;
  }

  public function getStructure(){
    if(self::$structure) return self::$entity;
    require_once("function/get_entity_names.php");
    foreach(get_entity_names() as $entityName) $this->getEntity($entityName);
    self::$structure = true;
    return self::$entity;
  }

  public function getEntity($entityName){
    if (isset(self::$entity[$entityName])) return self::$entity[$entityName];

    $dir = "class/model/entity/";
    $name = snake_case_to("XxYy", $entityName) . ".php";
    $prefix = "";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      $prefix = "_";
      require_once($dir.$prefix.$name);
    }
    
    $className = $prefix.snake_case_to("XxYy", $entityName) . "Entity";
    $c = new $className;
    $c->container = $this;
    self::$entity[$entityName] = $c; //se asigna previamente como clase estatica antes de llamar a la estructura, la estructura poseera tambien la entidad
    self::$entity[$entityName]->structure = $this->getStructure();
    return self::$entity[$entityName];
  }


  public function getField($entity, $field){
    $dir = "class/model/field/" . snake_case_to("xxYy", $entity) . "/";
    $name = snake_case_to("XxYy", $field) . ".php";
    $prefix = "";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      $prefix = "_";
      require_once($dir.$prefix.$name);
    }
    
    $className = $prefix."Field".snake_case_to("XxYy", $entity) . snake_case_to("XxYy", $field);
    if (isset(self::$field[$className])) return self::$field[$className]; 
    $c = new $className;
    $c->container = $this;
    return self::$field[$className] = $c; 
  }

  public function getApi($controller, $entityName){
    $path = "class/api/" . snake_case_to("xxYy", $controller) . "/" . snake_case_to("XxYy", $entityName) . ".php";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$path)){
      require_once($path);
      $className = snake_case_to("XxYy", $entityName) . snake_case_to("XxYy", $controller). "Api";    
    } 
    else{
      require_once("class/api/" . snake_case_to("XxYy", $controller) . ".php");
      $className = snake_case_to("XxYy", $controller)   . "Api";    
    }

    $c = new $className;
    $c->container = $this;
    $c->entityName = $entityName;
    return $c;
  }

  public function getSqlTools(){
    require_once("class/model/SqlTools.php");
    $c = new SqlTools;
    $c->container = $this;
    return $c;
  }

  public function getController($controller){
    /**
     * Controlador
     */
    $dir = "class/controller/";
    $name = snake_case_to("XxYy", $controller) . ".php";
    $className = snake_case_to("XxYy", $controller);    
    require_once($dir.$name);
    $c = new $className;
    $c->container = $this;
    return $c;
  }
  
  public function getControllerEntity($controller, $entityName){
    /**
     * Controlador asociado a entidad
     */
    $path = "class/controller/" . snake_case_to("xxYy", $controller) . "/" . snake_case_to("XxYy", $entityName) . ".php";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$path)){
      require_once($path);
      $className = snake_case_to("XxYy", $entityName) . snake_case_to("XxYy", $controller);    
    } else{
      require_once("class/controller/" . snake_case_to("XxYy", $controller) . ".php");
      $className = snake_case_to("XxYy", $controller);    
    }

    $c = new $className;
    $c->container = $this;
    $c->entityName = $entityName;
    return $c;
  }

  public function getImport($entityName){
    $path = "class/import/" . snake_case_to("xxYy", $entityName) . "/Import.php";
    $className = snake_case_to("XxYy", $entityName)."Import";    
    require_once($path);
    $c = new $className;
    $c->entityName = $entityName;
    $c->container = $this;
    return $c;
  }

  
  public function getImportElement($entityName){
    $path = "class/import/" . snake_case_to("xxYy", $entityName) . "/Element.php";
    $className = snake_case_to("XxYy", $entityName)."ImportElement";    
    require_once($path);
    $c = new $className;
    $c->entityName = $entityName;
    $c->logs = new Logs();
    $c->container = $this;
    return $c;
  }

  public function getSqlo($entity) {
    $dir = "class/model/sqlo/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $prefix = "";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      $prefix = "_";
      require_once($dir.$prefix.$name);
    }
    
    $className = $prefix.snake_case_to("XxYy", $entity) . "Sqlo";

    if (isset(self::$sqlo[$className])) return self::$sqlo[$className];
    $c = new $className;
    $c->entity = $this->getEntity($entity);
    $c->sql = $this->getSql($entity);
    $c->container = $this;
    $c->mapping = $this->getMapping($entity);
    return self::$sqlo[$className] = $c;
  }

  public function getSql($entity, $prefix = null){
    $dir = "class/model/sql/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $prf = "";
    
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) {
      require_once($dir.$name);
      $className = snake_case_to("XxYy", $entity) . "Sql";
    
    } elseif (file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir."_".$name)) {
      require_once($dir."_".$name);
      $className = "_".snake_case_to("XxYy", $entity) . "Sql";
    
    } else {
      require_once("class/model/Sql.php");
      $className = "EntitySql";
    }
    
    $sql = new $className;
    if($prefix) $sql->prefix = $prefix;
    $sql->container = $this;
    $sql->entity = $this->getEntity($entity);
    return $sql;    
  }

  public function getMapping($entity, $prefix = ""){
    $dir = "class/model/mapping/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $prf = "";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      $prf = "_";
      require_once($dir.$prf.$name);
    }
    
    $className = $prf.snake_case_to("XxYy", $entity) . "Mapping";
    $c = new $className;
    if($prefix) $c->prefix = $prefix;
    $c->container = $this;
    $c->entity = $this->getEntity($entity);
    return $c;    
  }
  
  public function getCondition($entityName, $prefix = ""){
    $dir = "class/model/condition/";
    $name = snake_case_to("XxYy", $entityName) . ".php";
    $prf = "";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      $prf = "_";
      require_once($dir.$prf.$name);
    }

    $className = $prf.snake_case_to("XxYy", $entityName) . "Condition";
    $c = new $className;
    if($prefix) $c->prefix = $prefix;
    $c->container = $this;
    $c->mapping = $this->getMapping($entityName, $prefix);
    $c->value = $this->getValue($entityName, $prefix);
    $c->sql = $this->getSqlTools();
    $c->entity = $this->getEntity($entityName);
    return $c;    
  }

  public function getConditionAux($entity, $prefix = ""){
    $dir = "class/model/conditionAux/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $prf = "";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)){
      $className = $prf.snake_case_to("XxYy", $entity) . "ConditionAux";
      require_once($dir.$name);
    } else{
      $className = "ConditionAuxEntityOptions";
      require_once("class/model/entityOptions/ConditionAux.php");
    }
    
    $c = new $className;
    $c->container = $this;
    if($prefix) $c->prefix = $prefix;
    $c->entity = $this->getEntity($entity);
    $c->mapping = $this->getMapping($entity, $prefix);
    $c->sql = $this->getSqlTools();
    return $c;
  }

  public function getFieldAlias($entity, $prefix = ""){
    $dir = "class/model/fieldAlias/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $prf = "";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      $prf = "_";
      require_once($dir.$prf.$name);
    }
    
    $className = $prf.snake_case_to("XxYy", $entity) . "FieldAlias";
    $c = new $className;
    if($prefix) $c->prefix = $prefix;
    $c->container = $this;
    $c->entity = $this->getEntity($entity);
    $c->mapping = $this->getMapping($entity, $prefix);
    return $c;    
  }

  public function getValue($entity, $prefix = ""){
    $dir = "class/model/value/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $prf = "";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      $prf = "_";
      require_once($dir.$prf.$name);
    }
    
    $className = $prf.snake_case_to("XxYy", $entity) . "Value";
    $c = new $className;
    if($prefix) $c->prefix = $prefix;
    $c->container = $this;
    $c->entity = $this->getEntity($entity);
    $c->sql = $this->getSqlTools();
    $c->_logs = new Logs();
    return $c;    
  }

}