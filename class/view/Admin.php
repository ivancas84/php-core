<?php

require_once("class/view/View.php");
require_once("class/model/Sqlo.php");
require_once("class/model/Render.php");
require_once("class/model/Dba.php");

class EntityViewAdmin extends View {

  public $id;
  public $entityName;
  public $value;

  public function __construct($entityName){
    $this->entityName = $entityName;
  }

  

 
}
