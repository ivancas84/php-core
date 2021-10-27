<?php

require_once("class/model/Render.php");

class RenderBuild {
  /**
   * Definir objeto de presentacion a partir de una entidad (real o ficticia)
   * Es util cuando se necesita definir condiciones para entidades ficticias
   * Por ejemplo si se indica la entidad ficticia "alumno_activo", 
   * RenderBuild generara un nuevo Render para la entidad "alumno" agregando la condicion "activo = true"
   * En otras palabras RenderBuild tiene dos propositos principales:
   * 1) Traducir una entidad ficticia a una entidad real mas una condicion
   * 2) Aplicar una condicion a una entidad real para restringir permisos segun el rol del usuario
   */

  public $container; //contenedor
  public $entityName; //entidad principal (real o ficticia)

  public function main($display = null) {
    $render = Render::getInstanceDisplay($display);
    $render->entityName = $this->entityName;
    $render->container = $this->container;
    return $render;
  }

}

