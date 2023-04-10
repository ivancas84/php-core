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
  static $entities_tree_json = [];
  static $entities_relations_json = [];
  static $entities_json = [];
  static $fields_json = [];
  static $db = null;
  static $model_tools = null;
  static $tools = []; //las instancias dependen de la entidad
  static $persist = []; //las instancias dependen de la entidad
  static $entity = []; //las instancias dependen de la entidad
  static $field = []; //las instancias dependen de la entidad
  static $controller = []; //no todos los controladores son singletones
  static $structure = false; //@deprecated? flag para indicar que se generaron todas las entidades

  public function vendor_autoload(){
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

  public function tree_json(){
    if (!empty(self::$entities_tree_json)) return self::$entities_tree_json;

    $string = file_get_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_ROOT . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "entity-tree.json");
    self::$entities_tree_json = json_decode($string, true); 
    return self::$entities_tree_json;
  }


  public function tree($entity_name) {
    $tree = $this->tree_json();
    return array_key_exists($entity_name, $tree) ? $tree[$entity_name] : [];
  }
  
  public function relations_json(){
    if (!empty(self::$entities_relations_json)) return self::$entities_relations_json;

    $string = file_get_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_ROOT . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "entity-relations.json");
    self::$entities_relations_json = json_decode($string, true); 
    return self::$entities_relations_json;
  }

  public function relations($entity_name) {
    $tree = $this->relations_json();
    return array_key_exists($entity_name, $tree) ? $tree[$entity_name] : [];
  }
  
  public function entity_names() {
    $tree = $this->entities_json();
    return array_keys($tree);
  }
  
  
  public function structure(){
    if(self::$structure) return self::$entity;
    foreach($this->entity_names() as $entity_name){
      $this->entity($entity_name);
    }
    self::$structure = true;
    return self::$entity;
  }

  public function entities_json(){    
    if (!empty(self::$entities_json)) return self::$entities_json;
    $string = file_get_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_ROOT. DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "_entities.json");
    $array = json_decode($string, true);

    $string2 = file_get_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_ROOT. DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "entities.json");
    if(!empty($string2)){
      $array2 = json_decode($string2, true);
      foreach($array as $entity_name => $value){
        if(array_key_exists($entity_name, $array2)){
          
          $array[$entity_name] = array_merge($array[$entity_name], $array2[$entity_name]);
        }
      }
      self::$entities_json = $array;
    } else {
      self::$entities_json = json_decode($string, true); 
    }
    return self::$entities_json;
  }

  public function fields_json($entity_name){
    if (isset(self::$fields_json[$entity_name])) return self::$fields_json[$entity_name];

    $string = file_get_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_ROOT. DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "fields/_" . $entity_name . ".json");
    $array = json_decode($string, true);
    
    if(file_exists($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_ROOT. DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "fields/" .  $entity_name . ".json")){
      $string2 = file_get_contents($_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . PATH_ROOT. DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR . "fields/" .  $entity_name . ".json");
      $array2 = json_decode($string2, true);
      foreach($array as $field_name => $value){
        if(array_key_exists($field_name, $array2))
          $array[$field_name] = array_merge($array[$field_name], $array2[$field_name]);
      }
      foreach($array2 as $field_name => $value){
        if(!array_key_exists($field_name, $array)) 
          $array[$field_name] = $array2[$field_name];
      }
    }
    self::$fields_json[$entity_name] = $array;
    return self::$fields_json[$entity_name];
  }

  public function entity($entity_name){
    if (isset(self::$entity[$entity_name])) return self::$entity[$entity_name];

    $entities_json = $this->entities_json();
    self::$entity[$entity_name] = new Entity($entities_json[$entity_name]);
    self::$entity[$entity_name]->container = $this;
    self::$entity[$entity_name]->structure = $this->structure();
    return self::$entity[$entity_name];
  }

  public function field($entity_name, $field_name){
    if (isset(self::$field[$entity_name.UNDEFINED.$field_name])) {
        return self::$field[$entity_name.UNDEFINED.$field_name]; 
    }
    $fields_json = $this->fields_json($entity_name);
    self::$field[$entity_name.UNDEFINED.$field_name] = (array_key_exists($field_name, $fields_json)) ? new Field($fields_json[$field_name]) : new Field(["name"=>$field_name]);
    self::$field[$entity_name.UNDEFINED.$field_name]->container = $this;
    self::$field[$entity_name.UNDEFINED.$field_name]->entity_name = $entity_name;
    return self::$field[$entity_name.UNDEFINED.$field_name]; 
  }

  public function field_by_id($entity_name, $field_id){
    $relations = $this->relations($entity_name);
    return $this->field($entity_name, $relations[$field_id]["field_name"]);
  }

  protected function instance_from_dir($dir, $action, $entity_name){
    $D = snake_case_to("XxYy", $dir);
    $A = snake_case_to("XxYy", $action);
    $E = snake_case_to("XxYy", $entity_name);

    $aa = @include_once $dir . DIRECTORY_SEPARATOR . $entity_name . DIRECTORY_SEPARATOR . $action . ".php";
	
	  if($aa){
      $class_name =  $E . $A . $D;
    } else{
	    require_once("". $dir . "/" . $action . ".php");
      $class_name = $A   . $D;
    }

    $c = new $class_name;
    $c->container = $this;
    $c->entity_name = $entity_name;
    return $c;
  }

  public function api($action, $entity_name = "") {
    return $this->instance_from_dir("api",$action,$entity_name);
  }

  public function script($action, $entity_name = "") {
    return $this->instance_from_dir("script",$action,$entity_name);
  }

  public function pdf($action, $entity_name = "") {
    return $this->instance_from_dir("pdf",$action,$entity_name);
  }
  
  public function controller_($controller, $singleton = false){
    /**
     * Controlador (si utilizan container o algun elemento que pueda instanciarse desde container entonces es un controlador)
     */
    $dir = "controller/";
    $name = snake_case_to("XxYy", $controller) . ".php";
    $class_name = snake_case_to("XxYy", $controller);    
    require_once($dir.$name);
    
    if($singleton) {
      if(!empty(self::$controller[$controller])) return self::$controller[$controller];
    }
    self::$controller[$controller] = new $class_name;
    self::$controller[$controller]->container = $this;
    return self::$controller[$controller];
  }

  public function tools_($tool){
    /**
     * Tools (no se les asigna ningun parametro adicional)
     */
    $dir = "tools/";
    $name = snake_case_to("XxYy", $tool) . ".php";
    $class_name = snake_case_to("XxYy", $tool);    
    require_once($dir.$name);
    $c = new $class_name;
    return $c;
  }
  
  public function controller($controller, $entity_name = "", $prefix = ""){
    /**
     * Controlador asociado a entidad
     */
    if((@include_once "controller/" . $entity_name . "/" . $controller . ".php") == true){
      $class_name = snake_case_to("XxYy", $entity_name) . snake_case_to("XxYy", $controller);    
    } else{
      require_once("controller/" . $controller . ".php");
      $class_name = snake_case_to("XxYy", $controller);    
    }

    $c = new $class_name;
    $c->container = $this;
    $c->entity_name = $entity_name;
    if(!empty($prefix)) $c->prefix = $prefix;
    return $c;
  }

  public function import($id){
    $path = "import/" .  $id . "/Import.php";
    $class_name = snake_case_to("XxYy", $id)."Import";       
    require_once($path);
    $c = new $class_name;
    $c->id = $id;
    $c->container = $this;
    return $c;
  }

  
  /**
   * Obtener elemento de importacion
   * 
   * @param $import Clase de importacion
   */
  public function import_element($entity_name, $import){
    $path = "import/" .  $entity_name . "/Element.php";
    $class_name = snake_case_to("XxYy", $entity_name)."ImportElement";    
    require_once($path);
    $c = new $class_name;
    $c->entity_name = $entity_name;
    $c->logs = $this->tools_("logs");
    $c->import = $import;
    $c->container = $this;
    return $c;
  }

  public function persist($entity_name) {
    if (isset(self::$persist[$entity_name])) return self::$persist[$entity_name];

    $c = new EntityPersist;
    $c->entity_name = $entity_name;
    $c->container = $this;
    return self::$persist[$entity_name] = $c;
  }

  public function query($entity_name = null){
    $render = new EntityQuery;
    $render->entity_name = $entity_name;
    $render->container = $this;
    return $render;
  }

  public function tools($entity_name) {
    if (isset(self::$tools[$entity_name])) return self::$tools[$entity_name];

    $c = new EntityTools;
    $c->entity_name = $entity_name;
    $c->container = $this;
    return self::$tools[$entity_name] = $c;
  }


  public function mapping($entity_name, $prefix = ""){
    $dir = "model/mapping/";
    $name = snake_case_to("XxYy", $entity_name) . ".php";
    if((@include_once $dir.$name) == true){
      $class_name = snake_case_to("XxYy", $entity_name) . "Mapping";
    } else{
      require_once("model/entityOptions/Mapping.php");
      $class_name = "MappingEntityOptions";
    }
    
    $c = new $class_name;
    if($prefix) $c->prefix = $prefix;
    $c->entity_name = $entity_name;
    $c->container = $this;
    return $c;    
  }

  
  public function condition($entity_name, $prefix = ""){
    $dir = "model/condition/";
    $name = snake_case_to("XxYy", $entity_name) . ".php";
    if((@include_once $dir.$name) == true){      
      $class_name = $prf.snake_case_to("XxYy", $entity_name) . "Condition";
    } else {
      require_once("model/entityOptions/Condition.php");
      $class_name = "ConditionEntityOptions";
    }

    $c = new $class_name;
    if($prefix) $c->prefix = $prefix;
    $c->container = $this;
    $c->entity_name = $entity_name;
    return $c;    
  }

  public function value($entity_name, $prefix = ""){
    /**
     * Definir instancia de Value para la entidad.
     * 
     * Value es una clase utilizada para manipular los valores de una entidad.
     * 
     * @param string $entity_name Nombre de la entidad
     * @param prefix Prefijo de identificacion. El prefijo es util cuando los 
     * valores se obtienen de resultado de relaciones.
     */
    $dir = "model/value/";
    $name = snake_case_to("XxYy", $entity_name) . ".php";
    if((@include_once $dir.$name) == true){ //si existe se utiliza la clase exclusiva
      $class_name = snake_case_to("XxYy", $entity_name) . "Value";
    } else { //si no existe clase exclusiva, se utiliza clase general
      require_once("model/entityOptions/Value.php");
      $class_name = "ValueEntityOptions";
    }

    $c = new $class_name;
    if($prefix) $c->prefix = $prefix;
    $c->container = $this;
    $c->entity_name = $entity_name;
    $c->logs = $this->tools_("logs");
    return $c;    
  }


    /**
     * Separar un field en 3 elementos field_id, entity_name y field_name
     */
    public function explode_field($entity_name, $field_name){
        $f = explode("-",$field_name);

        if(count($f) == 2) return [
            "field_id" => $f[0],
            "entity_name" => $this->relations($entity_name)[$f[0]]["entity_name"],
            "field_name" => $f[1]
        ];

        return [
            "field_id" => "",
            "entity_name" => $entity_name,
            "field_name" => $field_name
        ];
    }
}