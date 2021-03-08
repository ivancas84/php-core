<?php
require_once("class/api/UniqueRel.php");

class UniqueRelArrayApi extends UniqueRel {
  /**
   * Especializacion de UniqueRel en el que modifica el valor de retorno
   * en un unico array utilizando prefijos correspondientes
   */

  public $entityName;
  public $container;
  public $permission = "r";
  public $params = [];
  /**
   * Lista de parametros
   * @example [
   *   "id" => "value", //hace referencia al campo alumno.id
   *   "per-numero_documento" => "value" //hace referencia al campo persona.numero_documento
   *   "per_dom-calle" => "value" //hace referencia al campo domicilio.calle
   * ]
   */

  public $row = []; //Resultado estruturado
  /**
   * @example Para la entidad alumno [
   *   "alumno" => [
   *     "id" => "...",
   *     "activo" => "..."
   *     "..."
   *   ],
   *   "per-persona" => [ //per: nombre de la relacion, persona: nombre de la clave foranea (alumno.persona)
   *     "id" => "..."
   *     "numero_documento" => "..."
   *   ],
   *   "per_dom-domicilio" => [ ... ] //per_dom: nombre del a relacion, domicilio: nombre de la clave foranea (persona.domicilio)
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
      $render = $this->container->getControllerEntity("render_build", $this->entityName)->main();
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
    $this->recursive($tree, $data);
  }

  protected function recursive(array $tree, $data){
    foreach ($tree as $prefix => $value) {
      if(array_key_exists($prefix, $data) && isset($data[$prefix]["id"])){
        /**
         * Si existe $prefix en $data significa que existen datos en la base de datos inicializados
         * se ignoran los parametros y se asignan los valores de $data
        */
        $this->row[$prefix."-".$tree[$prefix]["field_name"]] = $data[$prefix];
      } else {
        /**
         * Si no existe $prefix en $data significa que no existen datos en la base de datos inicializados
         * se verifica la existencia de parametros para inicializar
         */
        if(array_key_exists($prefix,$this->params)){
          $render = $this->container->getControllerEntity("render_build", $tree[$prefix]["entity_name"])->main();
          $row = $this->container->getDb()->unique($render->entityName, $this->params[$prefix]);
          if(!empty($row)) {
            $data = $this->container->getRel($render->entityName, $prefix)->json2($row);
            $this->row[$prefix."-".$tree[$prefix]["field_name"]] = $data[$prefix];
          } else {
            $data = [];
            $this->row[$prefix."-".$tree[$prefix]["field_name"]] = $this->params[$prefix];
          }
        } else {
          $this->row[$prefix."-".$tree[$prefix]["field_name"]] = [];
        }

      } 

      if(!empty($value["children"])) $this->recursive($value["children"], $data);
    }
  }
  
}
