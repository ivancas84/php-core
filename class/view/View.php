<?php

class View {

  public $index = PATH_VIEW_INDEX;
  public $title;
  public $content;

  public static function getInstance($entity) { //instancia a partir de string  
    $className = snake_case_to("XxYy", $entity) . "ViewAdmin";
    $instance = new $name;
    return $instance;
  }

  final public static function getInstanceRequire($entity) {    
    require_once("class/view/admin/" . snake_case_to("XxYy", $entity) . ".php");
    return self::getInstance($entity);
  }

  public function title(){ return $this->title(); }
  public function content(){ return $this->content(); }
  public function display(){ require_once($this->index); }

  

}
