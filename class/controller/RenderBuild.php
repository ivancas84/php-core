<?php

require_once("class/model/Render.php");

class RenderBuild {
  /**
   * @todo puede resultar medio confuso!
   * Se asignan la entidad correcta y las relaciones, pero despues que valor se debe utilizar?
   * La entidad ficticia o la entidad real?
   * Puede ser confuso para ciertos metodos en los cuales solo se puede utilizar la entidad real
   * Por ejemplo getRel->json2()
   */

  /**
   * Definir objeto de presentacion a partir de una entidad (real o ficticia)
   * Es util cuando se necesita definir condiciones para entidades ficticias
   * Por ejemplo si se indica la entidad ficticia "alumno_activo", 
   * RenderBuild generara un nuevo Render para la entidad "alumno" agregando la condicion "activo = true"
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

