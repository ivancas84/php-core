<?php

require_once("class/model/Sqlo.php");
require_once("class/model/Render.php");
require_once("class/model/Dba.php");



class View {

  public $index = PATH_VIEW_INDEX;
  public $title;
  public $content;

  public function title(){ return $this->title(); }
  public function content(){ return $this->content(); }
  public function display(){ require_once($this->index); }

}
