<?php

require_once("class/model/Sqlo.php");
require_once("class/model/Render.php");
require_once("class/model/Dba.php");



abstract class EntityViewList {

  public $entityName;
  public $template;
  public $search;
  public $page;
  public $index;
  public $rows;

  public function search(){
    $render = new Render();
    $render->setCondition(["search_","=",$this->search]);
    $sql = EntitySqlo::getInstanceRequire($this->entityName)->all($render);
    $this->$rows = Dba::fetchAll($sql);    
  }

  public function show(){

  }
}
