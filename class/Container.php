<?php

require_once("function/snake_case_to.php");

class Container {
  /**
   * Si una clase debe utilizar container, 
   * entonces es un controlador o alguno de sus derivados (api, import, etc.).
   * Si una clase debe utilizar container, 
   * entonces debe instanciarse desde container y no deberia tener elementos static.   
   * Si un elemento puede almacenarse en un atributo estatico para ser reutilizado,
   * debe definirse un método de instanciacion exclusivo en el contenedor
   */
  static $db = null;

  static $sqlo = []; //las instancias dependen de la entidad
  static $rel = []; //las instancias dependen de la entidad
  static $entity = []; //las instancias dependen de la entidad
  static $field = []; //las instancias dependen de la entidad
  static $structure = false; //flag para indicar que se generaron todas las entidades

  public function getDb() {
    if (isset(self::$db)) return self::$db;
    require_once("class/model/Ma.php");
    $c = new Ma();
    $c::$connections++;
    $c->container = $this;
    return self::$db = $c;
  }

  public function getAuth(){
    require_once("class/tools/Auth.php");
    $c = new Auth();
    return $c;
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

    if((@include_once $dir.$name) == true) 
      $className = snake_case_to("XxYy", $entityName) . "Entity";
    else{      
      require_once($dir."_".$name);
      $className = "_".snake_case_to("XxYy", $entityName) . "Entity";
    }
    
    $c = new $className;
    $c->container = $this;
    self::$entity[$entityName] = $c; //se asigna previamente como clase estatica antes de llamar a la estructura, la estructura poseera tambien la entidad
    self::$entity[$entityName]->structure = $this->getStructure();
    return self::$entity[$entityName];
  }


  public function getField($entity, $field){
    if (isset(self::$field[$entity.$field])) return self::$field[$entity.$field]; 

    $dir = "class/model/field/" . snake_case_to("xxYy", $entity) . "/";
    $name = snake_case_to("XxYy", $field) . ".php";    
    $prefix = "";

    if((@include_once $dir.$name) == true){
      $className = "Field".snake_case_to("XxYy", $entity) . snake_case_to("XxYy", $field);  
    } elseif((@include_once $dir."_".$name) == true) {
        $className = "_Field".snake_case_to("XxYy", $entity) . snake_case_to("XxYy", $field);  
    } else {
      require_once("class/model/field/".$name);
      $className = "_Field".snake_case_to("XxYy", $field);  
    }
    
    $c = new $className;
    $c->container = $this;
    $c->entityName = $entity;
    return self::$field[$entity.$field] = $c; 
  }

  public function getApi($action, $entityName) {
    $path = "class/api/" . snake_case_to("xxYy", $action) . "/" . snake_case_to("XxYy", $entityName) . ".php";
    if((@include_once $path) == true){
      $className = snake_case_to("XxYy", $entityName) . snake_case_to("XxYy", $action). "Api";
    } else{
      require_once("class/api/" . snake_case_to("XxYy", $action) . ".php");
      $className = snake_case_to("XxYy", $action)   . "Api";
    }

    $c = new $className;
    $c->container = $this;
    $c->entityName = $entityName;
    return $c;
  }


  public function getController($controller){
    /**
     * Controlador (si utilizan container o algun elemento que pueda instanciarse desde container entonces es un controlador)
     */
    $dir = "class/controller/";
    $name = snake_case_to("XxYy", $controller) . ".php";
    $className = snake_case_to("XxYy", $controller);    
    require_once($dir.$name);
    $c = new $className;
    $c->container = $this;
    return $c;
  }

  public function getTool($tool){
    /**
     * Tools (no se les asigna ningun parametro adicional)
     */
    $dir = "class/tools/";
    $name = snake_case_to("XxYy", $tool) . ".php";
    $className = snake_case_to("XxYy", $tool);    
    require_once($dir.$name);
    $c = new $className;
    return $c;
  }
  
  public function getControllerEntity($controller, $entityName){
    /**
     * Controlador asociado a entidad
     */
    $path = "class/controller/" . snake_case_to("xxYy", $controller) . "/" . snake_case_to("XxYy", $entityName) . ".php";
    
    if((@include_once $path) == true){
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
    $c->logs = $this->getTool("logs");
    $c->container = $this;
    return $c;
  }

  public function getSqlo($entity) {
    if (isset(self::$sqlo[$entity])) return self::$sqlo[$entity];

    $dir = "class/model/sqlo/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $prefix = "";

    if((@include_once $dir.$name) == true){
      $className = snake_case_to("XxYy", $entity) . "Sqlo";
    } else {
      require_once("class/model/Sqlo.php");
      $className = "EntitySqlo";
    }

    $c = new $className;
    $c->entityName = $entity;
    $c->container = $this;
    return self::$sqlo[$entity] = $c;
  }

  public function getRender($entityName = null){
    require_once("class/model/Render.php");
    $render = new Render;
    $render->container = $this;  
    $render->entityName = $entityName;    
    return $render;    
  }

  public function getSql($entity, $prefix = null){
    $dir = "class/model/sql/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    $prf = "";

    if((@include_once $dir.$name) == true){
      $className = snake_case_to("XxYy", $entity) . "Sql";
    
    } else {
      require_once("class/model/Sql.php");
      $className = "EntitySql";
    }
    
    $sql = new $className;
    if($prefix) $sql->prefix = $prefix;
    $sql->container = $this;  
    $sql->entityName = $entity;    
    return $sql;    
  }

  public function getRel($entity, $prefix = "") {
    if (isset(self::$rel[$entity])) return self::$rel[$entity];

    $dir = "class/model/rel/";
    $name = snake_case_to("XxYy", $entity) . ".php";
    
    if((@include_once $dir.$name) == true){
      $className = snake_case_to("XxYy", $entity) . "Rel";
    
    } else {
      require_once("class/model/Rel.php");
      $className = "EntityRel";
    }
      
    $c = new $className;
    $c->entityName = $entity;
    $c->prefix = $prefix;
    $c->container = $this;
    return self::$rel[$entity] = $c;
  }


  public function getMapping($entityName, $prefix = ""){
    $dir = "class/model/mapping/";
    $name = snake_case_to("XxYy", $entityName) . ".php";
    if((@include_once $dir.$name) == true){
      $className = snake_case_to("XxYy", $entityName) . "Mapping";
    } else{
      require_once("class/model/entityOptions/Mapping.php");
      $className = "MappingEntityOptions";
    }
    
    $c = new $className;
    if($prefix) $c->prefix = $prefix;
    $c->entityName = $entityName;
    $c->container = $this;
    $c->entity = $this->getEntity($entityName);
    return $c;    
  }
  
  public function getCondition($entityName, $prefix = ""){
    $dir = "class/model/condition/";
    $name = snake_case_to("XxYy", $entityName) . ".php";
    if((@include_once $dir.$name) == true){      
      $className = $prf.snake_case_to("XxYy", $entityName) . "Condition";
    } else {
      require_once("class/model/entityOptions/Condition.php");
      $className = "ConditionEntityOptions";
    }

    $c = new $className;
    if($prefix) $c->prefix = $prefix;
    $c->container = $this;
    $c->mapping = $this->getMapping($entityName, $prefix);
    $c->value = $this->getValue($entityName, $prefix);
    $c->sql = $this->getController("sql_tools");;
    $c->entity = $this->getEntity($entityName);
    $c->entityName = $entityName;
    return $c;    
  }

  public function getConditionAux($entityName, $prefix = ""){
    $dir = "class/model/conditionAux/";
    $name = snake_case_to("XxYy", $entityName) . ".php";
    $prf = "";
    if((@include_once $dir.$name) == true){
      $className = $prf.snake_case_to("XxYy", $entityName) . "ConditionAux";      
    } else{
      require_once("class/model/entityOptions/ConditionAux.php");
      $className = "ConditionAuxEntityOptions";
    }
    
    $c = new $className;
    $c->container = $this;
    if($prefix) $c->prefix = $prefix;
    $c->entity = $this->getEntity($entityName);
    $c->entityName = $entityName;
    $c->mapping = $this->getMapping($entityName, $prefix);
    $c->sql = $this->getController("sql_tools");
    return $c;
  }

  public function getFieldAlias($entityName, $prefix = ""){
    $dir = "class/model/fieldAlias/";
    $name = snake_case_to("XxYy", $entityName) . ".php";
    if((@include_once $dir.$name) == true){
      $className = $prf.snake_case_to("XxYy", $entityName) . "FieldAlias";
    } else {
      require_once("class/model/entityOptions/FieldAlias.php");
      $className = "FieldAliasEntityOptions";
    }
    
    $c = new $className;
    if($prefix) $c->prefix = $prefix;
    $c->container = $this;
    $c->entityName = $entityName;
    $c->entity = $this->getEntity($entityName);
    $c->mapping = $this->getMapping($entityName, $prefix);
    return $c;    
  }

  public function getValue($entityName, $prefix = ""){
    $dir = "class/model/value/";
    $name = snake_case_to("XxYy", $entityName) . ".php";
    if((@include_once $dir.$name) == true){
      $className = snake_case_to("XxYy", $entityName) . "Value";
    } else {
      require_once("class/model/entityOptions/Value.php");
      $className = "ValueEntityOptions";
    }

    $c = new $className;
    if($prefix) $c->prefix = $prefix;
    $c->container = $this;
    $c->entityName = $entityName;
    $c->sql = $this->getController("sql_tools");
    $c->logs = $this->getTool("logs");
    return $c;    
  }

}