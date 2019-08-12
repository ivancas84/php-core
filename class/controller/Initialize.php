<?php

require_once("function/snake_case_to.php");
require_once("class/model/Dba.php");
require_once("class/model/Values.php");

class EntityInitializeController { //controlador de inicializacion de entidad
  
  public $entity; //Entity: Entidad principal de administracion
  public $sqlo; //EntitySqlo: Definicion de sqlo de la entidad

  public static function getInstance($entity) { //instancia a partir de string  
    $className = snake_case_to("XxYy", $entity) . "InitializeController";
    $instance = new $className;
    return $instance;
  }

  final public static function getInstanceRequire($entity) {    
    require_once("class/controller/initialize/" . snake_case_to("xxYy", $entity) . "/" . snake_case_to("XxYy", $entity) . ".php");
    return self::getInstance($entity);
  }

  public function idOrNull($id){ //id o null
    /**
     * $params
     *   array("nombre_field" => "valor_field", ...)
     */
    $sql = $this->sqlo->getAll([$id]);
    if(!$sql) return null;
    $rows = Dba::fetchAll($sql);

    if(count($rows) > 1) throw new Exception("La busqueda por id retorno mas de un resultado");
    if(count($rows) == 1) return$rows[0];
    return null;
  }
  
}
