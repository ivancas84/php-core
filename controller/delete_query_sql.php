<?php


class DeleteQuerySql {
  /**
   * Definir SQL de eliminacion.
   * Se realiza una consulta a la base de datos para obtener los ids a eliminar.
   * Se invoca al sql de eliminacion a traves de los ids definidos
   */

  public $container;
  public $entity_name;

  public function main(EntityQuery $query) {
    $ids = $query->fieldAdd("id")->column();
    if(empty($ids)) return ["ids" => [], "sql"=>""];

    $sql = $this->container->persist($this->entity_name)->delete($ids);
    return["ids" => $ids, "sql"=>$sql];
  }
}