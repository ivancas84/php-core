<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("function/get_entity_tree.php");
require_once("function/php_input.php");

class UniqueRelApi { //1.1
  /**
   * Consulta de campos unicos de una entidad o sus relaciones
   * Se realiza un analisis de los parametros, y en funcion de sus prefijos, se consulta a la base de datos de la entidad o sus relaciones
   * Trabaja con relaciones fk
   */

  public $entityName;
  public $container;
  public $permission = "r";
  public $params = [];
  /**
   * Lista de parametros
   * @example para la entidad alumno [
   *   "id" => "value", //hace referencia al campo alumno.id
   *   "per-numero_documento" => "value" //hace referencia al campo persona.numero_documento
   *   "per_dom-calle" => "value" //hace referencia al campo domicilio.calle
   * ]
   */

  public $row = []; //Resultado estructurado
  /**
   * @example Para la entidad alumno [
   *   "alumno" => [
   *     "id" => "...",
   *     "activo" => "..."
   *     "..."
   *   ],
   *   "per" => [ //per: nombre de la relacion, persona: nombre de la clave foranea (alumno.persona)
   *     "id" => "..."
   *     "numero_documento" => "..."
   *   ],
   *   "per_dom" => [ ... ] //per_dom: nombre del a relacion, domicilio: nombre de la clave foranea (persona.domicilio)
   * ]
   */

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    if(empty($this->params)) $this->params = php_input();
    /**
     * Mediante el if, habilitamos el uso de la api sin necesidad de utilizar rest
     * se deben asignar params antes de invocar al main
     */
    
    $this->identifyParams();
    $this->query();
    return $this->row;
  }

  public function identifyParams(){
    /**
     * Recorrer la lista de parametros y clasificarlos en base a sus prefijos
     * Ej. Si se recibe alu_per-nombres, se define $this->params["alu_per"] = "nombres"
     *   
     * 
     */
    $params = [];
    foreach($this->params as $key => $value){
      $el = explode("-", $key);
      if(count($el) == 2) $params[$el[0]][$el[1]] = $value;
      else $params[$this->entityName][$el[0]] = $value;
    }
    $this->params = $params;

  }

  public function query(){
    $tree = get_entity_tree($this->entityName);

    if(array_key_exists($this->entityName,$this->params)){
      $render = $this->container->getRender($this->entityName);
      $row = $this->container->getDb()->unique($render->entityName, $this->params[$this->entityName]);
      if(!empty($row)) {
        $data = $this->container->getRel($render->entityName)->json2($row);
        $this->row[$this->entityName] = $data[$this->entityName];
      } else {
        $data = [];
        $this->row[$this->entityName] = $this->params[$this->entityName];
      }
    } else {
      $data = [];
      $this->row[$this->entityName] = [];
    } 
    $this->recursive($tree, $data, $this->entityName);
  }

  protected function recursive(array $tree, $data, $previousKey){
    foreach ($tree as $prefix => $value) {
     if(array_key_exists($prefix, $data) && isset($data[$prefix]["id"])){
        /**
         * Si existe $prefix en $data significa que existen datos en la base de datos inicializados
         * se ignoran los parametros y se asignan los valores de $data
        */
        $this->row[$prefix] = $data[$prefix];
      } elseif(array_key_exists($value["field_name"], $this->row[$previousKey]) && !empty($this->row[$previousKey][$value["field_name"]])){
        /**
         * Si no existe $prefix en $data significa que no existen datos en la base de datos inicializados
         * se verifica la existencia de parametros para inicializar.
         * 
         * En primer lugar se verifica la existencia de parametros en la entidad padre identificada con previousKey, que correspondan a la entidad actual
         * En el caso de que exista un valor valido en la entidad padre se ignoran los parametros de la entidad actual
         */
        $data = $this->previousParam($value, $prefix, $previousKey);
      } elseif(array_key_exists($prefix,$this->params)){
        /**
         * En segundo lugar se verifican los parametros de la entidad actual
         */
        $data = $this->prefixParam($value, $prefix);
      } else {
        /**
         * En tercer lugar, al no existir parametros, se inicializa vacio
         */
        $this->row[$prefix] = [];
      } 

      if(!empty($value["children"])) $this->recursive($value["children"], $data, $prefix);
    }
  }

  public function previousParam($leaf, $prefix, $previousKey){
    $render = $this->container->getRender($leaf["entity_name"]);
    $row = $this->container->getDb()->get($render->entityName, $this->row[$previousKey][$leaf["field_name"]]);
    if(!empty($row)) {
      $data = $this->container->getRel($render->entityName, $prefix)->json2($row);
      $this->row[$prefix] = $data[$prefix];
    } else {
      $data = [];
      $this->row[$prefix] = [ "id" => $this->row[$previousKey][$leaf["field_name"]]];
    }
    return $data;
  }

  public function prefixParam($leaf, $prefix){
    $render = $this->container->getRender($leaf["entity_name"]);
    $row = $this->container->getDb()->unique($render->entityName, $this->params[$prefix]);
    if(!empty($row)) {
      $data = $this->container->getRel($render->entityName, $prefix)->json2($row);
      $this->row[$prefix] = $data[$prefix];
    } else {
      $data = [];
      $this->row[$prefix] = $this->params[$prefix];
    } 
    return $data;   
  }
}
