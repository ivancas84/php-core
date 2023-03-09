<?php

require_once("api/base.php");

class TreeApi extends BaseApi {

  public $container;

  public function main(){
    return $this->container->tree_json();    
  }
}



