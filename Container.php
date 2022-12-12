<?php


require_once("config.php"); //configuracion general
require_once("function/snake_case_to.php");
require_once("model/Entity.php");
require_once("model/Field.php");
require_once("model/EntityPersist.php");
require_once("model/EntityTools.php");
require_once("model/EntityQuery.php");
require_once("model/Db.php");


class Container {
  /**
   * Contenedor
   * 
   * Si una clase debe utilizar container, entonces es un controlador o alguno de sus derivados (api, import, etc.).
   * Si una clase debe utilizar container,  entonces debe instanciarse desde container y no deberia tener elementos static.   
   * Si un elemento puede almacenarse en un atributo estatico para ser reutilizado debe definirse un método de instanciacion exclusivo en el contenedor
   */
  static $entitiesTreeJson = [];
  static $entitiesRelationsJson = [];
  static $entitiesJson = [];
  static $fieldsJson = [];
  static $db = null;
  static $modelTools = null;
  static $tools = []; //las instancias dependen de la entidad
  static $persist = []; //las instancias dependen de la entidad
  static $entity = []; //las instancias dependen de la entidad
  static $field = []; //las instancias dependen de la entidad
  static $controller = []; //no todos los controladores son singletones
  static $structure = false; //flag para indicar que se generaron todas las entidades

  public function vendorAutoload(){
    require_once($_SERVER["DOCUMENT_ROOT"] . "/" . PATH_ROOT . '/vendor/autoload.php');
  }

  public function db() {
    if (!isset(self::$db)) {
      self::$db = new Db();
      self::$db->container = $this;
    }
    self::$db::$connections++;
    return self::$db;
  }

  public function auth(){
    require_once("tools/Auth.php");
    $c = new Auth();
    return $c;
  }

  public function treeJson(){
    if (!empty(self::$entitiesTreeJson)) return self::$entitiesTreeJson;

    $string = file_get_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_ROOT . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "entity-tree.json");
    self::$entitiesTreeJson = json_decode($string, true); 
    return self::$entitiesTreeJson;
  }


  public function tree($entityName) {
    $tree = $this->treeJson();
    return array_key_exists($entityName, $tree) ? $tree[$entityName] : [];
  }
  
  public function relationsJson(){
    if (!empty(self::$entitiesRelationsJson)) return self::$entitiesRelationsJson;

    $string = file_get_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_ROOT . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "entity-relations.json");
    self::$entitiesRelationsJson = json_decode($string, true); 
    return self::$entitiesRelationsJson;
  }

  public function relations($entityName) {
    $tree = $this->relationsJson();
    return array_key_exists($entityName, $tree) ? $tree[$entityName] : [];
  }
  
  public function entityNames() {
    $tree = $this->entitiesJson();
    return array_keys($tree);
  }
  
  
  public function structure(){
    if(self::$structure) return self::$entity;
    foreach($this->entityNames() as $entityName){
      $this->entity($entityName);
    }
    self::$structure = true;
    return self::$entity;
  }

  public function entitiesJson(){    
    if (!empty(self::$entitiesJson)) return self::$entitiesJson;
    $string = file_get_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_ROOT. DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "_entities.json");
    $array = json_decode($string, true);

    $string2 = file_get_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_ROOT. DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "entities.json");
    if(!empty($string2)){
      $array2 = json_decode($string2, true);
      foreach($array as $entityName => $value){
        if(array_key_exists($entityName, $array2)){
          
          $array[$entityName] = array_merge($array[$entityName], $array2[$entityName]);
        }
      }
      self::$entitiesJson = $array;
    } else {
      self::$entitiesJson = json_decode($string, true); 
    }
    return self::$entitiesJson;
  }

  public function fieldsJson($entityName){
    if (isset(self::$fieldsJson[$entityName])) return self::$fieldsJson[$entityName];

    $string = file_get_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_ROOT. DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "fields/_" . $entityName . ".json");
    $array = json_decode($string, true);
    
    if(file_exists($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_ROOT. DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "fields/" .  $entityName . ".json")){
      $string2 = file_get_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_ROOT. DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "fields/" .  $entityName . ".json");
      $array2 = json_decode($string2, true);
      foreach($array as $fieldName => $value){
        if(array_key_exists($fieldName, $array2)){
          $array[$fieldName] = array_merge($array[$fieldName], $array2[$fieldName]);
        }
      }
    }
    self::$fieldsJson[$entityName] = $array;
    return self::$fieldsJson[$entityName];
  }

  public function entity($entityName){
    if (isset(self::$entity[$entityName])) return self::$entity[$entityName];

    $entitiesJson = $this->entitiesJson();
    self::$entity[$entityName] = new Entity($entitiesJson[$entityName]);
    self::$entity[$entityName]->container = $this;
    self::$entity[$entityName]->structure = $this->structure();
    return self::$entity[$entityName];
  }

  public function field($entityName, $fieldName){
    if (isset(self::$field[$entityName.UNDEFINED.$fieldName])) return self::$field[$entityName.UNDEFINED.$fieldName]; 

    $fieldsJson = $this->fieldsJson($entityName);
    self::$field[$entityName.UNDEFINED.$fieldName] = (array_key_exists($fieldName, $fieldsJson)) ? new Field($fieldsJson[$fieldName]) : new Field(["name"=>$fieldName]);
    self::$field[$entityName.UNDEFINED.$fieldName]->container = $this;
    self::$field[$entityName.UNDEFINED.$fieldName]->entityName = $entityName;
    return self::$field[$entityName.UNDEFINED.$fieldName]; 
  }

  public function fieldById($entityName, $fieldId){
    $relations = $this->relations($entityName);
    return $this->field($entityName, $relations[$fieldId]["field_name"]);
  }

  protected function instanceFromDir($dir, $action, $entityName){
    $d = snake_case_to("xxYy", $dir);
    $D = snake_case_to("XxYy", $dir);
    $a = snake_case_to("xxYy", $action);
    $A = snake_case_to("XxYy", $action);
    $E = snake_case_to("XxYy", $entityName);

    $aa = @include_once $d . DIRECTORY_SEPARATOR . $a . DIRECTORY_SEPARATOR .$E . ".php";
	
	  if($aa){
      $className =  $E . $A . $D;
    } else{
	    require_once("". $d . "/" . $A . ".php");
      $className = $A   . $D;
    }

    $c = new $className;
    $c->container = $this;
    $c->entityName = $entityName;
    return $c;
  }

  public function api($action, $entityName) {
    return $this->instanceFromDir("api",$action,$entityName);
  }

  public function script($action, $entityName) {
    return $this->instanceFromDir("script",$action,$entityName);
  }

  public function pdf($action, $entityName) {
    return $this->instanceFromDir("pdf",$action,$entityName);
  }
  
  public function controller_($controller, $singleton = false){
    /**
     * Controlador (si utilizan container o algun elemento que pueda instanciarse desde container entonces es un controlador)
     */
    $dir = "controller/";
    $name = snake_case_to("XxYy", $controller) . ".php";
    $className = snake_case_to("XxYy", $controller);    
    require_once($dir.$name);
    
    if($singleton) {
      if(!empty(self::$controller[$controller])) return self::$controller[$controller];
    }
    self::$controller[$controller] = new $className;
    self::$controller[$controller]->container = $this;
    return self::$controller[$controller];
  }

  public function tools_($tool){
    /**
     * Tools (no se les asigna ningun parametro adicional)
     */
    $dir = "tools/";
    $name = snake_case_to("XxYy", $tool) . ".php";
    $className = snake_case_to("XxYy", $tool);    
    require_once($dir.$name);
    $c = new $className;
    return $c;
  }
  
  public function controller($controller, $entityName, $prefix = null){
    /**
     * Controlador asociado a entidad
     */
    $path = "controller/" . snake_case_to("xxYy", $controller) . "/" . snake_case_to("XxYy", $entityName) . ".php";
    
    if((@include_once $path) == true){
      $className = snake_case_to("XxYy", $entityName) . snake_case_to("XxYy", $controller);    
    } else{
      require_once("controller/" . snake_case_to("XxYy", $controller) . ".php");
      $className = snake_case_to("XxYy", $controller);    
    }

    $c = new $className;
    $c->container = $this;
    $c->entityName = $entityName;
    if(!empty($prefix)) $c->prefix = $prefix;
    return $c;
  }

  public function import($id){
    $path = "import/" . snake_case_to("xxYy", $id) . "/Import.php";
    $className = snake_case_to("XxYy", $id)."Import";    
    require_once($path);
    $c = new $className;
    $c->id = $id;
    $c->container = $this;
    return $c;
  }

  
  /**
   * Obtener elemento de importacion
   * 
   * @param $import Clase de importacion
   */
  public function importElement($entityName, $import){
    $path = "import/" . snake_case_to("xxYy", $entityName) . "/Element.php";
    $className = snake_case_to("XxYy", $entityName)."ImportElement";    
    require_once($path);
    $c = new $className;
    $c->entityName = $entityName;
    $c->logs = $this->tools_("logs");
    $c->import = $import;
    $c->container = $this;
    return $c;
  }

  public function persist($entityName) {
    if (isset(self::$persist[$entityName])) return self::$persist[$entityName];

    $c = new EntityPersist;
    $c->entityName = $entityName;
    $c->container = $this;
    return self::$persist[$entityName] = $c;
  }

  public function query($entityName = null){
    $render = new EntityQuery;
    $render->entityName = $entityName;
    $render->container = $this;
    return $render;
  }

  public function tools($entityName) {
    if (isset(self::$tools[$entityName])) return self::$tools[$entityName];

    $c = new EntityTools;
    $c->entityName = $entityName;
    $c->container = $this;
    return self::$tools[$entityName] = $c;
  }


  public function mapping($entityName, $prefix = ""){
    $dir = "model/mapping/";
    $name = snake_case_to("XxYy", $entityName) . ".php";
    if((@include_once $dir.$name) == true){
      $className = snake_case_to("XxYy", $entityName) . "Mapping";
    } else{
      require_once("model/entityOptions/Mapping.php");
      $className = "MappingEntityOptions";
    }
    
    $c = new $className;
    if($prefix) $c->prefix = $prefix;
    $c->entityName = $entityName;
    $c->container = $this;
    return $c;    
  }

  
  public function condition($entityName, $prefix = ""){
    $dir = "model/condition/";
    $name = snake_case_to("XxYy", $entityName) . ".php";
    if((@include_once $dir.$name) == true){      
      $className = $prf.snake_case_to("XxYy", $entityName) . "Condition";
    } else {
      require_once("model/entityOptions/Condition.php");
      $className = "ConditionEntityOptions";
    }

    $c = new $className;
    if($prefix) $c->prefix = $prefix;
    $c->container = $this;
    $c->entityName = $entityName;
    return $c;    
  }

  public function value($entityName, $prefix = ""){
    /**
     * Definir instancia de Value para la entidad.
     * 
     * Value es una clase utilizada para manipular los valores de una entidad.
     * 
     * @param string $entityName Nombre de la entidad
     * @param prefix Prefijo de identificacion. El prefijo es util cuando los 
     * valores se obtienen de resultado de relaciones.
     */
    $dir = "model/value/";
    $name = snake_case_to("XxYy", $entityName) . ".php";
    if((@include_once $dir.$name) == true){ //si existe se utiliza la clase exclusiva
      $className = snake_case_to("XxYy", $entityName) . "Value";
    } else { //si no existe clase exclusiva, se utiliza clase general
      require_once("model/entityOptions/Value.php");
      $className = "ValueEntityOptions";
    }

    $c = new $className;
    if($prefix) $c->prefix = $prefix;
    $c->container = $this;
    $c->entityName = $entityName;
    $c->logs = $this->tools_("logs");
    return $c;    
  }


  /**
   * Separar un field en 3 elementos field_id, entity_name y field_name
   */
  public function explodeField($entityName, $field){
    $f = explode("-",$field);

    if(count($f) == 2) return [
        "field_id" => $f[0],
        "entity_name" => $this->relations($entityName)[$f[0]]["entity_name"],
        "field_name" => $f[1]
    ];

    return [
      "field_id" => "",
      "entity_name" => $entityName,
      "field_name" => $field
    ];
  }
}