<?php


require_once("function/php_input.php");

class DeleteApi {
  /**
   * Comportamiento general de eliminacion
   * UTILIZAR CON PRECAUCION
   */

  public $entityName;
  public $container;
  public $permission = "w";

  public function concat($id) {
    return($this->entityName . $id);
  }  

  public function main(){
    /**
     * @todo falta corroborar que los ids a eliminar pertenezcan verdaderamente a la entidad 
     * (sobre todo si se esta utilizando una entidad ficticia)
     * y tambien visualizar correctamente el caso de que no se pueda eliminar
     * para el caso de que la entidad a eliminar forme parte de una clave foranea dispara el error
     * Cannot delete or update a parent row: a foreign key constraint fails (`planfi10_20203`.`curso`, CONSTRAINT `fk_curso_asignatura1` FOREIGN KEY (`asignatura`) REFERENCES `asignatura` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION)
     */
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $ids = php_input();
    $render = $this->container->getControllerEntity("render_build", $this->entityName)->main();
    $sql = $this->container->getSqlo($render->entityName)->deleteAll($ids);
    $this->container->getDb()->multi_query_transaction($sql);
    $detail = array_map(array($this, 'concat'), $ids);    

    return ["ids" => $ids, "detail" => $detail];
  }
}



