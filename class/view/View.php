<?php

class View {

  public $index = "public/index/index.html";
  public $title = SYS_NAME;
  public $content;



  public function title(){ return $this->title; }
  public function content(){ return $this->content; }
  public function display(){ require_once($this->index); }

  

}
