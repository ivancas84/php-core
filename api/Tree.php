<?php

require_once("class/api/Base.php");

class TreeApi extends BaseApi {

  public $container;

  public function main(){
    return $this->container->treeJson();    
  }
}



