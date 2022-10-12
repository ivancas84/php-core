<?php

require_once("function/snake_case_to.php");
require_once("class/model/Sql.php");
require_once("class/model/Render.php");
require_once("function/settypebool.php");
require_once("function/get_entity_rel.php");
require_once("function/array_add_prefix.php");

class EntityRel {
  /**
   * Metodos adicionales definidos a partir de relaciones entre entidades
   */

  public $container;
  public $entityName;
  public $prefix = "";


  public function mapping($field){
    /**
     * Traducir campo para ser interpretado correctamente por el SQL
     */
    $f = explode("-",$field);
    if(count($f) == 2) {
      $prefix = (empty($this->prefix)) ? $f[0] : $this->prefix . "_" . $f[0];
      $entityName = get_entity_rel($this->entityName)[$f[0]]["entity_name"];
      if($r = $this->container->getMapping($entityName, $prefix)->_($f[1])) return $r;
    } 
    if($f = $this->container->getMapping($this->entityName, $this->prefix)->_($field)) return $f;
    throw new Exception("Campo no reconocido para {$this->entityName}: {$field}");
  }
  
  public function condition($field, $option, $value){
    /**
     * Condicion avanzada principal
     * Define una condicion avanzada que recorre todos los metodos independientes de condicion avanzadas de las tablas relacionadas
     * La restriccion de conditionFieldStruct es que $value no puede ser un array, ya que definirá un conjunto de condiciones asociadas
     */
    $f = explode("-",$field);
    if(count($f) == 2) {
      $prefix = $f[0];
      $entityName = get_entity_rel($this->entityName)[$f[0]]["entity_name"];
      return $this->container->getCondition($entityName, $prefix)->_($f[1], $option, $value);
    } 
    return $this->container->getCondition($this->entityName)->_($field, $option, $value);
  }
  
  public function conditionAux($field, $option, $value) {
    /**
     * Condicion de field auxiliar
     */
    $f = explode("-",$field);
    if(count($f) == 2) {
      $prefix = $f[0];
      $entityName = get_entity_rel($this->entityName)[$f[0]]["entity_name"];
      if($c = $this->container->getConditionAux($entityName, $prefix)->_($f[1], $option, $value)) return $c;
    } 
    if($c = $this->container->getConditionAux($this->entityName)->_($field, $option, $value)) return $c;
  }

  /**
   * Array de nombres de campos definidos entidad principal y sus relaciones, de la forma fieldId-fieldName
   */
  public function fieldNames(){
    $fieldNames = $this->container->getEntity($this->entityName)->getFieldNames();
    foreach(get_entity_rel($this->entityName) as $prefix => $value){
      $fieldNames = array_unique(
        array_merge(
          $fieldNames, 
          array_add_prefix(
            $this->container->getEntity($value["entity_name"])->getFieldNames(),
            $prefix."-"
          )
        )
      );
    }
    return $fieldNames;
  }

  
  
  public function join(Render $render){
    return $this->container->getControllerEntity("rel_join", $this->entityName)->main($render);
  }

  public function json($row){
    return $this->container->getControllerEntity("rel_json", $this->entityName)->main($row);
  }

  public function json2($row){
    /**
     * @return $example = [ //para la entidad alumno
     *   "id" => "...",
     *   "activo" => false,
     *   "persona_" => [
     *     "id" => "..."
     *     "numero_documento" > "..."¨
     *     "domicilio_" => [ ... ]
     *   ]
     * ]
     */
    return $this->container->getControllerEntity("rel_json_2", $this->entityName, $this->prefix)->main($row);
  }

  public function value($row){
    /**
     * Recorre la cadena de relaciones del resultado de una consulta y retorna instancias de EntityValues
     * El resultado es almacenado en un array asociativo.
     * Las claves del array son identificadores unicos representativos del nombre del campo
     * Las claves se forman a partir del nombre de la clave foranea (se extrae de las funciones de identificacion)
     * Se asigna un numero incremental a la clave en el caso de que se repita
     * A diferencia de otros métodos que retornan valores,  
     * values utiliza un array asociativo debido a que el valor es un objeto
     * facilita el acceso directo desde la llave por ejemplo $resultado["nombre_fk"]->metodo()
     * En el caso por ejemplo del metodo json, debido a que el valor es tambien un array asociativo, 
     * tiene sentido acomodarlo como un arbol de valores, identificandolos con el prefijo "_",
     * por ejemplo $resultado["nombre_fk] = "id_fk"
     * $resultado["_nombre_fk"] = array asociativo con los valores de la entidad para el id "id_fk"
     */
    return $this->container->getControllerEntity("rel_value", $this->entityName)->main($row);
  }


  public function jsonAll($rows){
    foreach($rows as &$row) $row = $this->json($row);
    return $rows;
  }

}
