<?php


class DeleteRenderSql {
  /**
   * Definir SQL de eliminacion.
   * Se realiza una consulta a la base de datos para obtener los ids a eliminar.
   * Se invoca al sql de eliminacion a traves de los ids definidos
   */

  public $container;
  public $entityName;

  public function main(Render $render) {
    $ids = $this->container->getDb()->ids($this->entityName, $render);
    if(empty($ids)) return ["ids" => [], "sql"=>""];

    $sql = $this->container->getSqlo($this->entityName)->delete($ids);
    return["ids" => $ids, "sql"=>$sql];
  }
}