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
    //@todo falta corroborar que los ida a eliminar pertenezcan verdaderamente a la entidad (sobre todo si se esta utilizando una entidad ficticia)
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $ids = php_input();
    $render = $this->container->getControllerEntity("render_build", $this->entityName)->main();
    $sql = $this->container->getSqlo($render->entityName)->deleteAll($ids);
    $this->container->getDb()->multi_query_transaction_log($sql);
    $detail = array_map(array($this, 'concat'), $ids);    

    return ["ids" => $ids, "detail" => $detail];
  }
}



