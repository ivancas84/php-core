<?php

abstract class BaseApi {
  /**
   * Controlador base
   * Todos los controladores que no se adaptan a las estructuras existentes pueden definirse con Base
   * El usuario puede retornar el valor que desee 
   * Data esta pensado para ser llamado a traves de una api
   * En el caso de que no se utilice una api, conviene utilizar directamente ModelTools.
   * Los controladores Base habitualmente relacionan un conjunto de entidades.
   * Cada valor del resultado de un controlador base al ser tan dependiente de otras entidades no se almacena en el storage
   * Cuando se va a devolver un conjunto de datos derivados dependiente de otras entidades se recomienda utilizar una api Base
   * evitando las apis estructurales
   **/
  
  public $entityName; 
  public $container;

  abstract public function main();

}
