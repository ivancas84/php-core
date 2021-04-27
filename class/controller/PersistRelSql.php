<?php

require_once("class/model/Rel.php");
require_once("function/php_input.php");
require_once("function/get_entity_rel.php");

class PersistRelSql { //3
  /**
   * Controlador para procesar una entidad y sus relaciones 
   */

  public $entityName; //entidad principal
  public $container;
  public $params = [];
  public $paramsUm = [];
  public $persistController = "id";
  public $detail = [];
  public $sql = "";

  public function compare($a, $b) {
    /**
     * Comparacion de elementos de un array 
     * para calcular la profundidad de la relacion
     */
    $a_ = ($a != $this->entityName) ? ($c = substr_count($a, '_') ? $c : 1) : 0;
    $b_ = ($b != $this->entityName) ? ($c = substr_count($b, '_') ? $c : 1) : 0;
    
    if ($a_ == $b_)  return 0;
    return ($a_ > $b_) ? -1 : 1;
  }

  

  function sortParams($params){ 
    /**
     * Array asociativo
     * Cada llave identifica:
     * 1) La entidad actual: Ejemplo "alumno"
     * 2) Una relacion fk directa "per" o indirecta "per_dom"
     * 3) Una relacion um (si posee el caracter "/"): 
     *   Ej 1 nota/alumno: nota (entidad) nota.alumno (fk), 
     *   Ej 2: per-toma/docente: toma (entidad) toma.docente (fk), 
     *     luego docente esta asociado con alumno 
     *     a traves de la relacion indicada en el prefijo "per"
     */ 
    
    foreach($params as $key => $value){
      if((strpos($key, '/') === false)) $this->params[$key]=$value;
      else $this->paramsUm[$key] = $value;
    }

    uksort($this->params, array($this, "compare"));

    /**
     * Para el ordenamiento se verifica el caracter "_" 
     * para calcular la profundidad.
     * En base a la "profundidad" se define el orden de persistencia
     */
  }

  public function main($params){
    $this->sortParams($params);
    $this->procesarParams();
    $this->procesarParamsUm();

    return ["id" => $this->params[$this->entityName]["id"], "detail" => $this->detail, "sql"=>$this->sql];

    
  }


  public function procesarParamsUm(){
    foreach($this->paramsUm as $key => $value) {
      $i = strpos($key, '-');
      $j = strpos($key, '/');

      if( $i === false){
        $prefix = $this->entityName;
        $entityName = substr($key,0,$j);   
      } else {
        $prefix = substr($key,0,$i);
        $entityName = substr($key,$i+1,($j-$i-1));
      }
      $fkName = substr($key,$j+1);


      foreach($value as $k => $row){
        $row[$fkName] = $this->params[$prefix]["id"];
        $persistRelSqlArray = $this->container->getControllerEntity("persist_rel_sql_array",$entityName);
        $p = $persistRelSqlArray->main($row);
        $this->sql .= $p["sql"];
        $this->detail =array_merge($this->detail, $p["detail"]);
      }

    
    }
  }


  public function procesarParamsRel($key, $row){
    //1) Definir $entityName, $fieldName en base a $key y $this->entityName
    $entityName = get_entity_rel($this->entityName)[$key]["entity_name"];
    $fieldName = get_entity_rel($this->entityName)[$key]["field_name"];
    
    //2) Definir $render en base a $entityName
    $render = $this->container->getControllerEntity("render_build", $entityName)->main();
    
    //3) Verificar si se debe eliminar (asignar $fkValue en null)
    if(array_key_exists("_delete",$row) && $row["_delete"]){
      $sql = $this->container->getSqlo($entityName)->delete([$row["id"]]);
      $persist = ["id" => $row["id"],"sql"=>$sql];
      $fkValue = null;
    } 
    
    //4) Verificar si se debe insertar o actualizar (asignar $fkValue con el id persistido)
    else {
      $p = $this->container->getControllerEntity("persist_sql", $render->entityName);
      $persist = ($this->persistController == "id") ?
        $p->id($this->params[$key]) : $p->unique($this->params[$key]);
      $idValue = $persist["id"];
    }
    
    //5) Actualizar $this->sql y $this->detail
    $this->sql .= $persist["sql"];
    array_push($this->detail, $entityName.$persist["id"]);
    
    //6) Asignar fk
    $pos = strrpos($key,"_");
    if($pos !== false){ 
      $s = substr($key, 0, $pos);
      foreach($this->params as $k => &$value)
        if($k_ == $s) {
          $value[$fieldName] = $idValue;
          break;
        }
    } else $this->params[$this->entityName][$fieldName] = $idValue;

    return $idValue;
  }

  public function procesarParamsEntity($row){
    //1) Definir $render en base a $this->entityName 
    $render = $this->container->getControllerEntity("render_build", $this->entityName)->main();
    
    //2) Verificar si se debe eliminar (asignar $idValue en null)
    if(array_key_exists("_delete",$row) && $row["_delete"]){
      $sql = $this->container->getSqlo($this->entityName)->delete([$row["id"]]);
      $persist = ["id" => $row["id"],"sql"=>$sql];
      $idValue = null;
    }

    //3) Verificar si se debe insertar o actualizar (asignar $idValue con el id persistido)
    else {
      $p = $this->container->getControllerEntity("persist_sql", $render->entityName);
      $persist = ($this->persistController == "id") ?
        $p->id($this->params[$this->entityName]) : $p->unique($render->entityName, $this->params[$key]);
      $idValue = $persist["id"];
    }

    //4) Actualizar $this->sql y $this->detail

    $this->sql .= $persist["sql"];
    array_push($this->detail, $this->entityName.$persist["id"]);

    return $idValue;
  }


  public function procesarParams(){
    foreach($this->params as $key => $row) {
      $idValue = ($key != $this->entityName) ?
        $this->procesarParamsRel($key, $row) :
        $this->procesarParamsEntity($row);
      $this->params[$key]["id"] = $idValue; //en el caso de eliminacion se carga en null
    }
  }
}


