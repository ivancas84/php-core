<?php

require_once("function/php_input.php");

class PersistRelSql {
  /**
   * Controlador para procesar una entidad y sus relaciones 
   * recibe un array multiple, ejemplo:
   * [
   *   alumno => [
   *     id => ...
   *     activo => ...
   *   ]
   *   per => [
   *     id => ...
   *     nombres => ...
   *   ]
   *   per_dom => [
   *     id => ...
   *     calle => ...
   *   ]
   *   nota/alumno => [ //nota (entidad) alumno.nota (fk). Solo se pueden procesar las relaciones um directas no se admiten una profundidad mayor en el arbol
   *     [id => ..., calificacion => ...]
   *     [...]
   *   ]
   * ]
   * 
   */

  public $entityName; //entidad principal
  public $container;
  public $data = [];
  public $detail = [];
  public $sql = "";
  public $rel;
  public $fieldIds;
  public $fieldIdsProcesados;
  public $index = 0;

  public function main($data){
    $this->data = $data;
    $this->fieldIds = array_fieldIds($this->data);
    $this->rel = $this->container->relations($this->entityName);
    $this->procesarData();
    $this->procesarUm();

    return ["id" => $this->data[$this->entityName]["id"], "detail" => $this->detail, "sql"=>$this->sql];
  }

  public function procesarData(){
    $fieldId = $this->fieldIds[$this->index];

    /**
     * si la llave posee el caracter '/' es una relacion um, lo ignoramos, sera procesada posteriormente.
     */
    if(strpos($this->fieldIds[$this->index], "/")) {
      $this->index++;
      $this->procesarData();
      return;
    }

    if($fieldId == $this->entityName){
      $this->index++;
      if($this->index < count($this->fieldIds) ) $this->procesarData();
      $this->procesarEntity();
    } else {
      if(!empty($this->rel[$fieldId]["parent"]) && !in_array($fieldId, $this->fieldIdsProcesados)) {
        if(++$index < count($this->fieldIds) ) $this->procesarData($index);
        else throw new Exception("Falta una entrada para el padre de ". $fieldId);
        $this->procesarData($index);
      }
      $this->procesarFk($fieldId);
    }    

    if(++$index < count($this->fieldIds) ) $this->procesarData($index);
  }

  public function procesarUm(){
    throw new Exception("Not implemented");
  }


  public function procesarFk($fieldId){
    if(in_array($fieldId, $this->fieldIdsProcesados)) return;

    array_push($this->fieldIdsProcesados, $fieldId);
    //Definir $entityName, $fieldName en base a $fieldId y $this->entityName
    $r = $this->container->relations($this->entityName)[$fieldId];
    
    //Ejecutar controlador
    $p = $this->container->controller("persist_sql", $r["entity_name"]);
    $persist = $p->main($this->params[$fieldId]);
    
    //Actualizar $this->sql y $this->detail
    $this->sql .= $persist["sql"];
    array_push($this->detail, $r["entity_name"].$persist["id"]);
    
    //5) Definir valor de fk
    $idValue = ($persist["_mode"] == "delete") ? null : $persist["id"];

    //6) Asignar pk 
    $this->data[$fieldId]["id"] = $idValue;

    //7) Asignar fk
    if($r["parent"]) $this->data[$r["parent"]][$r["field_name"]] = $idValue;
    else $this->data[$this->entityName][$r["field_name"]] = $idValue;
  }

  public function procesarEntity(){
    //persistir
    $persist = $this->container->controller("persist_sql", $this->entityName);
    $p = $persist->main($this->params[$this->entityName]);

    //Actualizar $this->sql y $this->detail
    $this->sql .= $p["sql"];
    array_push($this->detail, $this->entityName.$p["id"]);
    return ($p["_mode"] == "delete") ? null : $p["id"];
  }

}



