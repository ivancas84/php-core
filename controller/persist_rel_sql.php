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

  public $entity_name; //entidad principal
  public $container;
  public $data = [];
  public $detail = [];
  public $sql = "";
  public $rel;
  public $fieldIds;
  public $fieldIdsProcesados = []; //control de recursion
  public $index = 0;

  public function main($data){
    $this->data = $data;
    $this->fieldIds = array_keys($this->data);
    $this->rel = $this->container->relations($this->entity_name);
    $this->procesarData();
    //$this->procesarUm();

    return ["id" => $this->data[$this->entity_name]["id"], "detail" => $this->detail, "sql"=>$this->sql];
  }

  public function procesarData(){
    if($this->index >= count($this->fieldIds) ) return; //control de recursion

    $fieldId = $this->fieldIds[$this->index];

    /**
     * si la llave posee el caracter '/' es una relacion um, lo ignoramos, sera procesada posteriormente.
     */
    if(strpos($fieldId, "/")) {
      $this->index++;
      $this->procesarData();
      return;
    }

    /**
     * si la llave es la entidad actual, procesamos el resto de las llaves
     */
    if($fieldId == $this->entity_name){
      $this->index++;
      $this->procesarData();
      $this->procesarEntity();
      return;
    } 
    
    /**
     * si la llave posee padre no procesado, procesamos el resto de las llaves
     */
    if(!empty($this->rel[$fieldId]["parent_id"]) && !in_array($fieldId, $this->fieldIdsProcesados)) {
      $this->index++;
      $this->procesarData();
      $this->procesarFk($fieldId);
      return;
    }    

     /**
     * si llegamos hasta aca es porque vamos a procesar una fk sin padre
     */
    $this->procesarFk($fieldId);
    $this->index++;
    $this->procesarData();
  }

  public function procesarUm(){
    throw new Exception("Not implemented");
  }


  public function procesarFk($fieldId){
    array_push($this->fieldIdsProcesados, $fieldId);

    //Definir $entity_name, $field_name en base a $fieldId y $this->entity_name
    $r = $this->container->relations($this->entity_name)[$fieldId];
    
    //Ejecutar controlador
    $p = $this->container->controller("persist_sql", $r["entity_name"]);
    $persist = $p->main($this->data[$fieldId]);
    
    //Actualizar $this->sql y $this->detail
    $this->sql .= $persist["sql"];
    array_push($this->detail, $r["entity_name"].$persist["id"]);
    
    //5) Definir valor de fk
    $idValue = ($persist["mode"] == "delete") ? null : $persist["id"];

    //6) Asignar pk 
    $this->data[$fieldId]["id"] = $idValue;

    //7) Asignar fk
    if($r["parent_id"]) $this->data[$r["parent_id"]][$r["field_name"]] = $idValue;
    else $this->data[$this->entity_name][$r["field_name"]] = $idValue;
  }

  public function procesarEntity(){
    //persistir
    $persist = $this->container->controller("persist_sql", $this->entity_name);
    $p = $persist->main($this->data[$this->entity_name]);

    //Actualizar $this->sql y $this->detail
    $this->sql .= $p["sql"];
    array_push($this->detail, $this->entity_name.$p["id"]);
    return ($p["mode"] == "delete") ? null : $p["id"];
  }

}



