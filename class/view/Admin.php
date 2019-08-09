<?php

require_once("class/view/View.php");


class EntityViewAdmin extends View {

  public $entity; //Entity: Entidad principal de administracion
  public $admin; //EntityAdminController: Controlador de administracion de la entidad principal
  public $row; //valores del formulario

  public function main(){
    $id = Filter::request("id");

    if($id){
      $this->row = $initialize->idOrDefault();
    } else {
      $this->row = $initialize->default();
    }

    $this->display();
  }








  

 
}
