<?php

require_once("class/model/Render.php");

class RenderBuild {
  /**
   * Definir objeto de presentacion a partir de una entidad (real o ficticia)
   * Es util cuando se necesita definir condiciones para entidades ficticias
   */

  public $container; //contenedor
  public $entityName; //entidad principal (real o ficticia)
  /**
   * Si la entidad principal es ficticia debe redefinirse la clase para contemplar los cambios
   */

  public function main($display = null) {
    $render = Render::getInstanceDisplay($display);
    $render->entityName = $this->entityName;
    return $render;
  }

}

