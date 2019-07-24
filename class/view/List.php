<?php

require_once("class/view/View.php");
require_once("class/model/Sqlo.php");
require_once("class/model/Render.php");
require_once("class/model/Dba.php");

class EntityViewList extends View {

  protected static $instances = [];
  public $entityName;
  public $search;
  public $page;
  public $id;
  public $rows;

  public function __construct($entityName){
    $this->entityName = $entityName;
  }

  /*final public static function getInstance() {
    $className = get_called_class();
    if (!isset(self::$instances[$className])) {
      $c = new $className;
      self::$instances[$className] = $c;
    }
    return self::$instances[$className];
  }
  
  final public static function getInstanceRequire($entity) {    
    require_once("class/view/list/" . snake_case_to("XxYy", $entity) . ".php");
    $className = snake_case_to("XxYy", $entity) . "ViewList";
    return call_user_func("{$className}::getInstance");
  }*/

  public function search(){
    $render = new Render();
    //$render->setCondition(["search_","=",$this->search]);
    $sql = EntitySqlo::getInstanceRequire($this->entityName)->all($render);
    $this->rows = Dba::fetchAll($sql);    
  }

 
}
