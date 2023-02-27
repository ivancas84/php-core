<?php

require_once("api/base.php");

class RelationsApi extends BaseApi {

  public $container;

  public function main(){
    return $this->container->relationsJson();    
  }
}



