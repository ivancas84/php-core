<?php

require_once("class/model/Ma.php");

class Container {
  static $db = null;
  static $sqlFormat = null;
  static $sqlo = [];
  static $entity = [];
  static $field = [];

  public function getDb() {
    if (isset(self::$db)) return self::$db;
    $c = new Ma();
    $c->container = $this;
    return self::$db = $c;
  }

  public function getSqlFormat(){
    if (isset(self::$sqlFormat)) return self::$sqlFormat;
    $sqlFormat = self::$sqlFormat = new SqlFormat();
    $sqlFormat->db = $this->getDb();
    return self::$sqlFormat = $sqlFormat;
  }

  public function getEntity($entity){
    $dir = "class/model/entity/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $prefix = "";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      $prefix = "_";
      require_once($dir.$prefix.$name);
    }
    
    $className = $prefix.snake_case_to("XxYy", $entity) . "Entity";
    if (isset(self::$entity[$className])) return self::$entity[$className];
    $c = new $className;
    $c->container = $this;
    return self::$entity[$className] = $c;
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

  public function getController($controller, $entityName){

    $dir = "class/controller/" . snake_case_to("xxYy", $controller) . "/";
    $name = snake_case_to("XxYy", $entityName) . ".php";
    $className = snake_case_to("XxYy", $entityName) . snake_case_to("XxYy", $controller);    
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      require_once($dir."_".$name);
      $className = "_".$className;    
    }

    $c = new $className;
    $c->container = $this;
    $c->db = $this->getDb();
    return $c;
  }

  public function getValues($entity){
    $dir = "class/model/values/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $prf = "";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      $prf = "_";
      require_once($dir.$prf.$name);
    }
    
    $className = $prf.snake_case_to("XxYy", $entity);
    $c = new $className;
    $c->_logs = new Logs();
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
    return self::$sqlo[$className] = $c;
  }

  public function getSql($entity, $prefix = null){
    $dir = "class/model/sql/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $prf = "";
    if(file_exists($_SERVER["DOCUMENT_ROOT"]."/".PATH_SRC."/".$dir.$name)) require_once($dir.$name);
    else{
      $prf = "_";
      require_once($dir.$prf.$name);
    }
    
    $className = $prf.snake_case_to("XxYy", $entity) . "Sql";
    $sql = new $className;
    if($prefix) $sql->prefix = $prefix;
    $sql->container = $this;
    $sql->entity = $this->getEntity($entity);
    $sql->format = $this->getSqlFormat();
    return $sql;    
  }




}