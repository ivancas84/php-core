<?php

require_once("class/model/Rel.php");
require_once("function/php_input.php");

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

  
  public static function compare($a, $b) {
    /**
     * Comparacion de elementos de un array para calcular la profundidad de la relacion
     */
    $a_ = (strpos($a, '-') !== false) ? ($c = substr_count($a, '_') ? $c : 1) : 0;
    $b_ = (strpos($b, '-') !== false) ? ($c = substr_count($b, '_') ? $c : 1) : 0;
    
    if ($a_ == $b_)  return 0;
    return ($a_ > $b_) ? -1 : 1;
  }

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    $data = php_input();
    uksort($data, array("PersistRelApi", "compare"));
    /**
     * Array asociativo
     * Cada llave identifica:
     * 1) La entidad actual: Ejemplo "alumno"
     * 2) Una relacion fk directa o indirecta (si posee el caracter "-"): Ej "per-alumno", "per_dom-alumno"
     * para el ejemplo anterior, el sufijo luego del caracter "-" coincide con la entidad principal
     * 3) @todo Una relacion um (si posee el caracter "."): Ej nota.alumno, per-toma.docente,
     * para el ejemplo anterior nota seria la entidad y alu_per la relacion
     * De esta forma se verifica existencia de "-" para saber que es una relacion
     * y en el caso de que exista, se verifica "_" para saber la "profundidad" de la relacion
     * En base a la "profundidad" se define el orden de persistencia
     **/    

    $response = [];
    $detail  = [];
    foreach($data as $key => $row) {
      $e = explode("-",$key);
      if(count($e) == 2) {
        $entityName = get_entity_relations($this->entityName)[$e[0]];
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
            $e_ = explode("-",$key);
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
         * o en su defecto utilizar api PersistRelUnique
         */
        $result = $this->container->getDb()->multi_query_transaction($persist["sql"]);
        array_push($detail, $this->entityName.$persist["id"]);
        return ["id" => $persist["id"], "detail" => $detail];
      } 

    }
    return $response;
  }
}



