<?php

require_once("class/api/Base.php");

class RelationsApi extends BaseApi {

  public $container;

  public function main(){
    return $this->container->getEntitiesRelationsJson();    
  }
}



