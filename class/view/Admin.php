<?php

require_once("class/view/View.php");
require_once("function/snake_case_to.php");

class EntityViewAdmin extends View {

  public $entity; //Entity: Entidad principal de administracion
  public $initialize; //EntityInitializeController: Controlador de administracion de la entidad principal
  public $row; //valores del formulario

  public static function getInstance($entity) { //instancia a partir de string  
    $className = snake_case_to("XxYy", $entity) . "ViewAdmin";
    $instance = new $className;
    return $instance;
  }

  final public static function getInstanceRequire($entity) {    
    require_once("class/view/admin/" . snake_case_to("xxYy", $entity) . "/" . snake_case_to("XxYy", $entity) . ".php");
    return self::getInstance($entity);
  }

  public function main(){
    $id = Filter::request("id");

    if($id){
      $this->row = $this->initialize->getOrDefault($id);
    } else {
      $this->row = $this->initialize->default();
    }

    $this->display();
  }








  

 
}
