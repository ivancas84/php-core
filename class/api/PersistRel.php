<?php



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

  private static function compare($a, $b) {
    $a_ = (strpos($a_, '-') !== false) ? substr_count($a, '_') : 0;
    $b_ = (strpos($b_, '-') !== false) ? substr_count($b, '_') : 0;
    
    if ($a_ == $b_)  return 0;
    return ($a_ > $b_) ? -1 : 1;
  }

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);

    $data = usort(php_input(), "PersistRelApi", "compare");
    /**
     * Array asociativo
     * Cada llave identifica:
     * 1) La entidad actual: Ejemplo "alumno"
     * 2) Una relacion fk directa o indirecta (si posee el caracter "-"): Ej "per-alumno", "per_dom-alumno"
     * para el ejemplo anterior, el sufijo luego del caracter "-" coincide con la entidad principal
     * 3) @todo Una relacion um (si posee el caracter "^"): Ej alu^nota, alu_per^nota,
     * para el ejemplo anterior nota seria la entidad y alu_per la relacion
     * De esta forma se verifica existencia de "-" para saber que es una relacion
     * y en el caso de que exista, se verifica "_" para saber la "profundidad" de la relacion
     * En base a la "profundidad" se define el orden de persistencia
     **/    

    $response = [];
    foreach($data as $key => $row) {
      $e = explode("-",$key);
      if(count($e) == 2) {
        $entityName = get_entity_relations($e[1])[$e[0]];
        
        $render = $this->container->getControllerEntity("render_build", $entityName)->main();
        $p = $this->container->getController("persist_sql");
        $persist = $p->id($render->entityName, $data);
        /**
         * Para chequear existencia (realizar una insercion o actualizacion)
         * utiliza el id, el chequeo de unicidad debe hacerse en el cliente.
         * o en su defecto utilizar api PersistRelUnique
         */
        $persist = $this->container->getDb()->multi_query_transaction($persist["sql"]);
        $response[$key] = ["id" => $persist["id"], "detail" => [$entityName.$persist["id"]]];
      }
    }
    return $response;
  }
}



