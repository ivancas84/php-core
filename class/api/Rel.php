<?php

require_once("class/api/Base.php");
require_once("function/get_entity_rel.php");

class RelApi extends BaseApi { //1

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    return get_entity_rel($this->entityName);    
  }
}



