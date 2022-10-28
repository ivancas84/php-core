<?php

require_once("api/Base.php");

class RelApi extends BaseApi { //1

  public function main(){
    $this->container->auth()->authorize($this->entityName, $this->permission);
    return $this->container->relations($this->entityName);    
  }
}



