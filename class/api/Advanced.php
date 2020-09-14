<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class AdvancedApi {
  /**
   * Consultas avanzadas a una entidad
   * Las consultas avanzadas pueden acceder a campos no habituales
   * No se realiza un formato json de los campos obtenidos
   * Se devuelve el formato de la base pudiendo no coincidir con el esperado
   * Hacer un chequeo del valor recibido para ver si coincide con el formato esperado
   */

  public $entityName;
  public $container;

  public function main() {
    $display = Filter::jsonPostRequired();
    $render = Render::getInstanceDisplay($display);
    $rows = $this->container->getDb()->advanced($this->entityName, $render);
    return $rows;
  }

}
