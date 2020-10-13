<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");


class TestApi extends BaseApi {
  /**
   * Controlador de prueba
   **/

  public function main(){ return $this->entityName; }

}
