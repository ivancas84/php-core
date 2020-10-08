<?php

require_once("function/snake_case_to.php");
require_once("class/model/Sql.php");
require_once("class/model/Render.php");
require_once("function/settypebool.php");

class EntityRel {
  /**
   * Metodos adicionales definidos a partir de relaciones entre entidades
   */

  public $container;
  public $entityName;

  public function mapping($field){
    /**
     * Traducir campo para ser interpretado correctamente por el SQL
     */
    if($field_ = $this->container->getMapping($this->entityName)->_eval($field)) return $field_;
    throw new Exception("Campo no reconocido para {$this->entityName}: {$field}");
  }
  
  public function condition($field, $option, $value){
    /**
     * Condicion avanzada principal
     * Define una condicion avanzada que recorre todos los metodos independientes de condicion avanzadas de las tablas relacionadas
     * La restriccion de conditionFieldStruct es que $value no puede ser un array, ya que definirá un conjunto de condiciones asociadas
     */
    if($c = $this->container->getCondition($this->entityName)->_eval($field, [$option, $value])) return $c;
  }
  
  public function conditionAux($field, $option, $value) {
    /**
     * Condicion de field auxiliar (considera relaciones si existen)
     * Se sobrescribe si tiene relaciones
     */
    if($c = $this->container->getConditionAux($this->entityName)->_eval($field, [$option, $value])) return $c;
  }


  public function fields(){
    /**
     * Definir sql de campos
     * Sobrescribir si existen relaciones
     */
    return implode(",", $this->container->getFieldAlias($this->entityName)->_toArray());
  }



  public function join(Render $render){ return ""; } //Sobrescribir si existen relaciones fk u_


  public function json(array $row) { 
    /**
     * Recorre la cadena de relaciones del resultado de una consulta 
     * y retorna el resultado en un arbol de array asociativo en formato json.
     * Ver comentarios del metodo values para una descripcion del valor retornado
     * Este metodo debe sobscribirse en el caso de que existan relaciones     
     */ 
    return $this->container->getValue($this->entityName)->_fromArray($row, "set")->_toArray("json");
  }

  public function value(array $row){
    /**
     * Recorre la cadena de relaciones del resultado de una consulta y retorna instancias de EntityValues
     * El resultado es almacenado en un array asociativo.
     * Las claves del array son nombres representativos de la entidad que contiene
     * Las claves se forman a partir del nombre de la clave foranea
     * Se asigna un numero incremental a la clave en el caso de que se repita
     * Este metodo debe sobrescribirse en el caso de que existan relaciones
     * A diferencia de otros métodos que retornan valores, 
     * values utiliza un array asociativo debido a que el valor es un objeto
     * facilita el acceso directo desde la llave por ejemplo $resultado["nombre_fk"]->metodo()
     * En el caso por ejemplo del metodo json, debido a que el valor es tambien un un array asociativo, 
     * tiene sentido acomodarlo como un arbol de valores, identificandolos con el prefijo "_",
     * por ejemplo $resultado["nombre_fk] = "id_fk"
     * $resultado["_nombre_fk"] = array asociativo con los valores de la entidad para el id "id_fk"
     */
    $row_ = [];
    $row_[$this->entityName] = $this->container->getValue($this->entityName)->_fromArray($row, "set");
    return $row_;
  }



}
