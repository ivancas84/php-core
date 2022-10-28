<?php

require_once("class/api/Base.php");

class RelApi extends BaseApi { //1

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    return $this->container->relations($this->entityName);    
  }
}



