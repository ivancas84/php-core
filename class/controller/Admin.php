<?php

require_once("function/snake_case_to.php");
require_once("class/controller/Dba.php");
require_once("class/model/Values.php");

class EntityAdminController { //controlador de administracion de entidad
  
  public $entity; //Entity: Entidad principal de administracion
  public $sqlo; //EntitySqlo: Definicion de sqlo de la entidad

  public static function getInstance($entity) { //instancia a partir de string
    $className = snake_case_to("XxYy", $entity) . "AdminController";
    $instance = new $className;
    return $instance;
  }

  final public static function getInstanceRequire($entity) {    
    require_once("class/controller/admin/" . snake_case_to("xxYy", $entity) . "/" . snake_case_to("XxYy", $entity) . ".php");
    return self::getInstance($entity);
  }

  public function unique(array $params){ //busqueda estricta por campos unicos
    /**
     * $params
     *   array("nombre_field" => "valor_field", ...)
     */
    $sql = $this->sqlo->_unique($params);
    if(!$sql) return null;
    $rows = Dba::fetchAll($sql);

    if(count($rows) > 1) throw new Exception("La busqueda estricta por campos unicos de {$entity} retorno mas de un resultado");
    if(count($rows) == 1) return$rows[0];
    return null;
  }

  public function persistRow($row){
    $ret = [ "id" => null, "sql" => "", "detail" => [] ];
    if(empty($row)) return $ret;

    $row_ = $this->sqlo->_unique($row); //1

    if (!empty($row_)){ //2
      $row["id"] = $row_["id"];
      return $this->sqlo->update($row);
    }

    else { return $this->sqlo->insert($row); } //3
  }

  public function persistValue(EntityValue $value){ return $this->persistRow($value->_toArray()); }
}
