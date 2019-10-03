<?php

require_once("class/view/View.php");


class EntityViewAdmin_ extends View { //procesamiento

  public $entity; //Entity: Entidad principal de administracion
  public $admin; //EntityAdminController: Controlador de administracion de la entidad principal
  public $row; //valores del formulario

  public function main(){
    $this->row = Filter::requestAll();

    $this->display();
  }








  

 
}
