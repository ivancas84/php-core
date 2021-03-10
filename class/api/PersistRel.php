<?php

require_once("class/model/Rel.php");
require_once("function/php_input.php");
require_once("function/get_entity_rel.php");

class PersistRelApi {
  /**
   * Comportamiento general de persistencia de elementos relacionados
   * 
   * Comportamiento por defecto
   * 1) Si existe el id para una determinada entidad, se considera actualizacion, sino insercion.
   * 2) Considera que la existencia de valores unicos debe hacerse en el cliente.
   */

  public $entityName; //entidad principal
  public $container;
  public $permission = "w";

  
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

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $data = php_input();
    /**
     * Array asociativo
     * Cada llave identifica:
     * 1) La entidad actual: Ejemplo "alumno"
     * 2) Una relacion fk directa "per" o indirecta "per_dom"
     * 3) @todo Una relacion um (si posee el caracter "/"): 
     *   Ej 1 nota/alumno: nota (entidad) nota.alumno (fk), 
     *   Ej 2: per-toma/docente: toma (entidad) toma.docente (fk), 
     *     luego docente esta asociado con alumno 
     *     a traves de la relacion indicada en el prefijo "per"
     */    
    
    uksort($data, array($this, "compare"));
    /**
     * Para el ordenamiento se verifica el caracter "_" 
     * para calcular la profundidad.
     * En base a la "profundidad" se define el orden de persistencia
     */

    $response = [];
    $detail  = [];
    foreach($data as $key => $row) {
      if($key != $this->entityName) {
        $entityName = get_entity_relations($this->entityName)[$key];
        $render = $this->container->getControllerEntity("render_build", $entityName)->main();
        $p = $this->container->getController("persist_sql");
        $persist = $p->id($render->entityName, $data[$key]);
        /**
         * Para chequear existencia (realizar una insercion o actualizacion)
         * utiliza el id, el chequeo de unicidad debe hacerse en el cliente.
         * o en su defecto utilizar api PersistRelUnique
         */
        $result = $this->container->getDb()->multi_query_transaction($persist["sql"]);
        array_push($detail, $entityName.$persist["id"]);
        
        //***** asignar fk *****/

        $pos = strrpos($e[0],"_");
        if($pos !== false){ 
          $s = substr($e[0], 0, $pos);
          foreach($data as $key => &$value){
            $e_ = explode("/",$key);
            if($e_ == $s) $value[$e[1]] = $persist["id"];
          }
        } else {
          $data[$this->entityName][$e[1]] = $persist["id"];
        }
      } else {
        $render = $this->container->getControllerEntity("render_build", $this->entityName)->main();
        $p = $this->container->getController("persist_sql");
        $persist = $p->id($render->entityName, $data[$this->entityName]);
        /**
         * Para chequear existencia (realizar una insercion o actualizacion)
         * utiliza el id, el chequeo de unicidad debe hacerse en el cliente.
         * o en su defecto utilizar api PersistRelUnique (en construccion)
         */
        $result = $this->container->getDb()->multi_query_transaction($persist["sql"]);
        array_push($detail, $this->entityName.$persist["id"]);
        return ["id" => $persist["id"], "detail" => $detail];
      } 

    }
    return $response;
  }
}


