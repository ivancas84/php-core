<?php
require_once("class/model/Render.php");

class DisplayRender {
  /**
   * Controlador asociado a entidad
   * Transformar parametros (display) en presentacion (render)
   * Display es similar a Render pero se estructura a traves de arrays (construido mediante un json)
   * Display se obtiene principalmente a traves de parametros desde el cliente
   */

  public $entityName;
  public $container;

  public function main($display) {
    return Render::getInstanceDisplay($display);
  }

}
