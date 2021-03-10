<?php
require_once("class/api/UniqueRel.php");

class UniqueRelArrayApi extends UniqueRel {
  /**
   * Especializacion de UniqueRel en el que modifica el valor de retorno
   * en un unico array utilizando prefijos correspondientes
   */

  public $row = []; //Resultado estruturado
  /**
   * @example [
   *   "id" => "...", //alumno.id
   *   "activo" => "..." //alumno.activo
   *   "..."
   *   "per/persona-id" => "..."
   *   "per/persona-numero_documento" => "..."
   *   ],
   *   "per_dom/domicilio-id" => [ ... ] //per_dom/domicilio: nombre de la relacion (per_dom) y fk correspondiente (persona.domicilio)
   * ]
   * Se incluye el nombre de la clave foranea para reducir el tiempo de procesamiento en el servidor
   */

  protected function recursive(array $tree, $data){
    foreach ($tree as $prefix => $value) {
      if(array_key_exists($prefix, $data) && isset($data[$prefix]["id"])){
        /**
         * Si existe $prefix en $data significa que existen datos en la base de datos inicializados
         * se ignoran los parametros y se asignan los valores de $data
        */
        foreach($data as $key => $value)
          $this->row[$prefix."/".$tree[$prefix]["field_name"] . "-". $key] = $value;
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
            foreach($data as $key => $value)
              $this->row[$prefix."/".$tree[$prefix]["field_name"] . "-". $key] = $value;
          } else {
            $data = [];
            foreach($this->params[$prefix] as $key => $value)
              $this->row[$prefix."/".$tree[$prefix]["field_name"] . "-". $key] = $value;
          }
        }
      } 

      if(!empty($value["children"])) $this->recursive($value["children"], $data);
    }
  }
  
}
